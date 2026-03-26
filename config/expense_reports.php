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

    ],

];
