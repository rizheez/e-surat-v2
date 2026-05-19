<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['unit', 'position', 'roles'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, fn ($query, string $role) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role)))
            ->when($request->status !== null && $request->status !== '', fn ($query, string $status) => $query->where('is_active', $status === 'active'))
            ->when($request->unit_id, fn ($query, string $unitId) => $query->where('unit_id', $unitId));

        return Inertia::render('Users/Index', [
            'users' => $query->latest()->paginate(10)->withQueryString()->through(fn (User $user) => $this->presentUser($user)),
            'filters' => $request->only(['search', 'role', 'status', 'unit_id']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'units' => Unit::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'units' => Unit::query()->orderBy('nama')->get(['id', 'nama']),
            'positions' => Position::with('unit')->orderBy('nama')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'unit_id' => $data['unit_id'] ?: null,
                'position_id' => $data['position_id'] ?: null,
                'is_active' => (bool) $data['is_active'],
            ]);

            $user->syncRoles([$data['role']]);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load(['unit', 'position', 'roles']);

        return Inertia::render('Users/Edit', [
            'user' => $this->presentUser($user),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'units' => Unit::query()->orderBy('nama')->get(['id', 'nama']),
            'positions' => Position::with('unit')->orderBy('nama')->get(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        if ($user->is($request->user()) && !((bool) $data['is_active'])) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        DB::transaction(function () use ($user, $data) {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'unit_id' => $data['unit_id'] ?: null,
                'position_id' => $data['position_id'] ?: null,
                'is_active' => (bool) $data['is_active'],
            ];

            if (!empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);
            $user->syncRoles([$data['role']]);
        });

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return back()->with('success', sprintf(
            'User %s berhasil %s.',
            $user->name,
            $user->is_active ? 'diaktifkan' : 'dinonaktifkan'
        ));
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $temporaryPassword = Str::password(12, true, true, false, false);

        $user->forceFill([
            'password' => $temporaryPassword,
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with('success', "Password {$user->name} direset. Password sementara: {$temporaryPassword}");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        try {
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', 'User tidak dapat dihapus karena masih memiliki relasi data.');
        }

        return back()->with('success', 'User berhasil dihapus.');
    }

    private function presentUser(User $user): array
    {
        $data = $user->toArray();
        $data['roles'] = $user->getRoleNames()->values()->all();
        $data['primary_role'] = $data['roles'][0] ?? null;

        return $data;
    }
}
