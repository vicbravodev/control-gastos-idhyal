<?php

namespace App\Services\Notifications;

final class InAppNotificationPresenter
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, body_lines: list<string>, expense_request_id: int|null, vacation_request_id: int|null}
     */
    public static function present(array $data): array
    {
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : '';

        $folio = self::stringOrNull($data['folio'] ?? null);
        $expenseRequestId = self::intOrNull($data['expense_request_id'] ?? null);
        $vacationRequestId = self::intOrNull($data['vacation_request_id'] ?? null);

        return match ($type) {
            'expense_request.submitted_for_approval' => [
                'title' => __('Nueva solicitud pendiente de aprobación'),
                'body_lines' => self::filterLines([
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    self::stringOrNull($data['concept'] ?? null),
                    isset($data['requester_name']) && is_string($data['requester_name'])
                        ? __('Solicitante: :name', ['name' => $data['requester_name']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.approval_progress' => [
                'title' => __('Actualización en tu solicitud de gasto'),
                'body_lines' => self::filterLines([
                    isset($data['approver_name']) && is_string($data['approver_name'])
                        ? __(':name registró una aprobación.', ['name' => $data['approver_name']])
                        : null,
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['remaining_groups']) && is_int($data['remaining_groups'])
                        ? __('Grupos de aprobación pendientes: :n', ['n' => $data['remaining_groups']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.fully_approved' => [
                'title' => __('Solicitud de gasto aprobada'),
                'body_lines' => self::filterLines([
                    __('Tu solicitud completó todas las aprobaciones.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['last_approver_name']) && is_string($data['last_approver_name'])
                        ? __('Última aprobación: :name.', ['name' => $data['last_approver_name']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.rejected' => [
                'title' => __('Solicitud de gasto rechazada'),
                'body_lines' => self::filterLines([
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['note']) && is_string($data['note'])
                        ? __('Motivo: :note', ['note' => $data['note']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.paid' => [
                'title' => __('Solicitud de gasto pagada'),
                'body_lines' => self::filterLines([
                    __('Contabilidad registró el pago de tu solicitud.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['amount_cents']) && is_int($data['amount_cents'])
                        ? __('Monto pagado: :amount MXN', ['amount' => self::formatMxFromCents($data['amount_cents'])])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.expense_report_submitted' => [
                'title' => __('Comprobación de gasto por revisar'),
                'body_lines' => self::filterLines([
                    __('Un solicitante envió la comprobación para revisión.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.expense_report_approved' => [
                'title' => __('Comprobación aprobada'),
                'body_lines' => self::filterLines([
                    __('Contabilidad aprobó tu comprobación de gasto.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['difference_cents']) && is_int($data['difference_cents'])
                        ? __('Diferencia (centavos): :c', ['c' => (string) $data['difference_cents']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.expense_report_rejected' => [
                'title' => __('Comprobación rechazada'),
                'body_lines' => self::filterLines([
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['note']) && is_string($data['note'])
                        ? __('Motivo: :note', ['note' => $data['note']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.settlement_liquidated' => [
                'title' => __('Liquidación registrada'),
                'body_lines' => self::filterLines([
                    __('Se registró la evidencia de liquidación del balance.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.settlement_closed' => [
                'title' => __('Balance cerrado'),
                'body_lines' => self::filterLines([
                    __('El balance de comprobación quedó cerrado.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'expense_request.settlement_pending_reminder' => [
                'title' => __('Recordatorio: balance pendiente'),
                'body_lines' => self::filterLines([
                    __('Hay un balance pendiente de liquidar para esta solicitud.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['settlement_status']) && is_string($data['settlement_status'])
                        ? __('Estado del balance: :s', ['s' => $data['settlement_status']])
                        : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => null,
            ],
            'vacation_request.submitted_for_approval' => [
                'title' => __('Nueva solicitud de vacaciones pendiente de aprobación'),
                'body_lines' => self::filterLines([
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    self::vacationPeriodLine($data),
                    isset($data['requester_name']) && is_string($data['requester_name'])
                        ? __('Solicitante: :name', ['name' => $data['requester_name']])
                        : null,
                ]),
                'expense_request_id' => null,
                'vacation_request_id' => $vacationRequestId,
            ],
            'vacation_request.approval_progress' => [
                'title' => __('Actualización en tu solicitud de vacaciones'),
                'body_lines' => self::filterLines([
                    isset($data['approver_name']) && is_string($data['approver_name'])
                        ? __(':name registró una aprobación.', ['name' => $data['approver_name']])
                        : null,
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['remaining_groups']) && is_int($data['remaining_groups'])
                        ? __('Grupos de aprobación pendientes: :n', ['n' => $data['remaining_groups']])
                        : null,
                ]),
                'expense_request_id' => null,
                'vacation_request_id' => $vacationRequestId,
            ],
            'vacation_request.fully_approved' => [
                'title' => __('Solicitud de vacaciones aprobada'),
                'body_lines' => self::filterLines([
                    __('Tu solicitud de vacaciones completó todas las aprobaciones.'),
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['last_approver_name']) && is_string($data['last_approver_name'])
                        ? __('Última aprobación: :name.', ['name' => $data['last_approver_name']])
                        : null,
                ]),
                'expense_request_id' => null,
                'vacation_request_id' => $vacationRequestId,
            ],
            'vacation_request.rejected' => [
                'title' => __('Solicitud de vacaciones rechazada'),
                'body_lines' => self::filterLines([
                    $folio !== null ? __('Folio: :folio', ['folio' => $folio]) : null,
                    isset($data['note']) && is_string($data['note'])
                        ? __('Motivo: :note', ['note' => $data['note']])
                        : null,
                ]),
                'expense_request_id' => null,
                'vacation_request_id' => $vacationRequestId,
            ],
            default => [
                'title' => __('Notificación'),
                'body_lines' => self::filterLines([
                    $type !== '' ? __('Tipo: :type', ['type' => $type]) : null,
                ]),
                'expense_request_id' => $expenseRequestId,
                'vacation_request_id' => $vacationRequestId,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function vacationPeriodLine(array $data): ?string
    {
        $start = self::stringOrNull($data['starts_on'] ?? null);
        $end = self::stringOrNull($data['ends_on'] ?? null);

        if ($start === null || $end === null) {
            return null;
        }

        return __('Periodo: :start — :end', ['start' => $start, 'end' => $end]);
    }

    private static function formatMxFromCents(int $cents): string
    {
        $amount = $cents / 100;

        return number_format($amount, 2, '.', ',');
    }

    /**
     * @param  list<string|null>  $lines
     * @return list<string>
     */
    private static function filterLines(array $lines): array
    {
        return array_values(array_filter($lines, static fn (?string $line): bool => $line !== null && $line !== ''));
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        return null;
    }
}
