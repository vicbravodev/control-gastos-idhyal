@extends('pdf.layout')

@section('title', 'Acuse de comprobación de gasto')
@section('doc-title', 'Acuse de comprobación de gasto')
@section('doc-subtitle', 'Constancia de envío de comprobación a revisión')
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
        <tr>
            <td class="label">Estado solicitud</td>
            <td class="value"><span class="badge badge-default">{{ $expenseRequest->status->label() }}</span></td>
        </tr>
    </table>

    <h3 class="section-title">Comprobación de gasto</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Estado comprobación</td>
            <td class="value"><span class="badge badge-default">{{ $expenseReport->status->label() }}</span></td>
        </tr>
        <tr>
            <td class="label">Enviada a revisión</td>
            <td class="value">
                @if($expenseReport->submitted_at)
                    {{ $expenseReport->submitted_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                @else
                    —
                @endif
            </td>
        </tr>
    </table>

    <div class="amount-card" style="margin-top: 16px;">
        <p class="amount-label">Monto comprobado</p>
        <p class="amount-value">${{ number_format($expenseReport->reported_amount_cents / 100, 2) }}</p>
    </div>
@endsection
