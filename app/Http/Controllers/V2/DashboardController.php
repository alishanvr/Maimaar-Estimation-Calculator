<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Estimation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $query = $user->isAdmin()
            ? Estimation::query()
            : $user->estimations();

        return Inertia::render('Dashboard', [
            'stats' => [
                'total' => (clone $query)->count(),
                'draft' => (clone $query)->where('status', 'draft')->count(),
                'calculated' => (clone $query)->where('status', 'calculated')->count(),
                'finalized' => (clone $query)->where('status', 'finalized')->count(),
            ],
        ]);
    }
}
