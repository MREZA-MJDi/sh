<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboard): View
    {
        return view('admin.dashboard.index', $dashboard->build(auth()->user()));
    }
}
