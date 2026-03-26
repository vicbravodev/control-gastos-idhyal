@extends('pdf.layout')

@section('title', 'Acuse de solicitud de gasto')
@section('doc-title', 'Acuse de solicitud de gasto')
@section('doc-subtitle', 'Constancia de recepción de solicitud en el sistema')
@section('folio', $expenseRequest->folio ?? '—')

@section('content')
    <h3 class="section-title">Datos de la solicitud</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Folio</td>
            <td class="value">{{ $expenseRequest->folio ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Estado</td>
            <td class="value"><span class="badge badge-default">{{ $expenseRequest->status->label() }}</span></td>
        </tr>
        <tr>
            <td class="label">Solicitante</td>
            <td class="value">{{ $expenseRequest->user->name }}</td>
        </tr>
        <tr>
            <td class="label">Concepto</td>
            <td class="value">{{ $expenseRequest->expenseConcept?->name ?? '—' }}</td>
        </tr>
        @if(filled($expenseRequest->concept_description))
        <tr>
            <td class="label">Detalle</td>
            <td class="value">{{ $expenseRequest->concept_description }}</td>
        </tr>
        @endif
        <tr class="highlight-row">
            <td class="label">Monto solicitado</td>
            <td class="value amount">${{ number_format($expenseRequest->requested_amount_cents / 100, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Método de entrega</td>
            <td class="value">{{ $expenseRequest->delivery_method->label() }}</td>
        </tr>
    </table>

    <h3 class="section-title">Cadena de aprobación</h3>

    <table class="data-table">
        <thead>
            <tr>
                <th>Paso</th>
                <th>Rol</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenseRequest->approvals->sortBy('step_order') as $a)
            <tr>
                <td>{{ $a->step_order }}</td>
                <td>{{ $a->role->name }}</td>
                <td><span class="badge badge-pending">{{ $a->status->label() }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
