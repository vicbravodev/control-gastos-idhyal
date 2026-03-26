@extends('pdf.layout')

@section('title', 'Reporte de gastos')
@section('doc-title', 'Reporte de gastos')
@section('doc-subtitle', 'Reporte generado desde el módulo de reportes de contabilidad')

@section('content')
    @if(count($activeFilters) > 0)
    <h3 class="section-title">Filtros aplicados</h3>
    <table class="detail-grid">
        @foreach($activeFilters as $filter)
        <tr>
            <td class="value" colspan="2">{{ $filter }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <h3 class="section-title">Resumen</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Total de solicitudes</td>
            <td class="value">{{ number_format($summary['total_count']) }}</td>
        </tr>
        <tr class="highlight-row">
            <td class="label">Monto total solicitado</td>
            <td class="value amount">${{ number_format($summary['total_requested_cents'] / 100, 2) }} MXN</td>
        </tr>
        <tr class="highlight-row">
            <td class="label">Monto total aprobado</td>
            <td class="value amount">${{ number_format($summary['total_approved_cents'] / 100, 2) }} MXN</td>
        </tr>
    </table>

    <h3 class="section-title">Detalle de solicitudes ({{ $rows->count() }} registros)</h3>

    <table class="data-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Solicitante</th>
                <th>Región</th>
                <th>Concepto</th>
                <th>Estado</th>
                <th style="text-align:right">Solicitado</th>
                <th style="text-align:right">Aprobado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            <tr>
                <td>{{ $row->folio ?? '#' . $row->id }}</td>
                <td>{{ $row->user->name }}</td>
                <td>{{ $row->user->region?->name ?? '—' }}</td>
                <td>{{ $row->conceptLabel() }}</td>
                <td>
                    <span class="badge @if(in_array($row->status->value, ['approved', 'paid', 'closed'])) badge-approved @elseif(in_array($row->status->value, ['rejected', 'cancelled'])) badge-rejected @else badge-pending @endif">
                        {{ $row->status->label() }}
                    </span>
                </td>
                <td style="text-align:right; font-variant-numeric:tabular-nums">${{ number_format($row->requested_amount_cents / 100, 2) }}</td>
                <td style="text-align:right; font-variant-numeric:tabular-nums">
                    @if($row->approved_amount_cents !== null)
                        ${{ number_format($row->approved_amount_cents / 100, 2) }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $row->created_at?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
