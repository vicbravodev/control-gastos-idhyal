<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Regions\StoreRegionRequest;
use App\Http\Requests\Admin\Regions\UpdateRegionRequest;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Region::class);

        $regions = Region::query()
            ->withCount('states')
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')
            ->get()
            ->map(fn (Region $r): array => [
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'states_count' => $r->states_count,
            ]);

        return Inertia::render('admin/regions/index', [
            'regions' => $regions,
            'filters' => [
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Region::class);

        return Inertia::render('admin/regions/create');
    }

    public function store(StoreRegionRequest $request): RedirectResponse
    {
        Region::query()->create([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
        ]);

        return redirect()
            ->route('admin.regions.index')
            ->with('status', __('Región creada.'));
    }

    public function edit(Region $region): Response
    {
        $this->authorize('update', $region);

        return Inertia::render('admin/regions/edit', [
            'region' => [
                'id' => $region->id,
                'code' => $region->code,
                'name' => $region->name,
            ],
            'can' => [
                'delete' => (auth()->user()?->can('delete', $region) ?? false)
                    && ! $region->states()->exists()
                    && ! $region->budgets()->exists(),
            ],
        ]);
    }

    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse
    {
        $region->update([
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
        ]);

        return redirect()
            ->route('admin.regions.index')
            ->with('status', __('Región actualizada.'));
    }

    public function destroy(Region $region): RedirectResponse
    {
        $this->authorize('delete', $region);

        if ($region->states()->exists()) {
            return redirect()
                ->route('admin.regions.index')
                ->withErrors([
                    'region' => __('No se puede eliminar una región que aún tiene estados. Elimine o reasigne los estados primero.'),
                ]);
        }

        if ($region->budgets()->exists()) {
            return redirect()
                ->route('admin.regions.index')
                ->withErrors([
                    'region' => __('No se puede eliminar una región con presupuestos vinculados.'),
                ]);
        }

        $region->delete();

        return redirect()
            ->route('admin.regions.index')
            ->with('status', __('Región eliminada.'));
    }
}
