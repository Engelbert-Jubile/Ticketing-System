<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected function mapNotifications($user): array
    {
        if (! $user) {
            return [
                'unread_count' => 0,
                'items' => [],
            ];
        }

        $notifications = $user->notifications()->latest()->limit(15)->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'icon' => $notification->data['icon'] ?? 'notifications',
                    'title' => $notification->data['title'] ?? 'Activity',
                    'message' => $notification->data['message'] ?? null,
                    'url' => $notification->data['url'] ?? null,
                    'is_unread' => is_null($notification->read_at),
                    'read_at' => $notification->read_at ? $notification->read_at->toDateTimeString() : null,
                    'time_ago' => optional($notification->created_at)?->diffForHumans(),
                ];
            })->values()->all(),
        ];
    }

    protected function notificationResponse(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'notifications' => $this->mapNotifications($request->user()),
            ]);
        }

        return back();
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notif = $user?->notifications()->where('id', $id)->first();

        if ($notif) {
            $notif->markAsRead();
            $notif->refresh();
        }

        $url = $notif?->data['url'] ?? null;
        if ($url && is_string($url)) {
            return redirect()->to($url);
        }

        return back();
    }

    public function mark(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $notif = $user?->notifications()->where('id', $id)->first();

        if ($notif) {
            $notif->markAsRead();
            $notif->refresh();
        }

        return $this->notificationResponse($request);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return $this->notificationResponse($request);
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->user()?->notifications()->where('id', $id)->delete();

        return $this->notificationResponse($request);
    }
}
