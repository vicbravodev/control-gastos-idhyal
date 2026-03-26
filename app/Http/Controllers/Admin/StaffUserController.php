<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreStaffUserRequest;
use App\Http\Requests\Admin\Users\UpdateStaffUserRequest;
use App\Models\Region;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StaffUserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('manageStaffDirectory', User::class);

        $users = User::query()
            ->with(['role:id,slug,name', 'region:id,name,code', 'state:id,name,code'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('role'), fn ($q, $roleId) => $q->where('role_id', $roleId))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => $this->presentUserRow($u));

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $request->query('search', ''),
                'role' => $request->query('role', ''),
            ],
            'roles' => Role::query()->orderBy('name')->get(['id', 'name'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageStaffDirectory', User::class);

        return Inertia::render('admin/users/create', $this->formShared());
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'username' => $request->filled('username')
                ? $request->string('username')->toString()
                : null,
            'phone' => $request->filled('phone')
                ? $request->string('phone')->toString()
                : null,
            'password' => Hash::make($request->string('password')->toString()),
            'role_id' => $request->filled('role_id') ? $request->integer('role_id') : null,
            'region_id' => $request->filled('region_id') ? $request->integer('region_id') : null,
            'state_id' => $request->filled('state_id') ? $request->integer('state_id') : null,
            'hire_date' => $request->date('hire_date'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Usuario creado.'));
    }

    public function edit(User $user): Response
    {
        $this->authorize('manageStaffDirectory', User::class);

        $user->load(['role:id,slug,name', 'region:id,name,code', 'state:id,name,code,region_id']);

        return Inertia::render('admin/users/edit', array_merge($this->formShared(), [
            'user' => $this->presentUserRow($user),
        ]));
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'username' => $request->filled('username')
                ? $request->string('username')->toString()
                : null,
            'phone' => $request->filled('phone')
                ? $request->string('phone')->toString()
                : null,
            'role_id' => $request->filled('role_id') ? $request->integer('role_id') : null,
            'region_id' => $request->filled('region_id') ? $request->integer('region_id') : null,
            'state_id' => $request->filled('state_id') ? $request->integer('state_id') : null,
            'hire_date' => $request->filled('hire_date') ? $request->date('hire_date') : null,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('Usuario actualizado.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formShared(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(['id', 'slug', 'name']),
            'regions' => Region::query()->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentUserRow(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'username' => $u->username,
            'phone' => $u->phone,
            'role_id' => $u->role_id,
            'region_id' => $u->region_id,
            'state_id' => $u->state_id,
            'role' => $u->role !== null
                ? $u->role->only(['id', 'slug', 'name'])
                : null,
            'region' => $u->region !== null
                ? $u->region->only(['id', 'name', 'code'])
                : null,
            'state' => $u->state !== null
                ? $u->state->only(['id', 'name', 'code', 'region_id'])
                : null,
            'hire_date' => $u->hire_date?->toDateString(),
        ];
    }
}
