<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());
        $data['student'] = $request->user();

        return view('student.dashboard', $data);
    }
}
