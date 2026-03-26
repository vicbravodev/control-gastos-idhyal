@extends('pdf.layout')

@section('title', 'Recibo de aprobación de solicitud de gasto')
@section('doc-title', 'Recibo de aprobación')
@section('doc-subtitle', 'Cadena de aprobación completada satisfactoriamente')
@section('folio', $expenseRequest->folio ?? '—')

@section('content')
    <h3 class="section-title">Datos de la solicitud</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Folio</td>
            <td class="value">{{ $expenseRequest->folio ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Estado actual</td>
            <td class="value"><span class="badge badge-approved">{{ $expenseRequest->status->label() }}</span></td>
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
        <tr>
            <td class="label">Método de entrega</td>
            <td class="value">{{ $expenseRequest->delivery_method->label() }}</td>
        </tr>
    </table>

    <div style="display: table; width: 100%; margin-top: 12px;">
        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="amount-card">
                <p class="amount-label">Monto solicitado</p>
                <p class="amount-value">${{ number_format($expenseRequest->requested_amount_cents / 100, 2) }}</p>
            </div>
        </div>
        <div style="display: table-cell; width: 4%;"></div>
        <div style="display: table-cell; width: 48%; vertical-align: top;">
            <div class="amount-card" style="border-left-color: #1a3a73;">
                <p class="amount-label">Monto aprobado</p>
                <p class="amount-value">{{ $expenseRequest->approved_amount_cents !== null ? '$' . number_format($expenseRequest->approved_amount_cents / 100, 2) : '—' }}</p>
            </div>
        </div>
    </div>

    <h3 class="section-title">Aprobaciones registradas</h3>

    <table class="data-table">
        <thead>
            <tr>
                <th>Paso</th>
                <th>Rol</th>
                <th>Resultado</th>
                <th>Aprobador</th>
                <th>Fecha y hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenseRequest->approvals as $a)
            <tr>
                <td>{{ $a->step_order }}</td>
                <td>{{ $a->role->name }}</td>
                <td><span class="badge badge-approved">{{ $a->status->label() }}</span></td>
                <td>{{ $a->approver?->name ?? '—' }}</td>
                <td>{{ $a->acted_at ? $a->acted_at->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
