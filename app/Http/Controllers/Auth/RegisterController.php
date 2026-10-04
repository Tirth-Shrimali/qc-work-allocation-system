<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }

        if (! AppSettings::allowRegistration()) {
            return redirect()->route('login')
                ->withErrors(['username' => 'Registration is currently disabled. Please contact the administrator.']);
        }

        return view('auth.register', [
            'requireApproval' => AppSettings::requireApproval(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        if (! AppSettings::allowRegistration()) {
            return redirect()->route('login')
                ->withErrors(['username' => 'Registration is currently disabled. Please contact the administrator.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes and underscores.',
        ]);

        $needsApproval = AppSettings::requireApproval();

        // Explicit field list — never mass-assign the request. A registrant can
        // never choose their own role or activate themselves.
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $needsApproval ? 'pending' : 'active',
            'approved_at' => $needsApproval ? null : now(),
        ]);

        $role = Role::where('code', AppSettings::defaultRoleCode())
            ->where('status', 'active')
            ->first()
            ?: Role::where('code', 'analyst')->first();

        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        AuditLogger::log('Users', 'register', $user->id, null, [
            'username' => $user->username,
            'status' => $user->status,
            'role' => $role?->code,
            'approval_required' => $needsApproval,
        ]);

        NotificationService::toRoles(
            ['super_admin', 'qc_admin'],
            'user.registered',
            $needsApproval ? 'New Registration Awaiting Approval' : 'New User Registered',
            "{$user->name} ({$user->username}) registered via self-registration.",
            route('users.index')
        );

        $message = $needsApproval
            ? 'Your account has been created and is awaiting administrator approval. You can sign in once it is approved.'
            : 'Your account has been created successfully. You can now sign in.';

        return redirect()->route('login')->with('success', $message);
    }
}
