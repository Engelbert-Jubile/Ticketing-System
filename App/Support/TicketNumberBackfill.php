<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class TicketNumberBackfill
{
    /**
     * Backfill tickets.ticket_no yang NULL / kosong dengan format: TCK-YYYYMMDD-0001.
     *
     * Tidak memakai Eloquent agar tidak memicu event/observer.
     *
     * @return array{processed:int,updated:int,skipped:int}
     */
    public static function backfillMissing(): array
    {
        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $nextSeqByDate = [];

        $missing = DB::table('tickets')
            ->select(['id', 'created_at'])
            ->where(function ($q) {
                $q->whereNull('ticket_no')->orWhere('ticket_no', '=', '');
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($missing as $row) {
            $processed++;

            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                $skipped++;
                continue;
            }

            $date = null;
            try {
                $date = Carbon::parse($row->created_at)->format('Ymd');
            } catch (\Throwable) {
                $date = now()->format('Ymd');
            }

            $prefix = 'TCK-'.$date.'-';

            if (! isset($nextSeqByDate[$date])) {
                $maxExisting = DB::table('tickets')
                    ->whereNotNull('ticket_no')
                    ->where('ticket_no', 'like', $prefix.'%')
                    ->orderByDesc('ticket_no')
                    ->value('ticket_no');

                $nextSeqByDate[$date] = 1;
                if (is_string($maxExisting) && preg_match('/-(\d{4})$/', $maxExisting, $m)) {
                    $nextSeqByDate[$date] = ((int) $m[1]) + 1;
                }
            }

            $seq = (int) $nextSeqByDate[$date];

            while (true) {
                $ticketNo = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $exists = DB::table('tickets')->where('ticket_no', $ticketNo)->exists();
                if (! $exists) {
                    break;
                }
                $seq++;
            }

            $affected = DB::table('tickets')
                ->where('id', $id)
                ->where(function ($q) {
                    $q->whereNull('ticket_no')->orWhere('ticket_no', '=', '');
                })
                ->update(['ticket_no' => $ticketNo]);

            if ($affected > 0) {
                $updated++;
                $nextSeqByDate[$date] = $seq + 1;
            } else {
                $skipped++;
            }
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    public static function missingCount(): int
    {
        return (int) DB::table('tickets')
            ->where(function ($q) {
                $q->whereNull('ticket_no')->orWhere('ticket_no', '=', '');
            })
            ->count();
    }
}

