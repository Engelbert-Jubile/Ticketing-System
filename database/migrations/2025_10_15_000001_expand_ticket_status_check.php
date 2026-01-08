<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        $this->dropExistingConstraint();

        DB::statement(<<<'SQL'
            ALTER TABLE tickets
            ADD CONSTRAINT chk_tickets_status
            CHECK (status IN (
                'new',
                'in_progress',
                'on_progress',
                'confirmation',
                'revision',
                'done'
            ))
        SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        $this->dropExistingConstraint();

        DB::statement(<<<'SQL'
            ALTER TABLE tickets
            ADD CONSTRAINT chk_tickets_status
            CHECK (status IN ('new', 'on_progress', 'done'))
        SQL);
    }

    private function dropExistingConstraint(): void
    {
        try {
            DB::statement('ALTER TABLE tickets DROP CHECK chk_tickets_status');
        } catch (\Throwable $e) {
            // MySQL < 8.0.16 uses DROP CONSTRAINT syntax
            try {
                DB::statement('ALTER TABLE tickets DROP CONSTRAINT chk_tickets_status');
            } catch (\Throwable) {
                // swallow if constraint is already absent
            }
        }
    }
};
