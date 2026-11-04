<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsageMetrics
{
    public function recordDashboardVisit(Request $request): void
    {
        // Partial reloads and speculative prefetches are not visits.
        if (! $request->isMethod('GET') || ! $request->user()
            || $request->headers->has('X-Inertia-Partial-Data')
            || $request->headers->has('X-Inertia-Partial-Except')
            || $request->headers->has('X-Inertia-Prefetch')
            || str_contains(strtolower($request->header('Purpose', '').' '.$request->header('Sec-Purpose', '')), 'prefetch')) {
            return;
        }
        $this->increment((int) $request->user()->getAuthIdentifier(), 'visits');
    }

    public function recordLogin(int $userId): void
    {
        $this->increment($userId, 'logins');
    }

    private function increment(int $userId, string $column): void
    {
        try {
            if ($userId <= 0 || ! Schema::hasTable('usage_counters')) return;
            DB::table('usage_counters')->insertOrIgnore([
                'user_id' => $userId, 'visits' => 0, 'logins' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('usage_counters')->where('user_id', $userId)->increment($column, 1, ['updated_at' => now()]);
        } catch (\Throwable $exception) {
            // Analytics must never prevent a user from signing in.
            report($exception);
        }
    }

    public function totals(?int $userId = null): array
    {
        $empty = ['visits' => null, 'logins' => null, 'since' => null, 'scope' => $userId === null ? 'all' : 'personal'];
        try {
            if (! Schema::hasTable('usage_counters')) return $empty;
            $row = DB::table('usage_counters')->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
                ->selectRaw('COALESCE(SUM(visits), 0) AS visits, COALESCE(SUM(logins), 0) AS logins, MIN(created_at) AS since')->first();
            return ['visits' => (int) $row->visits, 'logins' => (int) $row->logins, 'since' => $row->since ? substr($row->since, 0, 10) : null, 'scope' => $empty['scope']];
        } catch (\Throwable $exception) {
            report($exception);
            return $empty;
        }
    }
}
