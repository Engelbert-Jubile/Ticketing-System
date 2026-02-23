<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends BaseController
{
    private function normalizePath(string $path): string
    {
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        return $path;
    }

    private function resolveAttachmentFile(Attachment $attachment): ?string
    {
        $path = $this->normalizePath((string) $attachment->path);
        $disk = $attachment->disk ?? 'public';

        try {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->path($path);
            }
        } catch (\Throwable) {
        }

        $candidates = [
            public_path($path),
            public_path('storage/'.$path),
            storage_path('app/'.$path),
            storage_path('app/public/'.$path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

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
    public function view($attachment)
    {
        $attachment = \App\Models\Attachment::findOrFail($attachment);

        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        $fullPath = $this->resolveAttachmentFile($attachment);
        if (! $fullPath) {
            abort(404);
        }

        $mime = $attachment->mime ?: 'application/octet-stream';

        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    // GET /dashboard/attachments/{attachment}/download
    public function download($attachment)
    {
        $attachment = \App\Models\Attachment::findOrFail($attachment);

        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        $fullPath = $this->resolveAttachmentFile($attachment);
        if (! $fullPath) {
            abort(404);
        }

        return response()->download($fullPath, $attachment->original_name);
    }

    // DELETE /dashboard/attachments/{attachment}
    public function destroy(Request $request, $attachment)
    {
        $attachment = \App\Models\Attachment::findOrFail($attachment);

        if (! $this->canAccess($attachment)) {
            abort(403);
        }

        $disk = $attachment->disk ?? 'public';
        $path = $this->normalizePath((string) $attachment->path);

        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable) {
        }

        $legacyCandidates = [
            public_path($path),
            public_path('storage/'.$path),
            storage_path('app/'.$path),
            storage_path('app/public/'.$path),
        ];

        foreach ($legacyCandidates as $candidate) {
            if (is_file($candidate)) {
                File::delete($candidate);
            }
        }

        $attachment->delete();

        return back()->with('status', 'Lampiran dihapus.');
    }
}
