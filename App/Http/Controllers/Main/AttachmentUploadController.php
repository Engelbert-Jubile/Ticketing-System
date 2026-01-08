<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller as BaseController;
use App\Models\TmpUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AttachmentUploadController extends BaseController
{
    /**
     * FilePond process (POST /dashboard/attachments)
     * Return: {"id": "<uuid>"} — serverId used by FilePond
     */
    public function process(Request $request)
    {
        try {
            Log::info('File upload started');

            $allowedExtensions = $this->resolveAllowedExtensions($request->input('allowed_extensions'));
            $mimesRule = implode(',', $allowedExtensions);

            // Validasi khusus: balas 422 agar FilePond bisa menampilkan pesan yang jelas
            try {
                // max in KB (10240 = 10MB)
                $request->validate([
                    'file' => 'required|file|max:10240|mimes:'.$mimesRule,
                ]);
            } catch (ValidationException $ve) {
                Log::warning('Upload validation failed', ['errors' => $ve->errors()]);

                return response()->json([
                    'message' => 'Validasi gagal: tipe file tidak diizinkan atau ukuran terlalu besar',
                    'errors' => $ve->errors(),
                ], 422);
            }

            $file = $request->file('file');
            if (! $file || ! $file->isValid()) {
                Log::warning('Invalid file upload attempt');

                return response()->json(['message' => 'Invalid file upload'], 422);
            }

            $uuid = (string) Str::uuid();
            $ext = $file->getClientOriginalExtension();
            $folder = 'attachments_tmp';
            $name = $uuid.($ext ? '.'.$ext : '');

            $storage = Storage::disk('public');
            if (! $storage->exists($folder)) {
                $storage->makeDirectory($folder);
            }

            $path = $file->storeAs($folder, $name, 'public');
            if (! $path) {
                Log::error('Failed to store file');

                return response()->json(['message' => 'Failed to store file'], 500);
            }

            try {
                TmpUpload::create([
                    'id' => $uuid,
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id ?? auth()->id(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to create temp upload record', ['error' => $e->getMessage()]);
                // Boleh diabaikan; file tetap tersimpan
            }

            Log::info('File upload completed', ['id' => $uuid, 'name' => $file->getClientOriginalName()]);

            return response()->json(['id' => $uuid, 'name' => $file->getClientOriginalName()], 200);
        } catch (\Throwable $e) {
            Log::error('Error processing file upload', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * FilePond revert (DELETE /dashboard/attachments/revert)
     * Body = serverId (uuid), content-type text/plain
     * Return: 204
     */
    public function revert(Request $request): SymfonyResponse
    {
        try {
            $serverId = trim((string) $request->getContent());
            Log::info('File revert requested', ['serverId' => $serverId]);

            if ($serverId !== '') {
                // Hapus record DB jika ada
                try {
                    $tmp = TmpUpload::find($serverId);
                    if ($tmp) {
                        if (Storage::disk('public')->exists($tmp->path)) {
                            Storage::disk('public')->delete($tmp->path);
                            Log::info('Deleted temp file', ['path' => $tmp->path]);
                        }
                        $tmp->delete();
                        Log::info('Deleted temp upload record', ['id' => $serverId]);
                    } else {
                        // Tanpa DB record: cari file yang prefix-nya serverId.*
                        foreach (Storage::disk('public')->files('attachments_tmp') as $file) {
                            if (str_starts_with(basename($file), $serverId.'.')) {
                                Storage::disk('public')->delete($file);
                                Log::info('Deleted orphaned temp file', ['path' => $file]);
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error during revert cleanup', ['error' => $e->getMessage(), 'serverId' => $serverId]);
                }
            }

            return response('', 204);
        } catch (\Throwable $e) {
            Log::error('Error processing revert request', ['error' => $e->getMessage()]);

            return response('', 500);
        }
    }

    /**
     * FilePond load/restore (GET /dashboard/attachments/tmp/{id})
     * Return: file binary untuk preview
     */
    public function load(Request $request, string $id): SymfonyResponse
    {
        try {
            Log::info('File load requested', ['id' => $id]);

            $disk = 'public';
            $filePath = null;
            $mime = 'application/octet-stream';

            // Coba lewat DB dulu
            try {
                $tmp = TmpUpload::find($id);
                if ($tmp && is_string($tmp->path)) {
                    $filePath = Storage::disk($disk)->path($tmp->path);
                    $mime = $tmp->mime ?? $mime;
                } else {
                    // Tanpa DB: cari file yang prefix-nya {id}.*
                    foreach (Storage::disk($disk)->files('attachments_tmp') as $f) {
                        if (str_starts_with(basename($f), $id.'.')) {
                            $filePath = Storage::disk($disk)->path($f);
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error retrieving file information', ['error' => $e->getMessage(), 'id' => $id]);
                $filePath = null;
            }

            if (! $filePath || ! is_file($filePath)) {
                Log::warning('File not found', ['id' => $id]);

                return response('', 404);
            }

            return response()->file($filePath, ['Content-Type' => $mime]);
        } catch (\Throwable $e) {
            Log::error('Error processing load request', ['error' => $e->getMessage(), 'id' => $id]);

            return response('', 500);
        }
    }

    private function resolveAllowedExtensions(?string $raw): array
    {
        $whitelist = [
            'pdf',
            'jpg',
            'jpeg',
            'png',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'zip',
            'rar',
            'txt',
            'csv',
        ];

        $requested = array_filter(array_map(function ($ext) use ($whitelist) {
            $normalized = strtolower(trim((string) $ext));
            if ($normalized === '' || ! in_array($normalized, $whitelist, true)) {
                return null;
            }

            return $normalized;
        }, explode(',', (string) $raw)));

        if (empty($requested)) {
            return $whitelist;
        }

        return array_values(array_unique($requested));
    }
}
