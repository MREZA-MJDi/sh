<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
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
    public function create(): View
    {
        return view('auth.login', $this->loginViewData('login'));
    }

    /**
     * Use one centered screen for both sign-in and student registration.
     * Demo credentials are direct in local/testing and require a private session unlock in production.
     */
    private function loginViewData(string $activeTab): array
    {
        $demoPassword = config('demo.password');
        $demoConfigured = (bool) config('demo.enabled')
            && (bool) config('demo.login_cards_enabled')
            && is_string($demoPassword)
            && mb_strlen($demoPassword) >= 12;

        $isRestrictedEnvironment = app()->environment(['local', 'testing']);
        $productionUnlockConfigured = app()->environment('production')
            && is_string(config('demo.login_cards_key'))
            && mb_strlen((string) config('demo.login_cards_key')) >= 32;
        $productionSessionUnlocked = session('demo_login_cards_unlocked') === true;

        // Never expose reusable credentials to every visitor of a public production login page.
        // Production users must first unlock the cards with a separate secret stored in .env.
        $showDemoAccounts = $demoConfigured
            && ($isRestrictedEnvironment || ($productionUnlockConfigured && $productionSessionUnlocked));

        return [
            'activeTab' => $activeTab,
            'demoAccounts' => $showDemoAccounts ? config('demo.accounts', []) : [],
            'demoPassword' => $showDemoAccounts ? $demoPassword : null,
            'showDemoAccessForm' => $demoConfigured
                && $productionUnlockConfigured
                && ! $productionSessionUnlocked,
        ];
    }

    public function unlockDemoAccounts(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('production'), 404);
        abort_unless(
            (bool) config('demo.enabled')
                && (bool) config('demo.login_cards_enabled')
                && is_string(config('demo.password'))
                && mb_strlen((string) config('demo.password')) >= 12,
            404
        );

        $validated = $request->validate([
            'access_key' => ['required', 'string', 'min:32', 'max:255'],
        ]);

        $configuredKey = (string) config('demo.login_cards_key');

        if (strlen($configuredKey) < 32 || ! hash_equals($configuredKey, $validated['access_key'])) {
            return back()->withErrors([
                'access_key' => 'کلید دسترسی معتبر نیست.',
            ]);
        }

        $request->session()->put('demo_login_cards_unlocked', true);

        return redirect()->route('login')->with('status', 'کارت‌های ورود آزمایشی برای این نشست فعال شدند.');
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

        // Ignore stale intended URLs from another role's protected workspace.
        return redirect()->route($dashboardRoute);
    }

    public function register(): View
    {
        return view('auth.login', $this->loginViewData('register'));
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

        $request->session()->forget('demo_login_cards_unlocked');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'با موفقیت از حساب خارج شدید.');
    }
}
