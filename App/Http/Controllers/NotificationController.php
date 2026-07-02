<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

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

        $query = DatabaseNotification::query()
            ->where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->getKey());

        $notifications = (clone $query)->latest()->limit(15)->get();

        return [
            'unread_count' => (clone $query)->whereNull('read_at')->count(),
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

    protected function userNotificationQuery(Request $request, string $id)
    {
        $user = $request->user();

        return DatabaseNotification::query()
            ->where('id', $id)
            ->where('notifiable_type', $user?->getMorphClass() ?? $user::class)
            ->where('notifiable_id', $user?->getKey());
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notif = $this->userNotificationQuery($request, $id)->first();

        if ($notif) {
            $this->userNotificationQuery($request, $id)->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
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
        $this->userNotificationQuery($request, $id)->update([
            'read_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->notificationResponse($request);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user) {
            DB::table('notifications')
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $this->notificationResponse($request);
    }

    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $this->userNotificationQuery($request, $id)->delete();

        return $this->notificationResponse($request);
    }
}
