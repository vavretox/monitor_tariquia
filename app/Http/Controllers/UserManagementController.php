<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('roles')
            ->when($request->filled('buscar'), fn ($query) => $query->where(function ($query) use ($request) {
                $term = '%'.$request->string('buscar')->trim().'%';
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);
        $temporaryPassword = Str::password(14);

        try {
            DB::transaction(function () use ($data, $temporaryPassword): void {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => Str::lower($data['email']),
                    'password' => $temporaryPassword,
                    'is_active' => $data['is_active'],
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                ]);
                $user->syncRoles([$data['role']]);
                $user->notify(new UserCredentialsNotification($temporaryPassword));
                $user->update(['credentials_sent_at' => now()]);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput($request->except('role'))
                ->withErrors(['email' => 'No fue posible enviar las credenciales por correo. Verifique la configuración SMTP o inténtelo nuevamente.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado y credenciales enviadas a su correo.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'No puedes desactivar tu propia cuenta.'])->withInput();
        }

        $user->update([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'is_active' => $data['is_active'],
        ]);
        $user->syncRoles([$data['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'No puedes eliminar tu propia cuenta.']);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    public function resendCredentials(User $user): RedirectResponse
    {
        $temporaryPassword = Str::password(14);

        try {
            $user->notify(new UserCredentialsNotification($temporaryPassword));
            $user->update([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'credentials_sent_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'No se modificó la contraseña porque el correo no pudo enviarse. Inténtelo nuevamente.']);
        }

        return back()->with('success', 'Se generó una nueva contraseña temporal y se envió por correo.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
