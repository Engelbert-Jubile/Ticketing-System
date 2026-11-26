<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TmpUpload;
use App\Models\User;
use App\Support\WorkflowStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketLifecycle
{
    public function changeStatus(Ticket $ticket, User $actor, string $status, array $input = [], bool $rejecting = false): void
    {
        if ($ticket->status === $status) {
            return;
        }

        if ($ticket->status === WorkflowStatus::DONE) {
            throw ValidationException::withMessages(['status' => __('Tiket sudah selesai.')]);
        }

        if (! $rejecting && ! $ticket->canUserSetStatus($actor, $status)) {
            throw ValidationException::withMessages(['status' => __('Anda tidak memiliki izin untuk mengubah status tiket ini.')]);
        }

        if ($status === WorkflowStatus::CONFIRMATION) {
            $this->recordConfirmation($ticket, $actor, $input);
        }

        $ticket->status = $status;
    }

    public function canEditDeadline(Ticket $ticket, User $actor): bool
    {
        return $ticket->isRequester($actor)
            && ! in_array($ticket->status, [WorkflowStatus::DONE, WorkflowStatus::CANCELLED], true)
            && $ticket->created_at && now()->lte($ticket->created_at->copy()->addHours(48))
            && (! $ticket->deadline() || now()->lt($ticket->deadline()));
    }

    public function changeDeadline(Ticket $ticket, User $actor, Carbon $deadline): void
    {
        if ($ticket->deadline()?->eq($deadline)) {
            return;
        }
        if (! $this->canEditDeadline($ticket, $actor)) {
            throw ValidationException::withMessages(['due_at' => __('Deadline hanya dapat diubah oleh requester dalam 48 jam sejak tiket dibuat dan sebelum tenggat.')]);
        }
        if ($deadline->lt(now())) {
            throw ValidationException::withMessages(['due_at' => __('Deadline harus berada di masa mendatang.')]);
        }

        $ticket->due_at = $deadline;
        $ticket->due_date = $deadline->toDateString();
    }

    public function closeExpired(): int
    {
        $now = now();
        $closed = 0;
        Ticket::query()->whereNotIn('status', ['done', 'completed', 'complete', 'closed', 'cancelled', 'cancel', 'canc'])
            ->where(fn ($query) => $query
                ->where('confirmation_at', '<=', $now->copy()->subHours(24))
                ->orWhere('due_at', '<=', $now)
                ->orWhere('due_date', '<=', $now->toDateString()))
            ->chunkById(200, function ($tickets) use ($now, &$closed): void {
                foreach ($tickets as $candidate) {
                    $closed += DB::transaction(function () use ($candidate, $now): int {
                        $ticket = Ticket::lockForUpdate()->find($candidate->id);
                        if (! $ticket || in_array($ticket->status, [WorkflowStatus::DONE, WorkflowStatus::CANCELLED], true)) {
                            return 0;
                        }
                        $closeAt = $ticket->status === WorkflowStatus::CONFIRMATION
                            ? $ticket->confirmation_at?->copy()->addHours(24)
                            : $ticket->deadline()?->endOfDay()->setMicrosecond(0);
                        if (! $closeAt || $closeAt->gt($now)) {
                            return 0;
                        }

                        $ticket->completed_at = $closeAt;
                        $ticket->status = WorkflowStatus::DONE;
                        $ticket->save();

                        return 1;
                    });
                }
            });

        return $closed;
    }

    private function recordConfirmation(Ticket $ticket, User $actor, array $input): void
    {
        $note = trim($input['confirmation_note'] ?? '');
        $ids = array_values(array_unique($input['evidence'] ?? []));
        $errors = [];
        if ($note === '') {
            $errors['confirmation_note'] = __('Keterangan wajib diisi.');
        }
        if ($ids === []) {
            $errors['evidence'] = __('Lampiran bukti wajib diunggah.');
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $uploads = TmpUpload::query()->whereIn('id', $ids)
            ->where('uploaded_by', $actor->id)->lockForUpdate()->get();
        $disk = Storage::disk('public');
        if ($uploads->count() !== count($ids) || $uploads->contains(fn ($upload) => ! $disk->exists($upload->path))) {
            throw ValidationException::withMessages(['evidence' => __('Unggah ulang lampiran bukti yang tidak tersedia.')]);
        }

        $confirmation = $ticket->confirmations()->create(['note' => $note]);
        foreach ($uploads as $upload) {
            // Retain the temporary upload until the transaction commits.
            $extension = pathinfo($upload->path, PATHINFO_EXTENSION);
            $path = 'attachments/'.now()->format('Y/m').'/'.Str::uuid().($extension ? '.'.$extension : '');
            if (! $disk->copy($upload->path, $path)) {
                throw ValidationException::withMessages(['evidence' => __('Lampiran bukti gagal disimpan.')]);
            }
            DB::afterRollBack(fn () => $disk->delete($path));
            $attachment = $ticket->attachments()->make($upload->only(['original_name', 'mime', 'size', 'uploaded_by']));
            $attachment->path = $path;
            $attachment->ticket_confirmation_id = $confirmation->id;
            $attachment->save();
            $upload->delete();
            DB::afterCommit(fn () => $disk->delete($upload->path));
        }
    }
}
