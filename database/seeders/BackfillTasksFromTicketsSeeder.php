<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillTasksFromTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $tickets = DB::table('tickets')
            ->where('type', 'task')
            ->get();

        foreach ($tickets as $t) {
            $exists = DB::table('tasks')->where('ticket_id', $t->id)->exists();
            if ($exists) {
                continue;
            }

            // map status ticket -> task
            $status = match ($t->status) {
                'new' => 'pending',
                'on_progress' => 'in_progress',
                'done' => 'completed',
                default => 'pending',
            };

            $payload = [
                'ticket_id' => $t->id,
                'title' => $t->title,
                'status' => $status,
            ];

            if (Schema::hasColumn('tasks', 'start_date')) {
                $payload['start_date'] = $t->due_date;
            }
            if (Schema::hasColumn('tasks', 'end_date')) {
                $payload['end_date'] = $t->finish_date;
            }
            if (Schema::hasColumn('tasks', 'created_by')) {
                $payload['created_by'] = $t->requester_id ?? $t->agent_id ?? null;
            }

            DB::table('tasks')->insert($payload);
        }
    }
}
