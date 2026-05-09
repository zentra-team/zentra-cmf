<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\SendResetLinkRequest;
use App\Services\UserService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        $remember = $request->boolean('remember');

        if (!Auth::guard('admin')->attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->with('toast_error', 'Неверный email или пароль');
        }

        $user = Auth::guard('admin')->user();

        if (!$user->is_active) {
            Auth::guard('admin')->logout();

            return back()
                ->withInput($request->only('email'))
                ->with('toast_error', 'Учётная запись заблокирована');
        }

        $this->userService->touchLastLogin($user);

        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('toast_success', 'Добро пожаловать, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showForgotPassword()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.forgot-password');
    }

    public function sendResetLink(SendResetLinkRequest $request)
    {
        try {
            $status = Password::broker('admin_users')->sendResetLink(
                $request->only('email'),
            );
        } catch (\Throwable $e) {
            Log::warning('Admin password reset email failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return back()->with(
                'toast_error',
                'Отправка email невозможна. Используйте команду: php artisan admin:reset-password ' . $request->input('email'),
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('toast_warning', 'Пожалуйста, подождите перед повторным запросом');
        }

        return back()->with(
            'toast_info',
            'Если указанный email зарегистрирован в системе, вы получите письмо со ссылкой для сброса пароля',
        );
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::broker('admin_users')->reset(
            $request->validated(),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('admin.login')
                ->with('toast_success', 'Пароль успешно изменён. Войдите с новым паролем.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('toast_error', 'Ссылка для сброса пароля недействительна или устарела');
    }
}
