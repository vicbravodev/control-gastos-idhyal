@extends('pdf.layout')

@section('title', 'Recibo de pago ejecutado')
@section('doc-title', 'Recibo de pago ejecutado')
@section('doc-subtitle', 'Comprobante de dispersión de recursos')
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
    </table>

    <div class="amount-card" style="margin-top: 16px;">
        <p class="amount-label">Monto pagado</p>
        <p class="amount-value">${{ number_format($payment->amount_cents / 100, 2) }}</p>
    </div>

    <h3 class="section-title">Detalle del pago</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Método de pago</td>
            <td class="value">{{ $payment->payment_method->label() }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de pago</td>
            <td class="value">{{ $payment->paid_on->format('d/m/Y') }}</td>
        </tr>
        @if($payment->transfer_reference)
        <tr class="highlight-row">
            <td class="label">Referencia de transferencia</td>
            <td class="value" style="font-weight: bold;">{{ $payment->transfer_reference }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Registrado por</td>
            <td class="value">{{ $payment->recordedBy->name }}</td>
        </tr>
    </table>
@endsection
