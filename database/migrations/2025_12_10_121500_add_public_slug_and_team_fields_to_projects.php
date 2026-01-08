<?php

use App\Models\Project;
use App\Models\ProjectPic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'public_slug')) {
                    $table->string('public_slug', 160)->nullable()->after('title');
                    $table->unique('public_slug', 'projects_public_slug_unique');
                }

                if (! Schema::hasColumn('projects', 'agent_id')) {
                    $table->unsignedBigInteger('agent_id')->nullable()->after('created_by');
                    $table->index('agent_id', 'projects_agent_id_idx');
                }

                if (! Schema::hasColumn('projects', 'assigned_id')) {
                    $table->unsignedBigInteger('assigned_id')->nullable()->after('agent_id');
                    $table->index('assigned_id', 'projects_assigned_id_idx');
                }
            });
        }

        if (Schema::hasTable('project_pics')) {
            Schema::table('project_pics', function (Blueprint $table) {
                if (! Schema::hasColumn('project_pics', 'role_type')) {
                    $table->string('role_type', 20)->default('pic')->after('user_id');
                    $table->index('role_type', 'project_pics_role_type_idx');
                }

                if (! Schema::hasColumn('project_pics', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('role_type');
                    $table->index('is_primary', 'project_pics_is_primary_idx');
                }
            });
        }

        if (! class_exists(Project::class)) {
            return;
        }

        Project::withoutEvents(function () {
            Project::query()
                ->with([
                    'pics:id,project_id,user_id,position,role_type,is_primary',
                    'ticket:id,agent_id',
                ])
                ->orderBy('id')
                ->chunkById(100, function ($projects) {
                    foreach ($projects as $project) {
                        $updates = [];

                        if (! $project->public_slug) {
                            $updates['public_slug'] = Project::generateUniquePublicSlug($project->title, $project->id);
                        }

                        $picUsers = $project->pics->pluck('user_id')->filter()->values();

                        if (! $project->assigned_id && $picUsers->isNotEmpty()) {
                            $updates['assigned_id'] = $picUsers->first();
                        }

                        if (! $project->agent_id && $project->ticket && $project->ticket->agent_id) {
                            $updates['agent_id'] = $project->ticket->agent_id;
                        }

                        if (! empty($updates)) {
                            $project->forceFill($updates)->saveQuietly();
                        }

                        foreach ($project->pics as $index => $pic) {
                            $attributes = [];

                            if (! $pic->role_type) {
                                $attributes['role_type'] = 'pic';
                            }

                            $attributes['is_primary'] = $index === 0;

                            if (! empty($attributes)) {
                                $pic->forceFill($attributes)->saveQuietly();
                            }
                        }
                    }
                });
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('project_pics')) {
            Schema::table('project_pics', function (Blueprint $table) {
                if (Schema::hasColumn('project_pics', 'is_primary')) {
                    $table->dropIndex('project_pics_is_primary_idx');
                    $table->dropColumn('is_primary');
                }
                if (Schema::hasColumn('project_pics', 'role_type')) {
                    $table->dropIndex('project_pics_role_type_idx');
                    $table->dropColumn('role_type');
                }
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (Schema::hasColumn('projects', 'assigned_id')) {
                    $table->dropIndex('projects_assigned_id_idx');
                    $table->dropColumn('assigned_id');
                }

                if (Schema::hasColumn('projects', 'agent_id')) {
                    $table->dropIndex('projects_agent_id_idx');
                    $table->dropColumn('agent_id');
                }

                if (Schema::hasColumn('projects', 'public_slug')) {
                    $table->dropUnique('projects_public_slug_unique');
                    $table->dropColumn('public_slug');
                }
            });
        }
    }
};
