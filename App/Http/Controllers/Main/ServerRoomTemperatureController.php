<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use App\Models\ServerRoomTemperatureCheck;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ServerRoomTemperatureController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [], __('server_room.attributes'));

        $now = CarbonImmutable::now();
        $from = $filters['from'] ?? $now->startOfMonth()->toDateString();
        $to = $filters['to'] ?? $now->endOfMonth()->toDateString();
        $reportUrl = routeLocale('server-room.index', absolute: false);
        $visibleChecks = ServerRoomTemperatureCheck::visibleTo($request->user());
        $periodChecks = (clone $visibleChecks)
            ->whereBetween('checked_at', [$from.' 00:00:00', $to.' 23:59:59']);
        $orderedChecks = (clone $periodChecks)->latest('checked_at')->latest('id');
        $latest = (clone $orderedChecks)->first(['room', 'temperature_c']);
        $checks = $orderedChecks->paginate(15)->withPath($reportUrl)->appends(['from' => $from, 'to' => $to])
            ->through(fn (ServerRoomTemperatureCheck $check) => [
                'id' => $check->id,
                'date' => $check->checked_at->toDateString(),
                'time' => $check->checked_at->format('H:i'),
                'room' => $check->room,
                'unit' => $check->unit,
                'temperature' => $check->temperature_c,
                'needsFollowUp' => $check->needs_follow_up,
                'inspector' => $check->inspector,
                'notes' => $check->notes,
                'evidenceUrl' => routeLocale('server-room.evidence', ['id' => $check->id], false),
            ]);

        return Inertia::render('ServerRoomTemperature', [
            'checks' => $checks,
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'today' => $visibleChecks->whereBetween('checked_at', [$now->startOfDay(), $now->endOfDay()])->count(),
                'latest' => $latest,
                'followUp' => $periodChecks->where('needs_follow_up', true)->count(),
            ],
            'defaults' => [
                'date' => $now->toDateString(),
                'time' => $now->format('H:i'),
                'timezone' => $now->getTimezone()->getName(),
            ],
            'urls' => [
                'index' => $reportUrl,
                'store' => routeLocale('server-room.store', absolute: false),
            ],
            'labels' => __('server_room.report'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01'],
            'time' => ['required', 'date_format:H:i'],
            'room' => ['required', 'string', 'max:120'],
            'temperature' => ['required', 'numeric', 'decimal:0,1', 'between:-99.9,99.9'],
            'condition' => ['required', 'in:normal,follow_up'],
            'notes' => ['required_if:condition,follow_up', 'nullable', 'string', 'max:1000'],
            'evidence' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
        ], [], __('server_room.attributes'));

        $checkedAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['date'].' '.$data['time']);
        if ($checkedAt->isFuture()) {
            throw ValidationException::withMessages(['time' => __('server_room.future_check')]);
        }

        $path = $request->file('evidence')->store('server-room-evidence', 'local');
        if ($path === false) {
            throw ValidationException::withMessages(['evidence' => __('server_room.upload_failed')]);
        }

        $user = $request->user();
        try {
            ServerRoomTemperatureCheck::create([
                'recorded_by' => $user->id,
                'inspector' => $user->display_name,
                'unit' => trim((string) $user->unit) ?: null,
                'room' => $data['room'],
                'checked_at' => $checkedAt,
                'temperature_c' => $data['temperature'],
                'needs_follow_up' => $data['condition'] === 'follow_up',
                'notes' => $data['notes'] ?? null,
                'evidence_path' => $path,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return redirect()->to(routeLocale('server-room.index', [
            'from' => $checkedAt->startOfMonth()->toDateString(),
            'to' => $checkedAt->endOfMonth()->toDateString(),
        ], false))->with('success', __('server_room.saved'));
    }

    public function evidence(Request $request): BinaryFileResponse
    {
        $check = ServerRoomTemperatureCheck::visibleTo($request->user())
            ->findOrFail($request->route('id'));
        $disk = Storage::disk('local');
        abort_unless($disk->exists($check->evidence_path), 404);

        return new BinaryFileResponse($disk->path($check->evidence_path), headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], public: false);
    }
}
