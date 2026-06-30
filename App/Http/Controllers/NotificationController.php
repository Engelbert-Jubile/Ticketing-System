<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected function notificationResponse(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    /** Tandai satu notifikasi sebagai dibaca lalu redirect ke URL tujuannya (bila ada). */
    public function read(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notif = $user?->notifications()->where('id', $id)->first();

        if ($notif && ! $notif->read_at) {
            $notif->forceFill(['read_at' => now()])->save();
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

        if ($notif && ! $notif->read_at) {
            $notif->forceFill(['read_at' => now()])->save();
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
