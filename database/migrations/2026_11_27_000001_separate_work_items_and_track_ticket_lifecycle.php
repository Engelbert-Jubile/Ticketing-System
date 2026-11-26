<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve existing standalone records and their direct ownership.
        foreach (['tasks' => ['ticket_id', 'project_id'], 'projects' => ['ticket_id']] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                foreach (Schema::getForeignKeys($table) as $foreign) {
                    if (in_array($column, $foreign['columns'], true)) {
                        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreign['name'] ?? [$column]));
                    }
                }
                foreach (Schema::getIndexes($table) as $index) {
                    if (in_array($column, $index['columns'], true)) {
                        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index['name']));
                    }
                }
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
            }
        }

        foreach (['worked_on_at', 'confirmation_at', 'completed_at'] as $column) {
            if (! Schema::hasColumn('tickets', $column)) {
                Schema::table('tickets', fn (Blueprint $table) => $table->timestamp($column)->nullable());
            }
        }
        $legacyColumns = array_values(array_filter(['type', 'sla'], fn (string $column) => Schema::hasColumn('tickets', $column)));
        if ($legacyColumns !== []) {
            Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn($legacyColumns));
        }
        if (! Schema::hasTable('ticket_confirmations')) {
            Schema::create('ticket_confirmations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
                $table->text('note');
                $table->timestamps();
            });
        }
        if (! Schema::hasColumn('attachments', 'ticket_confirmation_id')) {
            Schema::table('attachments', function (Blueprint $table): void {
                $table->foreignId('ticket_confirmation_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        DB::table('tickets')->whereIn('status', ['done', 'completed', 'closed'])->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
        DB::table('tickets')->where('status', 'confirmation')->whereNull('confirmation_at')
            ->update(['confirmation_at' => DB::raw('updated_at')]);
        if (Schema::hasTable('workflow_instance_histories')) {
            DB::table('workflow_instance_histories as history')
                ->join('workflow_instances as instance', 'instance.id', '=', 'history.workflow_instance_id')
                ->where('instance.subject_type', Ticket::class)
                ->where('history.event', 'status_changed')
                ->whereIn('history.to_status', ['in_progress', 'confirmation', 'done'])
                ->select(['history.id', 'instance.subject_id', 'history.to_status', 'history.created_at'])
                ->chunkById(250, function ($rows): void {
                    foreach ($rows as $row) {
                        $ticket = DB::table('tickets')->where('id', $row->subject_id);
                        match ($row->to_status) {
                            'in_progress' => $ticket->whereNull('worked_on_at')->update(['worked_on_at' => $row->created_at]),
                            'confirmation' => $ticket->where('status', 'confirmation')->update(['confirmation_at' => $row->created_at]),
                            'done' => $ticket->whereIn('status', ['done', 'completed'])->update(['completed_at' => $row->created_at]),
                        };
                    }
                }, 'history.id', 'id');
        }
    }

    public function down(): void
    {
        Schema::table('attachments', fn (Blueprint $table) => $table->dropConstrainedForeignId('ticket_confirmation_id'));
        Schema::dropIfExists('ticket_confirmations');
        Schema::table('tickets', fn (Blueprint $table) => $table->dropColumn(['worked_on_at', 'confirmation_at', 'completed_at']));
        Schema::table('tickets', function (Blueprint $table): void {
            $table->enum('type', ['task', 'project'])->default('task');
            $table->enum('sla', ['ontime', 'late'])->nullable();
        });
        Schema::table('tasks', fn (Blueprint $table) => $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('projects', fn (Blueprint $table) => $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete());
        $projectIdType = Schema::getColumnType('projects', 'id');
        Schema::table('tasks', function (Blueprint $table) use ($projectIdType): void {
            if (in_array($projectIdType, ['uuid', 'char', 'varchar', 'string'], true)) {
                $table->uuid('project_id')->nullable();
            } else {
                $table->unsignedBigInteger('project_id')->nullable();
            }
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }
};
