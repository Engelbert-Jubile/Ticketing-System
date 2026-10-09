<?php

namespace App\Services;

use App\Models\KnowledgeEntry;
use App\Models\Ticket;
use App\Models\User;
use App\Support\UnitVisibility;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiSourceService
{
    public function find(User $user, string $message): array
    {
        $words = preg_split('/[^\pL\pN_-]+/u', Str::lower($message), -1, PREG_SPLIT_NO_EMPTY);
        $terms = array_slice(array_values(array_filter(array_unique($words), fn ($word) => mb_strlen($word) >= 3 && ! in_array($word, ['ticket', 'tiket', 'task', 'project', 'saya', 'yang', 'dan', 'apa', 'berapa', 'tolong', 'untuk', 'dengan', 'bagaimana', 'ringkas', 'jelaskan']))), 0, 8);
        if (! $terms) {
            return [];
        }
        $tickets = UnitVisibility::scopeTickets(Ticket::query(), $user)->where(function ($query) use ($terms) {
            foreach ($terms as $term) {
                $query->orWhere('ticket_no', 'like', '%'.$term.'%')->orWhere('title', 'like', '%'.$term.'%');
            }
        })->latest()->limit(3)->get();
        $sources = $tickets->map(fn ($ticket) => [
            'title' => $ticket->ticket_no.' · '.$ticket->title,
            'text' => Str::limit(strip_tags((string) $ticket->description), 1800),
            'status' => $ticket->status,
            'url' => route('tickets.show', ['locale' => app()->getLocale(), 'ticket' => $ticket->id]),
        ])->all();
        if (Schema::hasTable('knowledge_entries')) {
            $entries = KnowledgeEntry::visibleTo($user)->where('published', true)->where('kind', 'article')
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->orWhere('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%');
                    }
                })->latest()->limit(3)->get();
            foreach ($entries as $entry) {
                $sources[] = ['title' => $entry->title, 'text' => Str::limit($entry->body, 2500), 'url' => route('knowledge.index', ['locale' => app()->getLocale(), 'q' => $entry->title])];
            }
        }
        foreach ($sources as $i => &$source) {
            $source['ref'] = 'S'.($i + 1);
        }

        return $sources;
    }
}
