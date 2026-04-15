<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_LENGTH = 4;

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Auth::validate(['email' => $request->email, 'password' => $request->password])) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        // Send OTP and redirect to OTP verification page
        $expiresAt = $this->sendLoginOtp($user);
        $request->session()->put('login_otp_user_id', $user->id);
        $request->session()->put('login_otp_expires_at', $expiresAt->toIso8601String());
        $request->session()->put('login_otp_remember', $request->boolean('remember', false));

        return redirect()->route('otp.login');
    }

    /**
     * Send OTP for login verification.
     */
    private function sendLoginOtp(User $user): \Illuminate\Support\Carbon
    {
        $code = str_pad((string) random_int(0, 9999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        Cache::put(
            $this->loginOtpCacheKey($user->id),
            $code,
            $expiresAt
        );

        Mail::to($user->email)->send(new OtpCodeMail($code, self::OTP_TTL_MINUTES));

        return $expiresAt;
    }

    /**
     * Get the cache key for login OTP.
     */
    private function loginOtpCacheKey(int $userId): string
    {
        return 'login-otp:' . $userId;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
