<?php

namespace App\Http\Controllers;

use App\Services\FullReportExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Generate and download the comprehensive multi-tab Excel report for the entire portal.
     */
    public function exportFullReport(Request $request, FullReportExportService $exportService): StreamedResponse
    {
        @ini_set('max_execution_time', 300);
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $user = auth()->user();
        return $exportService->export($user);
    }
}
