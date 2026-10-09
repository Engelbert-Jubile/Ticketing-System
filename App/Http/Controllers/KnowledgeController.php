<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeEntry;
use App\Support\RoleHelpers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KnowledgeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'kind' => ['nullable', Rule::in(['article', 'template'])]]);
        $entries = KnowledgeEntry::visibleTo($request->user())
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($b) => $b->where('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%')))
            ->when($filters['kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))
            ->latest()->paginate(15)->withQueryString();
        $canManage = RoleHelpers::userIsSuperAdmin($request->user()) || $request->user()->hasRole('admin');
        $assignees = $canManage ? \App\Models\User::query()
            ->when(! RoleHelpers::userIsSuperAdmin($request->user()), fn ($q) => $q->where('unit', $request->user()->unit))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'unit']) : collect();

        return Inertia::render('Knowledge/Index', ['entries' => $entries, 'filters' => $filters, 'can_manage' => $canManage, 'assignees' => $assignees]);
    }

    public function save(Request $request)
    {
        $user = $request->user();
        abort_unless(RoleHelpers::userIsSuperAdmin($user) || $user->hasRole('admin'), 403);
        abort_unless(RoleHelpers::userIsSuperAdmin($user) || trim((string) $user->unit) !== '', 403);
        $data = $request->validate([
            'id' => ['nullable', 'integer'], 'kind' => ['required', Rule::in(['article', 'template'])],
            'title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:100'], 'published' => ['required', 'boolean'],
            'checklist' => ['sometimes', 'array', 'max:30'], 'checklist.*' => ['required', 'string', 'max:255'],
            'default_assignee_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->whereNull('deleted_at')->when(! RoleHelpers::userIsSuperAdmin($user), fn ($q) => $q->where('unit', $user->unit)))],
        ]);
        $entry = empty($data['id']) ? new KnowledgeEntry : KnowledgeEntry::visibleTo($user)->findOrFail($data['id']);
        if ($entry->exists) {
            abort_unless(RoleHelpers::userIsSuperAdmin($user) || (int) $entry->author_id === (int) $user->id, 403);
        }
        unset($data['id']);
        if (! $entry->exists) {
            $entry->author_id = $user->id;
            $entry->unit = $user->unit ?: null;
        }
        $entry->fill($data)->save();

        return back()->with('success', 'Artikel atau template tersimpan.');
    }
}
