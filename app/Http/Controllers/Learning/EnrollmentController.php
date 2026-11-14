<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Learning\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class EnrollmentController extends Controller
{
    public function __invoke(Request $request, string $slug, EnrollmentService $service)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();
        $user = $request->user();

        if ($course->isFree()) {
            $service->enrollFree($user, $course);

            return redirect()->route('courses.show', $slug)->with('status', 'ثبت‌نام شما انجام شد.');
        }

        $enrollment = $service->startPaid($user, $course);

        if ($enrollment->isActive()) {
            return redirect()->route('courses.show', $slug);
        }

        $route = config('learning.payment_route');

        if (! Route::has($route)) {
            return back()->withErrors(['payment' => 'درگاه پرداخت هنوز به سامانه متصل نشده است.']);
        }

        return redirect()->route($route, $enrollment);
    }
}
