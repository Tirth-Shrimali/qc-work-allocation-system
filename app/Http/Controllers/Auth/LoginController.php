<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Services\AuditLogger;
use App\Services\SessionTracker;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private SessionTracker $sessions)
    {
    }

    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.login', [
            'timeoutChoices' => AppSettings::timeoutChoices(),
            'allowRegistration' => AppSettings::allowRegistration(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'session_timeout' => ['nullable', 'integer'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('username')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $remember = $request->boolean('remember');
        $timeoutMinutes = AppSettings::resolveTimeout($request->input('session_timeout'));

        if (! Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $remember)) {
            RateLimiter::hit($throttleKey, 60);

            LoginLog::create([
                'username' => $credentials['username'],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'status' => 'FAILED',
                'failure_reason' => 'Invalid credentials',
            ]);

            throw ValidationException::withMessages([
                'username' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        if ($user->status === 'pending') {
            $this->rejectLogin($request, $user, 'Account pending approval');

            throw ValidationException::withMessages([
                'username' => 'Your account is pending approval. Please contact the administrator.',
            ]);
        }

        if ($user->status !== 'active') {
            $this->rejectLogin($request, $user, 'Account inactive');

            throw ValidationException::withMessages([
                'username' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        // Admin-controlled simultaneous user limit (real session count, not the users table).
        if (! $this->sessions->hasCapacity($user)) {
            $this->rejectLogin($request, $user, 'Concurrent user limit reached');

            throw ValidationException::withMessages([
                'username' => 'The maximum number of active users has currently been reached. Please try again later.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        $log = LoginLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => 'SUCCESS',
        ]);

        // Remember the chosen inactivity duration for this session (server-side record).
        $request->session()->put('session_timeout', $timeoutMinutes);
        $this->sessions->start($request, $user, $timeoutMinutes, $log->id);

        AuditLogger::log('Authentication', 'login', $user->id, null, [
            'session_timeout_minutes' => $timeoutMinutes,
        ]);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            LoginLog::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->where('status', 'SUCCESS')
                ->orderByDesc('id')
                ->limit(1)
                ->update(['logout_at' => now()]);

            $this->sessions->endCurrent($request, 'logout');

            AuditLogger::log('Authentication', 'logout', $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Roll back the authentication attempt for a rejected login. */
    private function rejectLogin(Request $request, \App\Models\User $user, string $reason): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        LoginLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $request->ip(),
            'status' => 'FAILED',
            'failure_reason' => $reason,
        ]);
    }
}
