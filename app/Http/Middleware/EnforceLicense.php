<?php

namespace App\Http\Middleware;

use App\Support\AppSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Centralised application validity enforcement.
 *
 * The state (active / expiring / grace / expired / suspended) is computed
 * server-side from database-backed dates using the application clock — nothing
 * supplied by the browser is trusted. Holders of the system.settings
 * permission always retain access so an administrator can renew the licence.
 */
class EnforceLicense
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always (re)share so no banner leaks between requests in one process.
        $banner = null;

        // Health check, compiled assets and public storage are never gated.
        if ($request->is('up', 'storage/*', 'vendor/*', 'build/*')) {
            return $next($request);
        }

        $state = AppSettings::licenseState();
        $remaining = AppSettings::remainingDays();
        $user = $request->user();
        $privileged = $user && $user->hasPermission('system.settings');

        switch ($state) {
            case 'grace':
                $banner = [
                    'type' => 'warning',
                    'icon' => 'bi-exclamation-triangle',
                    'text' => 'License Expired — Grace Period Active. Contact your administrator to extend the validity.',
                ];
                break;

            case 'expiring':
                $banner = [
                    'type' => 'warning',
                    'icon' => 'bi-hourglass-split',
                    'text' => 'Application license expires in '.($remaining ?? 0).' day(s) — '.AppSettings::licenseExpiry()?->format('d M Y').'.',
                ];
                break;

            case 'suspended':
                $banner = [
                    'type' => 'danger',
                    'icon' => 'bi-slash-circle',
                    'text' => 'Application suspended by the administrator.',
                ];
                break;

            case 'expired':
                $banner = [
                    'type' => 'danger',
                    'icon' => 'bi-calendar-x',
                    'text' => 'Application license expired on '.AppSettings::licenseExpiry()?->format('d M Y').'.',
                ];
                break;
        }

        View::share('licenseBanner', $banner);

        if (in_array($state, ['active', 'expiring', 'unconfigured', 'grace'], true)) {
            return $next($request);
        }

        // expired | suspended → policy applies to everyone except system.settings holders.
        if ($privileged) {
            return $next($request);
        }

        $behavior = AppSettings::licenseBehavior();

        if ($state === 'suspended') {
            return $this->expiredResponse($state);
        }

        if ($behavior === 'restrict_login') {
            // Only authentication is blocked; already-authenticated sessions continue.
            if ($request->routeIs('login', 'register')) {
                return $this->expiredResponse($state);
            }

            return $next($request);
        }

        if ($behavior === 'readonly') {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return $next($request);
            }

            return $this->expiredResponse($state);
        }

        // Default: block the application entirely.
        return $this->expiredResponse($state);
    }

    private function expiredResponse(string $state): Response
    {
        return response()->view('errors.license-expired', [
            'state' => $state,
            'expiry' => AppSettings::licenseExpiry(),
            'behavior' => AppSettings::licenseBehavior(),
        ], 503);
    }
}
