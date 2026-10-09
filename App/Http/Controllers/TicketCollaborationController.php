<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Support\RoleHelpers;
use App\Support\UnitVisibility;
use App\Support\WorkflowStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketCollaborationController extends Controller
{
    private function internal(User $user, Ticket $ticket): bool
    {
        return RoleHelpers::userIsSuperAdmin($user) || $user->hasRole('admin') || $ticket->isAgent($user);
    }

    public function index(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $internal = $this->internal($request->user(), $ticket);
        $messages = DB::table('ticket_messages')->join('users', 'users.id', '=', 'ticket_messages.user_id')
            ->where('ticket_id', $ticket->id)->when(! $internal, fn ($q) => $q->where('internal', false))
            ->select('ticket_messages.*', 'users.first_name', 'users.last_name')->orderByDesc('ticket_messages.id')
            ->paginate(20)->withQueryString();

        return response()->json([
            'messages' => $messages,
            'checklist' => DB::table('ticket_checklist_items')->where('ticket_id', $ticket->id)->orderBy('id')->get(),
            'feedback' => DB::table('ticket_feedback')->where('ticket_id', $ticket->id)->first(),
            'can_internal' => $internal,
            'can_feedback' => $ticket->isRequester($request->user()) && $ticket->status === WorkflowStatus::DONE,
        ]);
    }

    public function message(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'internal' => ['required', 'boolean']]);
        abort_if($data['internal'] && ! $this->internal($request->user(), $ticket), 403);
        DB::transaction(function () use ($data, $request, $ticket, $locale) {
            DB::table('ticket_messages')->insert($data + ['ticket_id' => $ticket->id, 'user_id' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            // Mentions only notify existing participants who can read the message.
            preg_match_all('/@([a-zA-Z0-9_.-]+)/', $data['body'], $matches);
            $participants = $ticket->assignedUsers()->pluck('users.id')->push($ticket->agent_id, $ticket->assigned_id, $ticket->requester_id)->filter()->unique();
            $users = User::whereIn('id', $participants)->whereIn('username', array_slice(array_unique($matches[1]), 0, 10))->get();
            foreach ($users as $user) {
                if ($user->id === $request->user()->id || ($data['internal'] && ! $this->internal($user, $ticket))) {
                    continue;
                }
                $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'ticket.mention', 'data' => [
                    'title' => 'Anda disebut di ticket '.$ticket->ticket_no, 'message' => 'Ada pesan baru yang membutuhkan perhatian Anda.',
                    'url' => route('tickets.show', ['locale' => $locale, 'ticket' => $ticket->id]), 'icon' => 'alternate_email',
                ]]);
            }
        });

        return response()->json(['success' => true], 201);
    }

    public function checklist(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        abort_unless($this->internal($request->user(), $ticket), 403);
        $data = $request->validate(['id' => ['nullable', 'integer'], 'title' => ['required_without:id', 'string', 'max:255'], 'done' => ['sometimes', 'boolean']]);
        if (! empty($data['id'])) {
            $item = DB::table('ticket_checklist_items')->where('ticket_id', $ticket->id)->where('id', $data['id']);
            abort_unless($item->exists(), 404);
            $item->update(['done' => $data['done'] ?? false, 'updated_by' => $request->user()->id, 'updated_at' => now()]);
        } else {
            abort_if(DB::table('ticket_checklist_items')->where('ticket_id', $ticket->id)->count() >= 100, 422, 'Maksimal 100 checklist.');
            DB::table('ticket_checklist_items')->insert(['ticket_id' => $ticket->id, 'title' => $data['title'], 'done' => false, 'updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function feedback(Request $request, string $locale, Ticket $ticket)
    {
        UnitVisibility::ensureTicketAccess($request->user(), $ticket);
        abort_unless($ticket->isRequester($request->user()) && $ticket->status === WorkflowStatus::DONE, 403);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:2000'], 'reopen_requested' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $ticket, $data, $locale) {
            Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $previous = DB::table('ticket_feedback')->where('ticket_id', $ticket->id)->first();
            DB::table('ticket_feedback')->updateOrInsert(['ticket_id' => $ticket->id], $data + ['user_id' => $request->user()->id, 'updated_at' => now(), 'created_at' => $previous?->created_at ?? now()]);
            if (! $data['reopen_requested'] || $previous?->reopen_requested) {
                return;
            }
            $participants = $ticket->assignedUsers()->pluck('users.id')->push($ticket->agent_id, $ticket->assigned_id)->filter()->unique();
            foreach (User::whereIn('id', $participants)->get() as $user) {
                if ($user->id === $request->user()->id || ! UnitVisibility::scopeTickets(Ticket::query(), $user)->whereKey($ticket->id)->exists()) {
                    continue;
                }
                $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'ticket.follow-up', 'data' => [
                    'title' => 'Permintaan tindak lanjut '.$ticket->ticket_no,
                    'message' => 'Requester memerlukan bantuan kembali. Tinjau penilaian dan hubungi requester.',
                    'url' => route('tickets.show', ['locale' => $locale, 'ticket' => $ticket->id]), 'icon' => 'feedback',
                ]]);
            }
        });

        return response()->json(['success' => true]);
    }
}
