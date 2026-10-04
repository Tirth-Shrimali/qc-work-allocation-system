<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use App\Services\SessionTracker;
use App\Support\AppSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side inactivity timeout.
 *
 * The chosen duration is stored on the user's session record at login and
 * enforced here on every request: if no activity happened within the chosen
 * window the session is invalidated server-side and the user is sent back to
 * the login screen with a neutral explanation.
 */
class CheckInactivity
{
    public function __construct(private SessionTracker $tracker)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $request->hasSession()) {
            return $next($request);
        }

        $record = $this->tracker->current($request);
        $timeoutMinutes = $record?->timeout_minutes
            ?: (int) $request->session()->get('session_timeout', 0)
            ?: AppSettings::sessionDefault();

        // Enforce the administrator's absolute maximum regardless of what is stored.
        $timeoutMinutes = min($timeoutMinutes, AppSettings::sessionMax());

        if ($record && $record->last_activity_at && $record->last_activity_at->lt(now()->subMinutes($timeoutMinutes))) {
            AuditLogger::log('Authentication', 'session_expired', $user->id, null, [
                'timeout_minutes' => $timeoutMinutes,
            ]);

            $this->tracker->endCurrent($request, 'inactivity');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Your session expired because of inactivity. Please log in again.');
        }

        // Record activity for this request (creates the row for remember-me re-auth).
        if ($record) {
            $this->tracker->touch($request);
        } else {
            $this->tracker->start($request, $user, $timeoutMinutes);
            $request->session()->put('session_timeout', $timeoutMinutes);
        }

        return $next($request);
    }
}
