<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('workflows', 'slug')) {
            Schema::table('workflows', fn (Blueprint $table) => $table->string('slug', 160)->nullable()->after('uuid'));
        }

        DB::table('workflows')->orderBy('id')->get(['id', 'name', 'code', 'slug'])->each(function ($workflow): void {
            if ($workflow->slug) {
                return;
            }
            $base = Str::slug($workflow->name) ?: Str::slug($workflow->code) ?: 'workflow';
            $slug = $base;
            $suffix = 2;
            while (DB::table('workflows')->where('slug', $slug)->where('id', '!=', $workflow->id)->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            DB::table('workflows')->where('id', $workflow->id)->update(['slug' => $slug]);
        });

        Schema::table('workflows', fn (Blueprint $table) => $table->unique('slug', 'workflows_slug_unique'));

        $replacements = ['â€”' => '—', 'â€“' => '–', 'â€˜' => '‘', 'â€™' => '’', 'â€œ' => '“', 'â€' => '”', 'Â' => '', 'ï¿½' => ''];
        foreach (['workflows' => ['name', 'description'], 'workflow_stages' => ['name', 'action_label', 'instructions']] as $table => $columns) {
            if (! Schema::hasTable($table)) continue;
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) continue;
                foreach ($replacements as $broken => $fixed) {
                    DB::table($table)->where($column, 'like', '%'.$broken.'%')->update([$column => DB::raw("REPLACE($column, ".DB::getPdo()->quote($broken).', '.DB::getPdo()->quote($fixed).')')]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workflows', 'slug')) {
            Schema::table('workflows', function (Blueprint $table): void {
                $table->dropUnique('workflows_slug_unique');
                $table->dropColumn('slug');
            });
        }
    }
};
