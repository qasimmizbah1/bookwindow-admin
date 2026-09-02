<?php

namespace App\Http\Controllers;

use App\Exports\AnalyticsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsExportController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        return Excel::download(new AnalyticsExport(), 'analytics-report.xlsx');
    }
}