<?php

namespace App\Console\Commands;

use App\Models\TmpUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTmpUploads extends Command
{
    protected $signature = 'attachments:cleanup-tmp {--hours=24 : Delete temp uploads older than N hours}';

    protected $description = 'Delete orphaned temporary attachment files and stale DB records.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours(max($hours, 1));

        $disk = Storage::disk('public');
        $folder = 'attachments_tmp';

        $deletedFiles = 0;
        $deletedRecords = 0;

        // 1) Delete stale DB records and their files
        TmpUpload::where('created_at', '<', $cutoff)->chunkById(200, function ($chunk) use ($disk, &$deletedFiles, &$deletedRecords) {
            foreach ($chunk as $tmp) {
                try {
                    if (is_string($tmp->path) && $disk->exists($tmp->path)) {
                        $disk->delete($tmp->path);
                        $deletedFiles++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('CleanupTmpUploads: failed deleting file', ['path' => $tmp->path, 'error' => $e->getMessage()]);
                }
                try {
                    $tmp->delete();
                    $deletedRecords++;
                } catch (\Throwable $e) {
                    Log::warning('CleanupTmpUploads: failed deleting record', ['id' => $tmp->id, 'error' => $e->getMessage()]);
                }
            }
        });

        // 2) Delete orphaned files in storage without DB record
        try {
            foreach ($disk->files($folder) as $path) {
                // Extract basename (uuid.ext)
                $basename = basename($path);
                $uuid = pathinfo($basename, PATHINFO_FILENAME);
                if (! TmpUpload::find($uuid)) {
                    $disk->delete($path);
                    $deletedFiles++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('CleanupTmpUploads: failed listing folder', ['folder' => $folder, 'error' => $e->getMessage()]);
        }

        $this->info("Cleanup complete. Files: {$deletedFiles}, Records: {$deletedRecords}");

        return Command::SUCCESS;
    }
}
