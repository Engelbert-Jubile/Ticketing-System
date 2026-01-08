<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureProjectsSchema();
        $this->createProjectPicsTable();
        $this->createProjectActionsTable();
        $this->createProjectSubactionsTable();
        $this->createProjectCostsTable();
        $this->createProjectRiskAnalysisTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_risk_analyses');
        Schema::dropIfExists('project_costs');
        Schema::dropIfExists('project_subactions');
        Schema::dropIfExists('project_actions');
        Schema::dropIfExists('project_pics');
    }

    private function ensureProjectsSchema(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'status_id')) {
                $table->string('status_id', 4)->nullable()->after('project_no');
                $table->index('status_id');
            }

            if (! Schema::hasColumn('projects', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('projects', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('end_date');
            }

            if (! Schema::hasColumn('projects', 'project_no')) {
                $table->string('project_no', 20)->nullable()->after('ticket_id');
            }

            if (! Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (! Schema::hasColumn('projects', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });

        // ensure indexes / FK
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'project_no') && ! $this->indexExists('projects', 'projects_project_no_unique')) {
                $table->unique('project_no', 'projects_project_no_unique');
            }

            if (Schema::hasColumn('projects', 'created_by')) {
                $table->index('created_by', 'projects_created_by_idx');
            }
        });
    }

    private function createProjectPicsTable(): void
    {
        if (Schema::hasTable('project_pics')) {
            return;
        }

        $projectColumnType = $this->resolveProjectKeyType();

        Schema::create('project_pics', function (Blueprint $table) use ($projectColumnType) {
            $table->uuid('id')->primary();
            if ($projectColumnType === 'uuid') {
                $table->uuid('project_id');
            } else {
                $table->unsignedBigInteger('project_id');
            }
            $table->unsignedBigInteger('user_id');
            $table->string('position', 30);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('project_id');
            $table->index('user_id');

            $this->addProjectForeignKey($table, 'project_id');
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    private function createProjectActionsTable(): void
    {
        if (Schema::hasTable('project_actions')) {
            return;
        }

        $projectColumnType = $this->resolveProjectKeyType();

        Schema::create('project_actions', function (Blueprint $table) use ($projectColumnType) {
            $table->uuid('id')->primary();
            if ($projectColumnType === 'uuid') {
                $table->uuid('project_id');
            } else {
                $table->unsignedBigInteger('project_id');
            }
            $table->string('title', 60);
            $table->text('description')->nullable();
            $table->string('status_id', 4);
            $table->unsignedInteger('progress')->default(0);
            $table->uuid('pic_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('project_id');
            $table->index('status_id');
            $table->index('pic_id');

            $this->addProjectForeignKey($table, 'project_id');
            if (Schema::hasTable('statuses')) {
                $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            }
            if (Schema::hasTable('project_pics')) {
                $table->foreign('pic_id')->references('id')->on('project_pics')->nullOnDelete();
            }
        });
    }

    private function createProjectSubactionsTable(): void
    {
        if (Schema::hasTable('project_subactions')) {
            return;
        }

        Schema::create('project_subactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('action_id');
            $table->string('title', 60);
            $table->text('description')->nullable();
            $table->string('status_id', 4);
            $table->unsignedInteger('progress')->default(0);
            $table->uuid('pic_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('action_id');
            $table->index('status_id');
            $table->index('pic_id');

            if (Schema::hasTable('project_actions')) {
                $table->foreign('action_id')->references('id')->on('project_actions')->onDelete('cascade');
            }
            if (Schema::hasTable('statuses')) {
                $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            }
            if (Schema::hasTable('project_pics')) {
                $table->foreign('pic_id')->references('id')->on('project_pics')->nullOnDelete();
            }
        });
    }

    private function createProjectCostsTable(): void
    {
        if (Schema::hasTable('project_costs')) {
            return;
        }

        $projectColumnType = $this->resolveProjectKeyType();

        Schema::create('project_costs', function (Blueprint $table) use ($projectColumnType) {
            $table->uuid('id')->primary();
            if ($projectColumnType === 'uuid') {
                $table->uuid('project_id');
            } else {
                $table->unsignedBigInteger('project_id');
            }
            $table->string('cost_item', 60);
            $table->string('category', 60);
            $table->decimal('estimated_cost', 10, 2);
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('project_id');

            $this->addProjectForeignKey($table, 'project_id');
        });
    }

    private function createProjectRiskAnalysisTable(): void
    {
        if (Schema::hasTable('project_risk_analyses')) {
            return;
        }

        $projectColumnType = $this->resolveProjectKeyType();

        Schema::create('project_risk_analyses', function (Blueprint $table) use ($projectColumnType) {
            $table->uuid('id')->primary();
            if ($projectColumnType === 'uuid') {
                $table->uuid('project_id');
            } else {
                $table->unsignedBigInteger('project_id');
            }
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->enum('impact', ['low', 'medium', 'high']);
            $table->enum('likelihood', ['rare', 'possible', 'likely', 'almost_certain']);
            $table->text('mitigation_plan')->nullable();
            $table->string('status_id', 4);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('project_id');
            $table->index('status_id');

            $this->addProjectForeignKey($table, 'project_id');
            if (Schema::hasTable('statuses')) {
                $table->foreign('status_id')->references('id')->on('statuses')->onDelete('restrict');
            }
        });
    }

    private function resolveProjectKeyType(): string
    {
        if (! Schema::hasTable('projects')) {
            return 'bigint';
        }

        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ? LIMIT 1',
            [$database, 'projects', 'id']
        );

        $type = strtolower($result[0]->DATA_TYPE ?? 'bigint');

        return $type === 'char' || $type === 'uuid' || $type === 'varchar' ? 'uuid' : 'bigint';
    }

    private function addProjectForeignKey(Blueprint $table, string $column): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        $table->foreign($column)
            ->references('id')
            ->on('projects')
            ->onDelete('cascade');
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return (int) ($result[0]->c ?? 0) > 0;
    }
};
