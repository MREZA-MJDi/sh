<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();
        $data = $dashboard->build($user);
        $data['myConsultations'] = Consultation::query()
            ->where('requester_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('teacher.dashboard.index', $data);
    }
}
