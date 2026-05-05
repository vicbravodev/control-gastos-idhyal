<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\StoreDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->withCount('users')
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $d): array => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'is_active' => $d->is_active,
                'position' => $d->position,
                'users_count' => $d->users_count,
            ]);

        return Inertia::render('admin/departments/index', [
            'departments' => $departments,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Department::class);

        return Inertia::render('admin/departments/create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::query()->create([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', true),
            'position' => $request->filled('position') ? $request->integer('position') : 0,
        ]);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', __('Departamento creado.'));
    }

    public function edit(Department $department): Response
    {
        $this->authorize('update', $department);

        return Inertia::render('admin/departments/edit', [
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => $department->is_active,
                'position' => $department->position,
            ],
            'can' => [
                'delete' => (auth()->user()?->can('delete', $department) ?? false)
                    && ! $department->users()->exists(),
            ],
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
            'is_active' => $request->boolean('is_active', $department->is_active),
            'position' => $request->filled('position') ? $request->integer('position') : $department->position,
        ]);

        return redirect()
            ->route('admin.departments.index')
            ->with('status', __('Departamento actualizado.'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            return redirect()
                ->route('admin.departments.index')
                ->withErrors([
                    'department' => __('No se puede eliminar un departamento con usuarios asignados. Reasigne los usuarios primero.'),
                ]);
        }

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('status', __('Departamento eliminado.'));
    }
}
