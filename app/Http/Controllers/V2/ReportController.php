<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request): Response
    {
        $initialData = $this->reportService->getDashboardData([], $request->user());

        return Inertia::render('Reports/Index', [
            'initialData' => $initialData,
        ]);
    }
}
