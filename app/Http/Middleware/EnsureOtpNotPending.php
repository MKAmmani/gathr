<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpNotPending
{
    /**
     * Redirect users to OTP screens when an OTP is pending.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->session()->has('login_otp_user_id')) {
            if (! $request->routeIs('otp.login') && ! $request->routeIs('otp.login.*')) {
                return redirect()->route('otp.login');
            }
        }

        if ($request->session()->has('pending_otp_user_id')) {
            $otpReady = $request->session()->has('otp_expires_at');

            if (
                $otpReady
                && ! $request->routeIs('otp')
                && ! $request->routeIs('otp.*')
            ) {
                return redirect()->route('otp');
            }

            // Allow profile setup routes when OTP is not yet sent
            if (
                ! $otpReady
                && ! $request->routeIs('profile.show')
                && ! $request->routeIs('profile.store')
            ) {
                return redirect()->route('profile.show');
            }
        }

        return $next($request);
    }
}
