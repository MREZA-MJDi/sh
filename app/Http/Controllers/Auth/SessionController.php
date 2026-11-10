<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function create(): View
    {
        $showDemoAccounts = app()->environment('local') && config('demo.enabled');

        return view('auth.login', [
            'demoAccounts' => $showDemoAccounts ? config('demo.accounts', []) : [],
            'demoPassword' => $showDemoAccounts ? config('demo.password') : null,
        ]);
    }

    public function store(
        LoginRequest $request,
        AuthenticationService $authentication
    ): RedirectResponse {
        $identifier = $request->validated('email');
        $password = $request->validated('password');
        $remember = $request->boolean('remember');

        $authenticated = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? Auth::attempt(['email' => $identifier, 'password' => $password, 'is_active' => true], $remember)
            : false;

        if (! $authenticated) {
            $matches = User::query()
                ->where('phone', $identifier)
                ->where('is_active', true)
                ->limit(2)
                ->get();

            if ($matches->count() === 1 && Hash::check($password, $matches->first()->password)) {
                Auth::login($matches->first(), $remember);
                $authenticated = true;
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'ایمیل یا موبایل و گذرواژه صحیح نیست یا حساب کاربری غیرفعال است.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $dashboardRoute = $authentication->dashboardRouteName($user);

        if ($dashboardRoute === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'برای این حساب نقش فعالی تعریف نشده است. با مدیر سامانه تماس بگیرید.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($dashboardRoute));
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function storeRegistration(
        RegisterRequest $request,
        AuthenticationService $authentication
    ): RedirectResponse {
        $student = $authentication->registerStudent($request->validated());

        Auth::login($student);
        $request->session()->regenerate();

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'حساب دانش‌آموزی شما با موفقیت ساخته شد.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'با موفقیت از حساب خارج شدید.');
    }
}
