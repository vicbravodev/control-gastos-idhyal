@extends('pdf.layout')

@section('title', 'Recibo de liquidación de balance')
@section('doc-title', 'Recibo de liquidación de balance')
@section('doc-subtitle', 'Constancia de conciliación financiera')
@section('folio', $expenseRequest->folio ?? '—')

@section('content')
    <h3 class="section-title">Datos de la solicitud</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Folio solicitud</td>
            <td class="value">{{ $expenseRequest->folio ?? '—' }}</td>
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
            <td class="label">Estado del balance</td>
            <td class="value"><span class="badge badge-default">{{ $settlement->status->label() }}</span></td>
        </tr>
    </table>

    <h3 class="section-title">Conciliación de montos</h3>

    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card">
                <p class="amount-label">Base pagada</p>
                <p class="amount-value" style="font-size: 13pt;">${{ number_format($settlement->basis_amount_cents / 100, 2) }}</p>
            </div>
        </div>
        <div style="display: table-cell; width: 3%;"></div>
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card" style="border-left-color: #1a3a73;">
                <p class="amount-label">Monto comprobado</p>
                <p class="amount-value" style="font-size: 13pt;">${{ number_format($settlement->reported_amount_cents / 100, 2) }}</p>
            </div>
        </div>
        <div style="display: table-cell; width: 3%;"></div>
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card" style="border-left-color: {{ $settlement->difference_cents >= 0 ? '#166534' : '#991b1b' }};">
                <p class="amount-label">Diferencia</p>
                <p class="amount-value" style="font-size: 13pt; color: {{ $settlement->difference_cents >= 0 ? '#166534' : '#991b1b' }};">${{ number_format($settlement->difference_cents / 100, 2) }}</p>
            </div>
        </div>
    </div>

    <h3 class="section-title">Evidencia</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Archivo de evidencia</td>
            <td class="value">{{ $evidence->original_filename }}</td>
        </tr>
        <tr>
            <td class="label">Cargada por</td>
            <td class="value">{{ $evidence->uploadedBy->name }}</td>
        </tr>
    </table>
@endsection
