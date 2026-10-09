<?php

namespace App\Http\Controllers;

use App\Services\WorkListService;
use App\Support\WorkflowStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request, WorkListService $service): Response
    {
        return $this->render($request, $service, false);
    }

    public function work(Request $request, WorkListService $service): Response
    {
        return $this->render($request, $service, true);
    }

    private function render(Request $request, WorkListService $service, bool $personal): Response
    {
        $filters = $request->validate([
            'query' => ['nullable', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(['ticket', 'task', 'project'])],
            'status' => ['nullable', Rule::in(WorkflowStatus::all())],
            'view' => ['nullable', Rule::in(['mine', 'waiting', 'due', 'overdue'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        return Inertia::render('Search/Results', [
            'personal' => $personal, 'filters' => $filters,
            'statuses' => WorkflowStatus::labels(),
            'results' => $service->fetch($request->user(), $filters, $personal),
        ]);
    }
}
