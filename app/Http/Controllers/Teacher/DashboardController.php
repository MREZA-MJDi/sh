<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, TeacherDashboardService $dashboard): View
    {
        return view('teacher.dashboard.index', $dashboard->build($request->user()));
    }
}
