<?php

namespace App\Services;

use App\Domains\Project\Models\Project;
use App\Domains\Task\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Support\WorkflowStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GeminiAIService
{
    private ?string $apiKey;

    private string $model;

    private bool $verifySsl;

    public function __construct(?string $apiKey = null, ?string $model = null, ?bool $verifySsl = null)
    {
        $this->apiKey = $apiKey ?: config('services.gemini.api_key');
        $this->model = $model ?: config('services.gemini.model', 'gemini-2.5-flash');
        $this->verifySsl = $verifySsl ?? (bool) config('services.gemini.verify_ssl', true);
    }

    /**
     * @param  array<int,array{role:string,text:string}>  $history
     */
    public function respond(User $user, string $message, array $history = []): string
    {
        if (blank($this->apiKey)) {
            throw new \RuntimeException('Gemini API key belum dikonfigurasi.');
        }

        $message = trim($message);

        if ($message === '') {
            throw new \InvalidArgumentException('Pesan tidak boleh kosong.');
        }

        $context = $this->buildContext($user);
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($contextJson === false) {
            $contextJson = '{}';
        }

        $systemPrompt = <<<PROMPT
Kamu adalah "Gemini", asisten AI untuk Ticket Management System internal perusahaan.
Tugasmu:
- Berikan jawaban ringkas, jelas, dan praktis menggunakan Bahasa Indonesia kecuali user meminta bahasa lain.
- Jawab pertanyaan seputar tiket, task, project, laporan, analisis sederhana, serta perhitungan umum.
- Jika konteks tidak mencukupi, katakan dengan sopan bahwa kamu tidak memiliki data tersebut dan sarankan langkah di aplikasi.
- Tolak atau alihkan pertanyaan yang meminta password, API key, data sensitif, ataupun informasi pribadi rahasia.
- Jangan pernah membuat atau menebak kredensial. Jangan menyebutkan keberadaan API key.
- Jika diberi perintah untuk melakukan tindakan di aplikasi, jelaskan langkah manualnya, karena kamu hanya asisten percakapan.

Snapshot data pengguna saat ini:
{$contextJson}
PROMPT;

        $payloadContents = $this->buildHistoryPayload($history, $message);

        $payloadContents[] = [
            'role' => 'user',
            'parts' => [[
                'text' => $message,
            ]],
        ];

        $endpoint = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $this->model,
            $this->apiKey
        );

        $request = Http::timeout(20)->asJson();

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        try {
            $response = $request->post($endpoint, [
                'systemInstruction' => [
                    'role' => 'user',
                    'parts' => [[
                        'text' => $systemPrompt,
                    ]],
                ],
                'contents' => $payloadContents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 512,
                ],
            ]);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Gagal terhubung ke Gemini: '.$e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $errorBody = data_get($response->json(), 'error.message') ?: $response->body();

            throw new \RuntimeException('Gagal terhubung ke Gemini: '.$errorBody);
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! $text) {
            throw new \RuntimeException('Gemini tidak mengembalikan jawaban.');
        }

        return trim($text);
    }

    private function buildContext(User $user): array
    {
        $stats = [
            'tickets' => ['total' => null, 'done' => null, 'open' => null],
            'tasks' => ['total' => null, 'done' => null],
            'projects' => ['total' => null, 'done' => null],
        ];

        try {
            $ticketStatusCol = $this->pickStatusColumn('tickets');
            $taskStatusCol = $this->pickStatusColumn('tasks');
            $projectStatusCol = $this->pickStatusColumn('projects');

            $ticketTotal = Ticket::count();
            $ticketDone = Ticket::query()
                ->whereIn($ticketStatusCol, WorkflowStatus::equivalents(WorkflowStatus::DONE))
                ->count();

            $taskTotal = Task::count();
            $taskDone = Task::query()
                ->whereIn($taskStatusCol, WorkflowStatus::equivalents(WorkflowStatus::DONE))
                ->count();

            $projectTotal = Project::count();
            $projectDone = Project::query()
                ->whereIn($projectStatusCol, WorkflowStatus::equivalents(WorkflowStatus::DONE))
                ->count();

            $stats = [
                'tickets' => [
                    'total' => $ticketTotal,
                    'done' => $ticketDone,
                    'open' => max(0, $ticketTotal - $ticketDone),
                ],
                'tasks' => [
                    'total' => $taskTotal,
                    'done' => $taskDone,
                ],
                'projects' => [
                    'total' => $projectTotal,
                    'done' => $projectDone,
                ],
            ];
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'unit' => $user->unit ?? null,
            ],
            'stats' => $stats,
        ];
    }

    /**
     * @param  array<int,array{role:string,text:string}>  $history
     * @return array<int,array{role:string,parts:array<int,array{text:string}>}>
     */
    private function buildHistoryPayload(array $history, string $latestMessage): array
    {
        $recentHistory = array_slice($history, -6);
        $lastIndex = array_key_last($recentHistory);
        $normalizedLatest = Str::of($latestMessage)->trim()->value();

        $turns = [];
        $previousRole = null;

        foreach ($recentHistory as $index => $entry) {
            if (! isset($entry['role'], $entry['text'])) {
                continue;
            }

            $role = $entry['role'] === 'assistant' ? 'model' : 'user';
            $originalText = Str::of((string) $entry['text'])->trim()->value();

            if ($originalText === '') {
                continue;
            }

            if ($index === $lastIndex && $role === 'user' && $originalText === $normalizedLatest) {
                continue;
            }

            if ($previousRole === $role) {
                continue;
            }

            $previousRole = $role;

            $turns[] = [
                'role' => $role,
                'text' => Str::limit($originalText, 2000),
            ];
        }

        while (! empty($turns) && $turns[0]['role'] === 'model') {
            array_shift($turns);
        }

        return array_map(static function (array $turn): array {
            return [
                'role' => $turn['role'],
                'parts' => [[
                    'text' => $turn['text'],
                ]],
            ];
        }, $turns);
    }

    private function pickStatusColumn(string $table): string
    {
        $candidates = ['status', 'state', 'status_id'];
        foreach ($candidates as $candidate) {
            if (Schema::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        try {
            foreach (Schema::getColumnListing($table) as $column) {
                $lc = Str::lower($column);
                if (Str::contains($lc, ['status', 'state'])) {
                    return $column;
                }
            }
        } catch (\Throwable $e) {
            // ignore, fallback below
        }

        return $candidates[0];
    }
}
