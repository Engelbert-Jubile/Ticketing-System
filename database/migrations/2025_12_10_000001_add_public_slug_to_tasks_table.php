<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('public_slug')->nullable()->unique()->after('title');
        });

        Task::withoutEvents(function () {
            Task::select(['id', 'title'])->chunkById(500, function ($tasks) {
                foreach ($tasks as $task) {
                    $slug = Task::generateUniquePublicSlug($task->title, $task->id);
                    Task::whereKey($task->id)->update(['public_slug' => $slug]);
                }
            });
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_public_slug_unique');
            $table->dropColumn('public_slug');
        });
    }
};
