<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends BaseController
{
    private function canAccess(Attachment $attachment): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ((int) ($attachment->uploaded_by ?? 0) === (int) $user->id) {
            return true;
        }

        $attachable = $attachment->attachable;

        if ($attachable instanceof Ticket) {
            if ($attachable->isRequester($user) || $attachable->isAgent($user)) {
                return true;
            }
            try {
                return $attachable->assignedUsers()->where('users.id', $user->id)->exists();
            } catch (\Throwable) {
                return false;
            }
        }

        if ($attachable instanceof Task || $attachable instanceof Project) {
            $ticketId = null;
            try {
                $ticketId = $attachable->ticket_id ?? null;
            } catch (\Throwable) {
            }
            if ($ticketId) {
                $t = Ticket::find($ticketId);
                if ($t) {
                    if ($t->isRequester($user) || $t->isAgent($user)) {
                        return true;
                    }
                    try {
                        return $t->assignedUsers()->where('users.id', $user->id)->exists();
                    } catch (\Throwable) {
                    }
                }
            }
        }

        return false;
    }

    // GET /dashboard/attachments/{attachment}/view
    public function view(Attachment $attachment)
    {
        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        $disk = 'public';
        $path = $attachment->path;

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        try {
            $fullPath = Storage::disk($disk)->path($path);
        } catch (\Throwable) {
            abort(404);
        }

        $mime = $attachment->mime ?: 'application/octet-stream';

        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    // GET /dashboard/attachments/{attachment}/download
    public function download(Attachment $attachment)
    {
        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        return Storage::disk('public')->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    // DELETE /dashboard/attachments/{attachment}
    public function destroy(Request $request, Attachment $attachment)
    {
        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        if (Storage::disk('public')->exists($attachment->path)) {
            Storage::disk('public')->delete($attachment->path);
        }

        $attachment->delete();

        return back()->with('status', 'Lampiran dihapus.');
    }
}
