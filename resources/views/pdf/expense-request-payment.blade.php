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

    @if(count($cfdiSummaries) > 0)
        @php
            $sumSubtotal = array_sum(array_column($cfdiSummaries, 'subtotal_cents'));
            $sumTraslados = array_sum(array_column($cfdiSummaries, 'traslados_cents'));
            $sumRetenciones = array_sum(array_column($cfdiSummaries, 'retenciones_cents'));
            $sumLocales = array_sum(array_column($cfdiSummaries, 'locales_cents'));
            $sumTotal = array_sum(array_column($cfdiSummaries, 'total_cents'));
        @endphp

        <h3 class="section-title">Comprobantes fiscales (CFDI)</h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Emisor</th>
                    <th>Fecha</th>
                    <th style="text-align: right;">Subtotal</th>
                    <th style="text-align: right;">IVA / Traslados</th>
                    <th style="text-align: right;">Retenciones</th>
                    <th style="text-align: right;">Imp. locales</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cfdiSummaries as $s)
                    <tr>
                        <td>{{ $s['folio'] }}</td>
                        <td>{{ $s['emisor'] }}</td>
                        <td>{{ $s['fecha'] ?? '—' }}</td>
                        <td style="text-align: right;">${{ number_format($s['subtotal_cents'] / 100, 2) }}</td>
                        <td style="text-align: right;">${{ number_format($s['traslados_cents'] / 100, 2) }}</td>
                        <td style="text-align: right;">${{ number_format($s['retenciones_cents'] / 100, 2) }}</td>
                        <td style="text-align: right;">${{ number_format($s['locales_cents'] / 100, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($s['total_cents'] / 100, 2) }}</td>
                    </tr>
                @endforeach
                @if(count($cfdiSummaries) > 1)
                    <tr style="background-color: #f0f4fb;">
                        <td colspan="3" style="font-weight: bold; color: #1a3a73;">TOTAL GENERAL</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($sumSubtotal / 100, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($sumTraslados / 100, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($sumRetenciones / 100, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($sumLocales / 100, 2) }}</td>
                        <td style="text-align: right; font-weight: bold; color: #1a3a73;">${{ number_format($sumTotal / 100, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif
@endsection
