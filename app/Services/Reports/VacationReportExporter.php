<?php

namespace App\Services\Reports;

use App\Enums\VacationRequestStatus;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\VacationRequests\VacationEntitlementBalanceResolver;
use Carbon\CarbonImmutable;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VacationReportExporter
{
    public function __construct(
        private readonly VacationEntitlementBalanceResolver $balance,
    ) {}

    public function download(CarbonImmutable $from, CarbonImmutable $to): StreamedResponse
    {
        $filename = sprintf(
            'reporte-vacaciones-%s-a-%s.xlsx',
            $from->toDateString(),
            $to->toDateString(),
        );

        return new StreamedResponse(function () use ($from, $to): void {
            $writer = new XlsxWriter(new XlsxOptions);
            $writer->openToFile('php://output');

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('1A3A73');

            // Sheet 1: Personal y saldo
            $writer->getCurrentSheet()->setName('Personal');
            $writer->addRow(Row::fromValues([
                'Nombre',
                'Email',
                'Departamento',
                'Fecha de ingreso',
                'Antigüedad (años)',
                'Regla aplicada',
                'Días asignados',
                'Días consumidos',
                'Ajuste',
                'Días restantes',
            ], $headerStyle));

            $users = User::query()
                ->with('department')
                ->orderBy('name')
                ->get();

            foreach ($users as $user) {
                $b = $this->balance->resolveForUser($user);
                $writer->addRow(Row::fromValues([
                    $user->name,
                    $user->email,
                    $user->department?->name ?? '—',
                    $b['hire_date'] ?? '—',
                    $b['service_years'] ?? '—',
                    $b['rule']['name'] ?? '—',
                    $b['days_allocated'],
                    $b['days_consumed'],
                    $b['days_adjustment'],
                    $b['days_remaining'],
                ]));
            }

            // Sheet 2: Solicitudes en el rango
            $solicitudes = $writer->addNewSheetAndMakeItCurrent();
            $solicitudes->setName('Solicitudes');
            $writer->addRow(Row::fromValues([
                'Folio',
                'Solicitante',
                'Email',
                'Departamento',
                'Fecha de ingreso',
                'Estado',
                'Inicia',
                'Termina',
                'Días hábiles',
                'Creada el',
            ], $headerStyle));

            $requests = VacationRequest::query()
                ->with(['user.department'])
                ->whereDate('starts_on', '>=', $from->toDateString())
                ->whereDate('starts_on', '<=', $to->toDateString())
                ->orderBy('starts_on')
                ->get();

            foreach ($requests as $r) {
                $writer->addRow(Row::fromValues([
                    $r->folio ?? '—',
                    $r->user?->name ?? '—',
                    $r->user?->email ?? '—',
                    $r->user?->department?->name ?? '—',
                    $r->user?->hire_date?->toDateString() ?? '—',
                    $r->status instanceof VacationRequestStatus ? $r->status->label() : (string) $r->status,
                    $r->starts_on?->toDateString() ?? '—',
                    $r->ends_on?->toDateString() ?? '—',
                    $r->business_days_count,
                    $r->created_at?->toDateString() ?? '—',
                ]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
