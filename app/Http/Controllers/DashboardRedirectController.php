<?php

namespace App\Http\Controllers;

use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DashboardRedirectController extends Controller
{
    public function __invoke(
        Request $request,
        AuthenticationService $authentication
    ): RedirectResponse {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $dashboardRoute = $authentication->dashboardRouteName($user);

        abort_if($dashboardRoute === null, 403, 'برای حساب کاربری شما داشبورد فعالی تعریف نشده است.');

        return redirect()->route($dashboardRoute);
    }
}
