<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\States\StoreStateRequest;
use App\Http\Requests\Admin\States\UpdateStateRequest;
use App\Models\Region;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', State::class);

        $states = State::query()
            ->with('region:id,name,code')
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->when($request->query('region'), fn ($q, $regionId) => $q->where('region_id', $regionId))
            ->orderBy('name')
            ->get()
            ->map(fn (State $s): array => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->name,
                'region' => $s->region !== null
                    ? [
                        'id' => $s->region->id,
                        'name' => $s->region->name,
                        'code' => $s->region->code,
                    ]
                    : null,
            ]);

        return Inertia::render('admin/states/index', [
            'states' => $states,
            'filters' => [
                'search' => $request->query('search', ''),
                'region' => $request->query('region', ''),
            ],
            'regions' => Region::query()->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($r) => ['value' => (string) $r->id, 'label' => $r->name]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', State::class);

        return Inertia::render('admin/states/create', $this->formShared());
    }

    public function store(StoreStateRequest $request): RedirectResponse
    {
        State::query()->create([
            'region_id' => $request->integer('region_id'),
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
        ]);

        return redirect()
            ->route('admin.states.index')
            ->with('status', __('Estado creado.'));
    }

    public function edit(State $state): Response
    {
        $this->authorize('update', $state);

        return Inertia::render('admin/states/edit', array_merge($this->formShared(), [
            'state' => [
                'id' => $state->id,
                'region_id' => $state->region_id,
                'code' => $state->code,
                'name' => $state->name,
            ],
            'can' => [
                'delete' => (auth()->user()?->can('delete', $state) ?? false)
                    && ! $state->budgets()->exists(),
            ],
        ]));
    }

    public function update(UpdateStateRequest $request, State $state): RedirectResponse
    {
        $state->update([
            'region_id' => $request->integer('region_id'),
            'code' => $request->string('code')->toString(),
            'name' => $request->string('name')->toString(),
        ]);

        return redirect()
            ->route('admin.states.index')
            ->with('status', __('Estado actualizado.'));
    }

    public function destroy(State $state): RedirectResponse
    {
        $this->authorize('delete', $state);

        if ($state->budgets()->exists()) {
            return redirect()
                ->route('admin.states.index')
                ->withErrors([
                    'state' => __('No se puede eliminar un estado con presupuestos vinculados.'),
                ]);
        }

        $state->delete();

        return redirect()
            ->route('admin.states.index')
            ->with('status', __('Estado eliminado.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formShared(): array
    {
        return [
            'regions' => Region::query()->orderBy('name')->get(['id', 'name', 'code']),
        ];
    }
}
