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
        // Never intercept guest-facing, webhook, or auth routes.
        // Intercepting login/logout would trap users who have a stale pending_otp_user_id
        // in their session (from an abandoned registration) inside an infinite redirect loop:
        // EnsureOtpNotPending → /otp (guest-only) → guest middleware → /dashboard → repeat.
        if (
            $request->routeIs('collections.guest*') ||
            $request->routeIs('webhooks.*') ||
            $request->routeIs('api.*') ||
            $request->routeIs('login') ||
            $request->routeIs('logout')
        ) {
            return $next($request);
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
