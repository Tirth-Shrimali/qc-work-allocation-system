<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with(['roles', 'employee'])->orderBy('name');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('code', $request->input('role')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'roles' => Role::active()->get(),
        ]);
    }

    public function create()
    {
        return view('users.form', [
            'user' => null,
            'roles' => Role::active()->get(),
            'employees' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'employee_id' => $data['employee_id'] ?? null,
            'status' => $data['status'],
        ]);

        $user->roles()->sync([$data['role_id']]);

        AuditLogger::log('Users', 'create', $user->id, null, ['username' => $user->username]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::active()->get(),
            'employees' => Employee::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($user->id === $request->user()->id && $data['status'] !== 'active') {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        $old = ['status' => $user->status, 'roles' => $user->roles->pluck('code')->all()];

        $user->fill(collect($data)->except('password', 'role_id')->all());

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->roles()->sync([$data['role_id']]);

        AuditLogger::log('Users', 'update', $user->id, $old, ['status' => $user->status, 'role_id' => $data['role_id']]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        AuditLogger::log('Users', $user->status === 'active' ? 'activate' : 'deactivate', $user->id);

        $message = $user->status === 'active' ? 'activated' : 'deactivated';

        return back()->with('success', "User {$message} successfully.");
    }
}
