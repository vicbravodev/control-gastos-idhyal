<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportTemplateController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ReportTemplate::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'view' => ['required', Rule::in(['resumen', 'pivote', 'detalle'])],
            'group_by' => ['nullable', 'string', 'max:32'],
            'filters' => ['required', 'array'],
            'is_shared' => ['boolean'],
        ]);

        $template = ReportTemplate::query()->create([
            'owner_user_id' => $request->user()->id,
            'slug' => null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? 'bookmark',
            'view' => $data['view'],
            'group_by' => $data['group_by'] ?? null,
            'filters' => $data['filters'],
            'is_built_in' => false,
            'is_shared' => (bool) ($data['is_shared'] ?? false),
        ]);

        return redirect()
            ->route('reports.expenses.index', ['template_id' => $template->id])
            ->with('status', __('Plantilla guardada.'));
    }

    public function update(Request $request, ReportTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'view' => ['sometimes', 'required', Rule::in(['resumen', 'pivote', 'detalle'])],
            'group_by' => ['nullable', 'string', 'max:32'],
            'filters' => ['sometimes', 'required', 'array'],
            'is_shared' => ['boolean'],
        ]);

        $template->fill($data)->save();

        return redirect()
            ->back()
            ->with('status', __('Plantilla actualizada.'));
    }

    public function destroy(Request $request, ReportTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()
            ->route('reports.expenses.index')
            ->with('status', __('Plantilla eliminada.'));
    }
}
