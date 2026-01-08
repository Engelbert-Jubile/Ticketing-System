<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'project_actions' => ['foreign' => 'project_actions_status_id_foreign', 'onDelete' => 'restrict'],
            'project_subactions' => ['foreign' => 'project_subactions_status_id_foreign', 'onDelete' => 'restrict'],
            'project_risk_analyses' => ['foreign' => 'project_risk_analyses_status_id_foreign', 'onDelete' => 'restrict'],
            'project_deliverables' => ['foreign' => 'project_deliverables_status_id_foreign', 'onDelete' => 'cascade'],
        ];

        foreach ($tables as $name => $config) {
            if (! Schema::hasTable($name) || ! Schema::hasColumn($name, 'status_id')) {
                continue;
            }

            Schema::table($name, function (Blueprint $table) use ($config) {
                $table->dropForeign($config['foreign']);
                $table->string('status_id', 32)->change();
                $foreign = $table->foreign('status_id')->references('id')->on('statuses');
                if ($config['onDelete'] === 'cascade') {
                    $foreign->onDelete('cascade');
                } else {
                    $foreign->onDelete('restrict');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'project_actions' => ['foreign' => 'project_actions_status_id_foreign', 'onDelete' => 'restrict'],
            'project_subactions' => ['foreign' => 'project_subactions_status_id_foreign', 'onDelete' => 'restrict'],
            'project_risk_analyses' => ['foreign' => 'project_risk_analyses_status_id_foreign', 'onDelete' => 'restrict'],
            'project_deliverables' => ['foreign' => 'project_deliverables_status_id_foreign', 'onDelete' => 'cascade'],
        ];

        foreach ($tables as $name => $config) {
            if (! Schema::hasTable($name) || ! Schema::hasColumn($name, 'status_id')) {
                continue;
            }

            Schema::table($name, function (Blueprint $table) use ($config) {
                $table->dropForeign($config['foreign']);
                $table->string('status_id', 4)->change();
                $foreign = $table->foreign('status_id')->references('id')->on('statuses');
                if ($config['onDelete'] === 'cascade') {
                    $foreign->onDelete('cascade');
                } else {
                    $foreign->onDelete('restrict');
                }
            });
        }
    }
};
