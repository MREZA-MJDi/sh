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

    public function homework(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.assignments', [
            'homeworks' => $data['homeworks'],
            'student' => $request->user(),
        ]);
    }

    public function attendance(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.attendance', [
            'attendance' => $data['attendance'],
            'attendance_count' => $data['attendance_count'],
            'attendance_rate' => $data['attendance_rate'],
            'classrooms' => $data['classrooms'],
        ]);
    }

    public function results(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.results', ['results' => $data['results']]);
    }

    public function resources(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.resources', ['resources' => $data['resources']]);
    }

    public function liveClasses(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.live-classes', [
            'sessions' => $data['upcoming_sessions'],
            'classrooms' => $data['classrooms'],
        ]);
    }

    public function achievements(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.achievements', [
            'course_progress' => $data['course_progress'],
            'attendance_rate' => $data['attendance_rate'],
            'results' => $data['results'],
        ]);
    }
}
