<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Holidays\StoreHolidayRequest;
use App\Http\Requests\Admin\Holidays\UpdateHolidayRequest;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HolidayController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Holiday::class);

        $holidays = Holiday::query()
            ->when($request->query('search'), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->query('year'), fn ($q, $year) => $q->whereYear('date', $year))
            ->orderBy('date')
            ->get()
            ->map(fn (Holiday $h): array => [
                'id' => $h->id,
                'date' => $h->date?->toDateString(),
                'name' => $h->name,
                'description' => $h->description,
            ]);

        return Inertia::render('admin/holidays/index', [
            'holidays' => $holidays,
            'filters' => [
                'search' => $request->query('search', ''),
                'year' => $request->query('year', ''),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Holiday::class);

        return Inertia::render('admin/holidays/create');
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        Holiday::query()->create([
            'date' => $request->date('date'),
            'name' => $request->string('name')->toString(),
            'description' => $request->filled('description')
                ? $request->string('description')->toString()
                : null,
        ]);

        return redirect()
            ->route('admin.holidays.index')
            ->with('status', __('Día festivo agregado.'));
    }

    public function edit(Holiday $holiday): Response
    {
        $this->authorize('update', $holiday);

        return Inertia::render('admin/holidays/edit', [
            'holiday' => [
                'id' => $holiday->id,
                'date' => $holiday->date?->toDateString(),
                'name' => $holiday->name,
                'description' => $holiday->description,
            ],
        ]);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $holiday->update([
            'date' => $request->date('date'),
            'name' => $request->string('name')->toString(),
            'description' => $request->filled('description')
                ? $request->string('description')->toString()
                : null,
        ]);

        return redirect()
            ->route('admin.holidays.index')
            ->with('status', __('Día festivo actualizado.'));
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);
        $holiday->delete();

        return redirect()
            ->route('admin.holidays.index')
            ->with('status', __('Día festivo eliminado.'));
    }
}
