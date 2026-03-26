@extends('pdf.layout')

@section('title', 'Recibo de aprobación de solicitud de vacaciones')
@section('doc-title', 'Recibo de aprobación de vacaciones')
@section('doc-subtitle', 'Cadena de aprobación completada satisfactoriamente')
@section('folio', $vacationRequest->folio ?? '—')

@section('content')
    <h3 class="section-title">Datos de la solicitud</h3>

    <table class="detail-grid">
        <tr>
            <td class="label">Folio</td>
            <td class="value">{{ $vacationRequest->folio ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Estado actual</td>
            <td class="value"><span class="badge badge-approved">{{ $vacationRequest->status->label() }}</span></td>
        </tr>
        <tr>
            <td class="label">Solicitante</td>
            <td class="value">{{ $vacationRequest->user->name }}</td>
        </tr>
    </table>

    <h3 class="section-title">Período de vacaciones</h3>

    <div style="display: table; width: 100%;">
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card">
                <p class="amount-label">Fecha inicio</p>
                <p class="amount-value" style="font-size: 12pt;">{{ optional($vacationRequest->starts_on)?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>
        <div style="display: table-cell; width: 3%;"></div>
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card" style="border-left-color: #1a3a73;">
                <p class="amount-label">Fecha fin</p>
                <p class="amount-value" style="font-size: 12pt;">{{ optional($vacationRequest->ends_on)?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>
        <div style="display: table-cell; width: 3%;"></div>
        <div style="display: table-cell; width: 31%; vertical-align: top;">
            <div class="amount-card" style="border-left-color: #c09940;">
                <p class="amount-label">Días hábiles</p>
                <p class="amount-value" style="font-size: 12pt;">{{ $vacationRequest->business_days_count !== null ? number_format($vacationRequest->business_days_count) : '—' }}</p>
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
            @foreach($vacationRequest->approvals as $a)
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
