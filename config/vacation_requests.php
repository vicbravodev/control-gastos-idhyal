<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Expiración de solicitudes de vacaciones en trámite
    |--------------------------------------------------------------------------
    |
    | Días máximos que una solicitud puede permanecer en estatus "submitted" o
    | "approval_in_progress" antes de que el comando programado la marque como
    | expirada. Una solicitud expirada deja de consumir días del saldo del
    | usuario, devolviéndolos al disponible.
    |
    */

    'pending_expiration_days' => max(1, (int) env('VACATION_REQUEST_PENDING_EXPIRATION_DAYS', 7)),

];
