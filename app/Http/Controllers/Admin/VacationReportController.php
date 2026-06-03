<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VacationReports\ExportVacationReportRequest;
use App\Services\Reports\VacationReportExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class VacationReportController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        if (! $request->user()?->hasPermission('report.vacations.view')) {
            throw new AccessDeniedHttpException;
        }

        $today = CarbonImmutable::now();

        return Inertia::render('admin/reports/vacations/index', [
            'defaults' => [
                'from' => $today->startOfYear()->toDateString(),
                'to' => $today->endOfYear()->toDateString(),
            ],
        ]);
    }

    public function export(
        ExportVacationReportRequest $request,
        VacationReportExporter $exporter,
    ): StreamedResponse {
        $from = CarbonImmutable::parse($request->date('from'));
        $to = CarbonImmutable::parse($request->date('to'));

        return $exporter->download($from, $to);
    }
}
