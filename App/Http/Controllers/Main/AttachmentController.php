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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends BaseController
{
    private function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);

        if (preg_match('#^https?://#i', $path)) {
            $parsed = parse_url($path, PHP_URL_PATH);
            if (is_string($parsed) && $parsed !== '') {
                $path = $parsed;
            }
        }

        $path = ltrim($path, '/');

        $prefixes = [
            'storage/app/public/',
            'public/storage/',
            'storage/public/',
            'storage/',
            'public/',
            'app/public/',
            'app/',
        ];

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $path = substr($path, strlen($prefix));
                    $changed = true;
                }
            }
        }

        return ltrim($path, '/');
    }

    private function resolveAttachmentFile(Attachment $attachment): ?string
    {
        $rawPath = trim((string) $attachment->path);
        $rawPath = ltrim(str_replace('\\', '/', $rawPath), '/');
        $normalizedPath = $this->normalizePath((string) $attachment->path);
        $disk = $attachment->disk ?? 'public';

        $relativeCandidates = array_values(array_unique(array_filter([
            $normalizedPath,
            $rawPath,
        ], fn ($item) => is_string($item) && $item !== '')));

        foreach ($relativeCandidates as $relativePath) {
            $diskPaths = array_values(array_unique(array_filter([
                $relativePath,
                preg_replace('#^public/storage/#', '', $relativePath),
                preg_replace('#^storage/app/public/#', '', $relativePath),
                preg_replace('#^storage/#', '', $relativePath),
                preg_replace('#^public/#', '', $relativePath),
            ], fn ($item) => is_string($item) && $item !== '')));

            foreach ($diskPaths as $diskPath) {
                try {
                    if (Storage::disk($disk)->exists($diskPath)) {
                        return Storage::disk($disk)->path($diskPath);
                    }
                } catch (\Throwable) {
                }
            }
        }

        foreach ($relativeCandidates as $relativePath) {
            $candidates = [
                public_path($relativePath),
                public_path('storage/'.$relativePath),
                storage_path('app/'.$relativePath),
                storage_path('app/public/'.$relativePath),
            ];

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
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
            Log::warning('attachments.view.file_not_found', [
                'attachment_id' => $attachment->id,
                'db_path' => $attachment->path,
                'normalized_path' => $this->normalizePath((string) $attachment->path),
                'disk' => $attachment->disk ?? 'public',
            ]);
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
            Log::warning('attachments.download.file_not_found', [
                'attachment_id' => $attachment->id,
                'db_path' => $attachment->path,
                'normalized_path' => $this->normalizePath((string) $attachment->path),
                'disk' => $attachment->disk ?? 'public',
            ]);
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
        $rawPath = trim((string) $attachment->path);
        $rawPath = ltrim(str_replace('\\', '/', $rawPath), '/');

        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (\Throwable) {
        }

        $relativeCandidates = array_values(array_unique(array_filter([
            $path,
            $rawPath,
        ], fn ($item) => is_string($item) && $item !== '')));

        foreach ($relativeCandidates as $relativePath) {
            $legacyCandidates = [
                public_path($relativePath),
                public_path('storage/'.$relativePath),
                storage_path('app/'.$relativePath),
                storage_path('app/public/'.$relativePath),
            ];

            foreach ($legacyCandidates as $candidate) {
                if (is_file($candidate)) {
                    File::delete($candidate);
                }
            }
        }

        $attachment->delete();

        return back()->with('status', 'Lampiran dihapus.');
    }
}
