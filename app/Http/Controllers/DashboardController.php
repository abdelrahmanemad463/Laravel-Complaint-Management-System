<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Services\DashboardStatsService;

class DashboardController extends Controller
{
    public function __invoke(DashboardFilterRequest $request, DashboardStatsService $stats)
    {
        return view('dashboard.index', $stats->build($request->validated()));
    }
}
