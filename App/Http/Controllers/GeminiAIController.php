<?php

namespace App\Http\Controllers;

use App\Services\GeminiAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GeminiAIController extends Controller
{
    public function __construct(private GeminiAIService $service) {}

    public function respond(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:600'],
            'history' => ['sometimes', 'array'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.text' => ['required_with:history', 'string', 'max:1000'],
        ]);

        $message = (string) $validated['message'];

        if ($this->containsSensitiveRequest($message)) {
            return response()->json([
                'reply' => 'Maaf, saya tidak dapat membantu permintaan tersebut. Silakan hubungi admin bila membutuhkan bantuan resmi.',
            ]);
        }

        try {
            $reply = $this->service->respond(
                $request->user(),
                $message,
                $validated['history'] ?? []
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'reply' => 'Maaf, Gemini sedang tidak dapat merespons. Coba beberapa saat lagi.',
            ], 503);
        }

        return response()->json([
            'reply' => $reply,
        ]);
    }

    private function containsSensitiveRequest(string $message): bool
    {
        $lower = Str::lower($message);
        $keywords = ['password', 'kata sandi', 'sandi', 'api key', 'api-key', 'secret', 'token', 'credential', 'otp'];

        foreach ($keywords as $keyword) {
            if (Str::contains($lower, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
