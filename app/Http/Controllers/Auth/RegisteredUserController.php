<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => 'required|string|max:32',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $request->session()->put('pending_otp_user_id', $user->id);

        return redirect()->route('profile.show');
    }

    /**
     * Display the profile setup view.
     */
    public function showProfile(Request $request): Response
    {
        $userId = $request->session()->get('pending_otp_user_id');
        $user = User::find($userId);

        return Inertia::render('Auth/profile', [
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
    }

    /**
     * Store the profile data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function storeProfile(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('pending_otp_user_id');
        $user = User::find($userId);

        $request->validate([
            'name' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
        ]);

        $user->update([
            'name' => $request->name,
            'institution' => $request->institution,
            'department' => $request->department,
            'nickname' => $request->nickname,
        ]);

        event(new Registered($user));

        $expiresAt = $this->sendOtp($user);
        $request->session()->put('otp_expires_at', $expiresAt->toIso8601String());

        return redirect()->route('otp');
    }

    private function sendOtp(User $user): \Illuminate\Support\Carbon
    {
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(10);

        Cache::put(
            'email-otp:' . $user->id,
            $code,
            $expiresAt
        );

        Mail::to($user->email)->send(new OtpCodeMail($code, 10));

        return $expiresAt;
    }
}
