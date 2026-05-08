<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Roles\RolePermissionSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(private readonly RolePermissionSyncService $syncService) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions_count,
                'can_delete' => $role->users_count === 0
                    && ! $role->approvalPolicyStepsTargeting()->exists()
                    && ! $role->approvalPoliciesTargeting()->exists(),
            ]);

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('admin/roles/create', [
            'permissions' => $this->permissionsByModule(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create([
            'slug' => $request->string('slug')->slug('_')->toString(),
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString() ?: null,
        ]);

        $this->syncService->syncPermissions($role, $request->input('permission_ids', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', __('Rol creado.'));
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        $role->load('permissions:id');

        return Inertia::render('admin/roles/edit', [
            'role' => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'permission_ids' => $role->permissions->pluck('id')->all(),
            ],
            'permissions' => $this->permissionsByModule(),
            'can' => [
                'delete' => auth()->user()?->can('delete', $role) ?? false,
            ],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString() ?: null,
        ]);

        $this->syncService->syncPermissions($role, $request->input('permission_ids', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', __('Rol actualizado.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.roles.index')
                ->withErrors([
                    'role' => __('No se puede eliminar un rol con usuarios asignados. Reasigne los usuarios primero.'),
                ]);
        }

        if ($role->approvalPolicyStepsTargeting()->exists() || $role->approvalPoliciesTargeting()->exists()) {
            return redirect()
                ->route('admin.roles.index')
                ->withErrors([
                    'role' => __('No se puede eliminar un rol referenciado por políticas de aprobación.'),
                ]);
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', __('Rol eliminado.'));
    }

    /**
     * @return array<string, list<array{id: int, slug: string, name: string, module: string, description: string|null}>>
     */
    private function permissionsByModule(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('position')
            ->get(['id', 'slug', 'name', 'module', 'description'])
            ->groupBy('module')
            ->map(fn ($items) => $items->map(fn (Permission $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'module' => $p->module,
                'description' => $p->description,
            ])->values()->all())
            ->all();
    }
}
