<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Inertia\Inertia;
use Inertia\Response;

class OtpController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_LENGTH = 4;

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (!$user) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/Otp', [
            'email' => $user->email,
            'expiresAt' => $request->session()->get('otp_expires_at'),
            'source' => 'register',
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:' . self::OTP_LENGTH],
        ]);

        $user = $this->pendingUser($request);
        if (!$user) {
            return redirect()->route('register');
        }

        $cached = Cache::get($this->otpCacheKey($user->id));
        if (!$cached || $cached !== $request->code) {
            return back()->withErrors([
                'code' => 'Invalid or expired code. Please try again.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        Cache::forget($this->otpCacheKey($user->id));
        $request->session()->forget('pending_otp_user_id');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (!$user) {
            return redirect()->route('register');
        }

        $expiresAt = $this->sendOtp($user);
        $request->session()->put('otp_expires_at', $expiresAt->toIso8601String());

        return back();
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_otp_user_id');
        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    private function sendOtp(User $user): \Illuminate\Support\Carbon
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
        return 'email-otp:' . $userId;
    }
}
