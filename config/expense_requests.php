<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Adjuntos en solicitud (antes del pago)
    |--------------------------------------------------------------------------
    |
    | Archivos opcionales asociados a la solicitud (no son la comprobación
    | post-pago). Límites alineados de forma aproximada a evidencia de pago.
    |
    */

    'submission_attachments_max_count' => 10,

    'submission_attachments_max_kb' => 10240,

    'submission_attachments_mime_extensions' => 'pdf,jpg,jpeg,png,webp',

];
