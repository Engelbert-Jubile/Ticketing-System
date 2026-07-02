<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /** Tandai satu notifikasi sebagai dibaca lalu redirect ke URL tujuannya (bila ada). */
    public function read(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notif = $user?->notifications()->where('id', $id)->first();

        if ($notif) {
            DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);
        }

        $url = $notif?->data['url'] ?? null;
        if ($url && is_string($url)) {
            return redirect()->to($url);
        }

        return back();
    }

    /** Tandai dibaca lalu kembali (tanpa redirect ke URL). */
    public function mark(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $notif = $user?->notifications()->where('id', $id)->first();

        if ($notif) {
            DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);
        }

        return $this->notificationResponse($request);
    }

    /** Tandai semua sebagai dibaca. */
    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()?->unreadNotifications()->update(['read_at' => now()]);

        return $this->notificationResponse($request);
    }

    /** Hapus satu notifikasi milik user. */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $request->user()?->notifications()->where('id', $id)->delete();

        return $this->notificationResponse($request);
    }
}

