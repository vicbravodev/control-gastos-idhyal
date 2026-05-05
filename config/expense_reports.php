<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validación semántica del XML de comprobación (CFDI SAT México)
    |--------------------------------------------------------------------------
    |
    | Además de mimes:xml, el sistema puede exigir que el archivo sea un
    | Comprobante Fiscal Digital por Internet en los namespaces oficiales
    | del SAT (CFDI 3.3 / 4.0) y, opcionalmente, que el atributo Total
    | coincida con el monto comprobado declarado en el formulario.
    |
    */

    'cfdi' => [

        'validate_structure' => (bool) env('EXPENSE_REPORT_CFDI_VALIDATE_STRUCTURE', true),

        'require_total_matches_reported' => (bool) env('EXPENSE_REPORT_CFDI_REQUIRE_TOTAL_MATCH', true),

        'total_match_tolerance_cents' => max(0, (int) env('EXPENSE_REPORT_CFDI_TOTAL_TOLERANCE_CENTS', 2)),

        'require_moneda_mxn' => (bool) env('EXPENSE_REPORT_CFDI_REQUIRE_MONEDA_MXN', true),

        /*
        |----------------------------------------------------------------------
        | Validación del RFC del receptor
        |----------------------------------------------------------------------
        |
        | Si se define, el atributo `Receptor.Rfc` del CFDI debe coincidir
        | exactamente con este valor. Atrapa el error común de subir una
        | factura emitida a nombre personal en lugar de a la empresa.
        | Dejarlo en null deshabilita la validación.
        |
        */

        'expected_receiver_rfc' => env('EXPENSE_REPORT_CFDI_EXPECTED_RECEIVER_RFC'),

        /*
        |----------------------------------------------------------------------
        | Validación del rango de fechas del comprobante
        |----------------------------------------------------------------------
        |
        | Rechaza facturas más viejas que `max_age_days` o emitidas con más de
        | `allow_future_days` de adelanto. Sirve para evitar comprobaciones
        | extemporáneas o facturas adelantadas.
        |
        */

        'validate_fecha_range' => (bool) env('EXPENSE_REPORT_CFDI_VALIDATE_FECHA_RANGE', true),

        'max_age_days' => max(0, (int) env('EXPENSE_REPORT_CFDI_MAX_AGE_DAYS', 365)),

        'allow_future_days' => max(0, (int) env('EXPENSE_REPORT_CFDI_ALLOW_FUTURE_DAYS', 1)),

    ],

];
