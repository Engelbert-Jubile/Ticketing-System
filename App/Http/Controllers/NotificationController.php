<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Tandai satu notifikasi sebagai dibaca lalu redirect ke URL tujuannya (bila ada). */
    public function read(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notif = $user->notifications()->where('id', $id)->firstOrFail();
        if (! $notif->read_at) {
            $notif->markAsRead();
        }

        $url = $notif->data['url'] ?? null;
        if ($url && is_string($url)) {
            return redirect()->to($url);
        }

        return back();
    }

    /** Tandai dibaca lalu kembali (tanpa redirect ke URL). */
    public function mark(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $notif = $user->notifications()->where('id', $id)->firstOrFail();
        if (! $notif->read_at) {
            $notif->markAsRead();
        }

        return $this->notificationResponse($request);
    }

    /** Tandai semua sebagai dibaca. */
    public function readAll(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->notificationResponse($request);
    }

    /** Hapus satu notifikasi milik user. */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->where('id', $id)->delete();

        return $this->notificationResponse($request);
    }

    private function notificationResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}