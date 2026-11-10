<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Services\GuardianDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, GuardianDashboardService $dashboard): View
    {
        return view('guardian.dashboard.index', $dashboard->build($request->user()));
    }
}
