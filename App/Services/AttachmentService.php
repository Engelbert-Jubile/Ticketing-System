<?php

namespace App\Services;

use App\Models\TmpUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * @param  array|string|null  $serverIds  // dari request('attachments')
     * @param  Model  $attachable  // Task atau Project
     */
    public function adoptFromServerIds(array|string|null $serverIds, Model $attachable): void
    {
        $ids = is_array($serverIds) ? $serverIds : (empty($serverIds) ? [] : [$serverIds]);
        try {
            Log::info('attachments.adopt.start', [
                'ids' => $ids,
                'attachable' => get_class($attachable),
                'attachable_id' => method_exists($attachable, 'getKey') ? $attachable->getKey() : null,
            ]);
        } catch (\Throwable) {
        }

        foreach ($ids as $rawId) {
            // serverId bisa berupa UUID murni atau nama file dengan ekstensi
            $id = is_string($rawId) ? trim($rawId) : (string) $rawId;
            if ($id === '') {
                continue;
            }
            // Jika client mengirim nama file lengkap (mis. uuid.ext), ambil bagian UUID saja
            if (str_contains($id, '.')) {
                $id = explode('.', $id)[0];
            }
            $tmp = TmpUpload::find($id);
            if (! $tmp) {
                try {
                    Log::warning('attachments.adopt.tmp_not_found', ['id' => $id]);
                } catch (\Throwable) {
                }

                continue;
            }

            $ext = pathinfo((string) $tmp->path, PATHINFO_EXTENSION);
            $newPath = 'attachments/'.date('Y').'/'.date('m').'/'.Str::uuid().($ext ? ('.'.$ext) : '');

            // ensure destination directory exists
            try {
                $dir = dirname($newPath);
                if (! Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }
            } catch (\Throwable) {
            }

            // pindahkan file temp → permanen
            if (Storage::disk('public')->exists($tmp->path)) {
                try {
                    Log::info('attachments.adopt.moving', ['from' => $tmp->path, 'to' => $newPath]);
                    Storage::disk('public')->move($tmp->path, $newPath);
                    Log::info('attachments.adopt.moved', ['from' => $tmp->path, 'to' => $newPath]);
                } catch (\Throwable $e) {
                    try {
                        Log::error('attachments.adopt.move_failed', ['from' => $tmp->path, 'to' => $newPath, 'err' => $e->getMessage()]);
                    } catch (\Throwable) {
                    }
                }
            } else {
                $tmp->delete();
                try {
                    Log::warning('attachments.adopt.missing_tmp_file', ['path' => $tmp->path]);
                } catch (\Throwable) {
                }

                continue;
            }

            // buat record lampiran
            $attachable->attachments()->create([
                'original_name' => $tmp->original_name,
                'path' => $newPath,
                'mime' => $tmp->mime,
                'size' => $tmp->size,
                'uploaded_by' => $tmp->uploaded_by,
            ]);
            try {
                Log::info('attachments.adopt.created', ['path' => $newPath]);
            } catch (\Throwable) {
            }

            // bersihkan tmp
            $tmp->delete();
        }
    }
}
