<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tracks authenticated sessions in the user_sessions table:
 *  - login time, last activity, chosen inactivity timeout, logout reason
 *  - provides the real "active user" count for the concurrent-user limit
 *  - lazily releases slots held by stale/abandoned sessions
 */
class SessionTracker
{
    /** Session payload key pointing at this device's user_sessions row. */
    public const SESSION_KEY = '_us_row';
    /** Record a fresh authenticated session (called right after session regeneration). */
    public function start(Request $request, User $user, int $timeoutMinutes, ?int $loginLogId = null): UserSession
    {
        $row = UserSession::updateOrCreate(
            ['session_id' => $request->session()->getId()],
            [
                'user_id' => $user->id,
                'login_log_id' => $loginLogId,
                'login_at' => now(),
                'last_activity_at' => now(),
                'logout_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'is_active' => true,
                'timeout_minutes' => $timeoutMinutes,
                'logout_reason' => null,
            ]
        );

        // Pointer stored in the (server-side) session payload so the record can
        // be found even if the framework ever rotates the session id.
        $request->session()->put(self::SESSION_KEY, $row->id);

        return $row;
    }

    /** Mark the current session as ended (logout / inactivity expiry / policy). */
    public function endCurrent(Request $request, string $reason): void
    {
        $rowId = $request->session()->get(self::SESSION_KEY);

        if ($rowId) {
            UserSession::whereKey($rowId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'logout_at' => now(),
                    'logout_reason' => $reason,
                    'last_activity_at' => now(),
                    'updated_at' => now(),
                ]);

            $request->session()->forget(self::SESSION_KEY);

            return;
        }

        UserSession::where('session_id', $request->session()->getId())
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'logout_at' => now(),
                'logout_reason' => $reason,
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /** Heartbeat: refresh last activity for the current session row. */
    public function touch(Request $request): void
    {
        $rowId = $request->session()->get(self::SESSION_KEY);

        if ($rowId) {
            UserSession::whereKey($rowId)
                ->where('is_active', true)
                ->update([
                    'last_activity_at' => now(),
                    'session_id' => $request->session()->getId(),
                    'updated_at' => now(),
                ]);

            return;
        }

        UserSession::where('session_id', $request->session()->getId())
            ->where('is_active', true)
            ->update(['last_activity_at' => now(), 'updated_at' => now()]);
    }

    public function current(Request $request): ?UserSession
    {
        $rowId = $request->session()->get(self::SESSION_KEY);

        if ($rowId) {
            $row = UserSession::find($rowId);

            if ($row && $row->is_active) {
                return $row;
            }
        }

        return UserSession::where('session_id', $request->session()->getId())
            ->where('is_active', true)
            ->first();
    }

    /**
     * Release slots held by stale sessions:
     *  - rows idle longer than the maximum allowed timeout can no longer be valid
     *  - when the session store is the database, rows whose backing session row
     *    is gone (explicit logout / invalidated session) are released immediately
     */
    public function cleanupStale(): int
    {
        $cutoff = now()->subMinutes(AppSettings::sessionMax());

        $affected = UserSession::where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $cutoff);
            })
            ->update([
                'is_active' => false,
                'logout_at' => now(),
                'logout_reason' => 'stale',
                'updated_at' => now(),
            ]);

        if (config('session.driver') === 'database') {
            $liveIds = DB::table('sessions')->pluck('id')->all();

            UserSession::where('is_active', true)
                ->when(! empty($liveIds), fn ($q) => $q->whereNotIn('session_id', $liveIds))
                ->when(empty($liveIds), fn ($q) => $q->where('session_id', '!=', ''))
                ->update([
                    'is_active' => false,
                    'logout_at' => now(),
                    'logout_reason' => 'session_ended',
                    'updated_at' => now(),
                ]);
        }

        return $affected;
    }

    /** Rows that currently hold a slot (after stale cleanup). */
    public function activeRows()
    {
        $this->cleanupStale();

        return UserSession::query()
            ->active()
            ->where('last_activity_at', '>=', now()->subMinutes(AppSettings::sessionMax()))
            ->with(['user.roles'])
            ->orderByDesc('last_activity_at');
    }

    /** Number of distinct users with a live session. */
    public function activeCount(): int
    {
        $this->cleanupStale();

        return UserSession::query()
            ->active()
            ->where('last_activity_at', '>=', now()->subMinutes(AppSettings::sessionMax()))
            ->distinct()
            ->count('user_id');
    }

    /**
     * Whether another user may log in right now.
     * A user who already holds the only available slot may re-login
     * (distinct-user counting keeps the total unchanged).
     */
    public function hasCapacity(?User $attempting = null): bool
    {
        $max = AppSettings::maxActiveUsers();

        if ($max <= 0) {
            return true; // unlimited
        }

        $this->cleanupStale();

        $activeUserIds = UserSession::query()
            ->active()
            ->where('last_activity_at', '>=', now()->subMinutes(AppSettings::sessionMax()))
            ->distinct()
            ->pluck('user_id');

        if ($attempting && $activeUserIds->contains($attempting->id)) {
            return true;
        }

        return $activeUserIds->count() < $max;
    }
}
