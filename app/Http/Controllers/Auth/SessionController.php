<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\LegacyWordPressPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.login', $this->loginViewData('login', $request));
    }

    private function loginViewData(string $activeTab, Request $request): array
    {
        $demoPassword = config('demo.password');
        $showDemoAccounts = app()->environment(['local', 'testing'])
            && config('demo.enabled')
            && config('demo.login_cards_enabled')
            && is_string($demoPassword)
            && mb_strlen($demoPassword) >= 12;

        return [
            'activeTab' => $activeTab,
            'returnCourse' => $this->findFreeCourse($request->old('return_course', $request->query('course'))),
            'demoAccounts' => $showDemoAccounts ? config('demo.accounts', []) : [],
            'demoPassword' => $showDemoAccounts ? $demoPassword : null,
        ];
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

            if ($matches->count() === 1) {
                $candidate = $matches->first();
                $legacyPasswords = app(LegacyWordPressPassword::class);
                $storedPassword = (string) $candidate->password;
                $isLegacyHash = $legacyPasswords->isLegacyHash($storedPassword);
                $validPassword = $isLegacyHash
                    ? $legacyPasswords->verify($password, $storedPassword)
                    : Hash::check($password, $storedPassword);

                if ($validPassword) {
                    // Rehash legacy password formats on first successful mobile login.
                    if ($isLegacyHash) {
                        $candidate->forceFill(['password' => $password])->save();
                    }

                    Auth::login($candidate, $remember);
                    $authenticated = true;
                }
            }
        }

        if (! $authenticated) {
            $legacyMatches = User::query()
                ->where('legacy_login', $identifier)
                ->where('is_active', true)
                ->limit(2)
                ->get();

            if ($legacyMatches->count() === 1) {
                $candidate = $legacyMatches->first();
                $legacyPasswords = app(LegacyWordPressPassword::class);
                $storedPassword = (string) $candidate->password;
                $isLegacyHash = $legacyPasswords->isLegacyHash($storedPassword);
                $validPassword = $isLegacyHash
                    ? $legacyPasswords->verify($password, $storedPassword)
                    : Hash::check($password, $storedPassword);

                if ($validPassword) {
                    if ($isLegacyHash) {
                        $candidate->forceFill(['password' => $password])->save();
                    }

                    Auth::login($candidate, $remember);
                    $authenticated = true;
                }
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

        if ($dashboardRoute === 'student.dashboard' && ($course = $this->findFreeCourse($request->input('return_course')))) {
            return redirect()->route('public.courses.show', $course->slug);
        }

        // Ignore stale intended URLs from another role's protected workspace.
        return redirect()->route($dashboardRoute);
    }

    public function register(Request $request): View
    {
        return view('auth.login', $this->loginViewData('register', $request));
    }

    public function storeRegistration(
        RegisterRequest $request,
        AuthenticationService $authentication
    ): RedirectResponse {
        $data = $request->validated();
        $isParent = ($data['account_type'] ?? 'student') === 'parent';
        $user = $isParent ? $authentication->registerParent($data) : $authentication->registerStudent($data);

        Auth::login($user);
        $request->session()->regenerate();

        if ($isParent) {
            return redirect()->route('guardian.dashboard')
                ->with('status', 'حساب ولی ساخته شد. برای اتصال پرونده دانش‌آموز، با پشتیبانی شیخان تماس بگیرید.');
        }

        $course = $this->findFreeCourse($request->input('return_course'));

        return ($course ? redirect()->route('public.courses.show', $course->slug) : redirect()->route('student.dashboard'))
            ->with('status', 'حساب دانش‌آموزی شما با موفقیت ساخته شد.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->forget('demo_login_cards_unlocked');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'با موفقیت از حساب خارج شدید.');
    }

    private function findFreeCourse(mixed $slug): ?Course
    {
        if (! is_string($slug) || blank($slug) || mb_strlen($slug) > 190) {
            return null;
        }

        return Course::query()->where('slug', $slug)->where('type', 'free')->where('status', 'published')
            ->first(['id', 'slug', 'title']);
    }
}
