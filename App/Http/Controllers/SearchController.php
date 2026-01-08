<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Support\UnitVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $query = $request->input('query');
        $locale = app()->getLocale() ?? config('app.locale', 'en');

        if (! $query) {
            return redirect()->back()->with('error', 'Silakan masukkan kata kunci pencarian.');
        }

        $ticketsQuery = Ticket::with(['requester', 'statusRelation', 'priorityRelation']);
        $ticketsQuery = UnitVisibility::scopeTickets($ticketsQuery, $request->user());

        $tickets = $ticketsQuery
            ->where(function ($builder) use ($query) {
                $builder->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->limit(10)
            ->get();

        $tasksQuery = Task::with(['requester']);
        $tasksQuery = UnitVisibility::scopeTasks($tasksQuery, $request->user());

        $tasks = $tasksQuery
            ->where(function ($builder) use ($query) {
                $builder->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->limit(10)
            ->get();

        $projectsQuery = Project::with(['user']);
        $projectsQuery = UnitVisibility::scopeProjects($projectsQuery, $request->user());

        $projects = $projectsQuery
            ->where(function ($builder) use ($query) {
                $builder->where('title', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->limit(10)
            ->get();

        $backUrl = route('dashboard', ['locale' => $locale]);
        $previousUrl = url()->previous();
        if ($previousUrl) {
            $currentPath = parse_url(url()->current(), PHP_URL_PATH);
            $previousPath = parse_url($previousUrl, PHP_URL_PATH);

            $isSamePath = $previousPath && $currentPath && $previousPath === $currentPath;
            $isSearchPath = $previousPath && str_starts_with($previousPath, '/search');

            if (! $isSamePath && ! $isSearchPath) {
                $backUrl = $previousUrl;
            }
        }

        $fullUrl = $request->fullUrl();

        return Inertia::render('Search/Results', [
            'query' => $query,
            'backUrl' => $backUrl,
            'tickets' => $tickets->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => Str::of((string) $ticket->description)->stripTags()->toString(),
                'status' => $ticket->statusRelation ? [
                    'name' => $ticket->statusRelation->name,
                    'bg_color' => $ticket->statusRelation->bg_color,
                    'text_color' => $ticket->statusRelation->text_color,
                ] : null,
                'priority' => $ticket->priorityRelation ? [
                    'name' => $ticket->priorityRelation->name,
                    'bg_color' => $ticket->priorityRelation->bg_color,
                    'text_color' => $ticket->priorityRelation->text_color,
                ] : null,
                'created_at' => optional($ticket->created_at)?->format('d M Y'),
                'created_diff' => optional($ticket->created_at)?->diffForHumans(),
                'url' => route('tickets.show', $ticket),
            ])->values()->all(),
            'tasks' => $tasks->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => Str::of((string) $task->description)->stripTags()->toString(),
                'status' => [
                    'value' => $task->status instanceof \BackedEnum ? $task->status->value : (string) $task->status,
                    'label' => $task->status_label ?? Str::headline((string) $task->status),
                ],
                'requester' => $task->requester ? [
                    'id' => $task->requester->id,
                    'name' => $task->requester->display_name ?? $task->requester->name ?? $task->requester->email,
                ] : null,
                'created_diff' => optional($task->created_at)?->diffForHumans(),
                'url' => route('tasks.show', ['taskSlug' => $task->public_slug]),
            ])->values()->all(),
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => Str::of((string) $project->description)->stripTags()->toString(),
                'created_at' => optional($project->created_at)?->format('d M Y'),
                'owner' => $project->user ? [
                    'id' => $project->user->id,
                    'name' => $project->user->display_name ?? $project->user->name ?? $project->user->email,
                ] : null,
                'url' => route('projects.show', ['project' => $project->public_slug]),
            ])->values()->all(),
        ]);
    }
}
