<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetOtpController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_LENGTH = 4;

    /**
     * Display the "forgot password" email form.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Look up the account and email it a password reset OTP.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->withErrors([
                'email' => "We can't find an account with that email address.",
            ])->onlyInput('email');
        }

        try {
            $expiresAt = $this->sendOtp($user);
        } catch (TransportExceptionInterface $e) {
            report($e);

            return back()->withErrors([
                'email' => 'We could not send the verification code right now. Please try again later.',
            ])->onlyInput('email');
        }

        $request->session()->put('password_reset_email', $user->email);
        $request->session()->put('password_reset_otp_expires_at', $expiresAt->toIso8601String());
        $request->session()->forget('password_reset_verified');

        return redirect()->route('password.otp');
    }

    /**
     * Display the OTP verification view.
     */
    public function showOtp(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/Otp', [
            'email' => $email,
            'expiresAt' => $request->session()->get('password_reset_otp_expires_at'),
            'source' => 'reset-password',
        ]);
    }

    /**
     * Verify the submitted OTP code.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:'.self::OTP_LENGTH],
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('password.request');
        }

        $cached = Cache::get($this->otpCacheKey($user->id));

        if (! $cached || $cached !== $request->code) {
            return back()->withErrors([
                'code' => 'Invalid or expired code. Please try again.',
            ]);
        }

        Cache::forget($this->otpCacheKey($user->id));
        $request->session()->put('password_reset_verified', true);

        return redirect()->route('password.reset');
    }

    /**
     * Resend a fresh OTP code to the pending email address.
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('password.request');
        }

        try {
            $expiresAt = $this->sendOtp($user);
        } catch (TransportExceptionInterface $e) {
            report($e);

            return back()->withErrors([
                'code' => 'We could not resend the verification code right now. Please try again later.',
            ]);
        }

        $request->session()->put('password_reset_otp_expires_at', $expiresAt->toIso8601String());

        return back();
    }

    /**
     * Display the new password form, once the OTP has been verified.
     */
    public function createPassword(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email || ! $request->session()->get('password_reset_verified')) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/ResetPassword', [
            'email' => $email,
        ]);
    }

    /**
     * Update the user's password.
     */
    public function storePassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email || ! $request->session()->get('password_reset_verified')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return redirect()->route('password.request');
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        $request->session()->forget([
            'password_reset_email',
            'password_reset_otp_expires_at',
            'password_reset_verified',
        ]);

        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
    }

    private function pendingUser(Request $request): ?User
    {
        $email = $request->session()->get('password_reset_email');

        return $email ? User::where('email', $email)->first() : null;
    }

    private function sendOtp(User $user): Carbon
    {
        $code = str_pad((string) random_int(0, 9999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        Cache::put($this->otpCacheKey($user->id), $code, $expiresAt);

        try {
            Mail::to($user->email)->send(new OtpCodeMail($code, self::OTP_TTL_MINUTES));
        } catch (TransportExceptionInterface $e) {
            Cache::forget($this->otpCacheKey($user->id));
            throw $e;
        }

        return $expiresAt;
    }

    private function otpCacheKey(int $userId): string
    {
        return 'password-reset-otp:'.$userId;
    }
}
