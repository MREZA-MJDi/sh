<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('teacher') && ! $user->hasRole('admin')) {
            return redirect()->route('teacher.dashboard');
        }

        return view('admin.dashboard.index', $dashboard->build($user));
    }
}
