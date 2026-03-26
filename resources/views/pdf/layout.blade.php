<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        .page-wrapper {
            padding: 0 40px 30px 40px;
        }

        /* ── Header ── */
        .header {
            background-color: #1a3a73;
            color: #ffffff;
            padding: 20px 40px 0 40px;
            position: relative;
        }

        .header-inner {
            display: table;
            width: 100%;
        }

        .header-logo {
            display: table-cell;
            vertical-align: middle;
            width: 50%;
        }

        .header-meta {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }

        .brand-name {
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: 2px;
            color: #ffffff;
            margin: 0;
        }

        .brand-sub {
            font-size: 8pt;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
            letter-spacing: 0.5px;
        }

        .header-meta p {
            margin: 0;
            font-size: 8pt;
            color: rgba(255, 255, 255, 0.7);
        }

        .header-meta .folio-value {
            font-size: 11pt;
            font-weight: bold;
            color: #ffffff;
        }

        .gold-stripe {
            height: 4px;
            background-color: #c09940;
        }

        .header-bottom {
            background-color: #1a3a73;
            padding: 10px 40px 15px 40px;
        }

        /* ── Document Title ── */
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            color: #ffffff;
            margin: 0;
        }

        .doc-subtitle {
            font-size: 8pt;
            color: rgba(255, 255, 255, 0.6);
            margin: 4px 0 0 0;
        }

        /* ── Section titles ── */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1a3a73;
            margin: 24px 0 8px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #c09940;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Detail grid (key-value pairs) ── */
        .detail-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .detail-grid td {
            padding: 8px 12px;
            border-bottom: 1px solid #e8e8ee;
            vertical-align: top;
        }

        .detail-grid .label {
            width: 38%;
            font-weight: bold;
            color: #1a3a73;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .detail-grid .value {
            color: #2a2a3e;
        }

        .detail-grid tr:last-child td {
            border-bottom: none;
        }

        .detail-grid .highlight-row td {
            background-color: #f0f4fb;
        }

        .detail-grid .amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: bold;
        }

        /* ── Data tables ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9pt;
        }

        .data-table thead th {
            background-color: #1a3a73;
            color: #ffffff;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-table thead th:first-child {
            border-radius: 4px 0 0 0;
        }

        .data-table thead th:last-child {
            border-radius: 0 4px 0 0;
        }

        .data-table tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #e8e8ee;
            color: #2a2a3e;
        }

        .data-table tbody tr:nth-child(even) td {
            background-color: #f8f9fc;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 2px solid #c09940;
        }

        /* ── Status badges ── */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-default {
            background-color: #f0f4fb;
            color: #1a3a73;
        }

        /* ── Amounts card ── */
        .amount-card {
            background-color: #f0f4fb;
            border: 1px solid #d0d8ea;
            border-left: 4px solid #c09940;
            padding: 12px 16px;
            margin-top: 12px;
        }

        .amount-card .amount-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
        }

        .amount-card .amount-value {
            font-size: 16pt;
            font-weight: bold;
            color: #1a3a73;
            margin: 2px 0 0 0;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 2px solid #c09940;
        }

        .footer-inner {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            vertical-align: top;
            width: 60%;
        }

        .footer-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 40%;
        }

        .footer p {
            margin: 0;
            font-size: 7.5pt;
            color: #888;
        }

        .footer .footer-brand {
            font-weight: bold;
            color: #1a3a73;
            font-size: 8pt;
        }

        /* ── Watermark-style background label ── */
        .confidential {
            text-align: center;
            margin-top: 20px;
            font-size: 7pt;
            color: #aaa;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    {{-- ── Header ── --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-logo">
                <p class="brand-name">IDHYAL</p>
                <p class="brand-sub">Control de gastos</p>
            </div>
            <div class="header-meta">
                @hasSection('folio')
                    <p>Folio</p>
                    <p class="folio-value">@yield('folio')</p>
                @endif
            </div>
        </div>
    </div>
    <div class="header-bottom">
        <p class="doc-title">@yield('doc-title')</p>
        <p class="doc-subtitle">@yield('doc-subtitle')</p>
    </div>
    <div class="gold-stripe"></div>

    {{-- ── Content ── --}}
    <div class="page-wrapper">
        @yield('content')

        {{-- ── Footer ── --}}
        <div class="footer">
            <div class="footer-inner">
                <div class="footer-left">
                    <p class="footer-brand">IDHYAL &mdash; Control de gastos</p>
                    <p>Documento generado automáticamente. No requiere firma.</p>
                </div>
                <div class="footer-right">
                    <p>Generado: {{ $generatedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <p class="confidential">Documento interno &bull; Uso exclusivo para auditoría</p>
    </div>
</body>
</html>
