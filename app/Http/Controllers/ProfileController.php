<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $participationCount = $user->participations()->count() + $user->collections()->count();
        $reputation = $this->reputationFromParticipations($participationCount);

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'institution' => $user->institution,
                'department' => $user->department,
                'nickname' => $user->nickname,
                'bank_name' => $user->bank_name,
                'bank_account_number' => $user->bank_account_number,
                'bank_account_name' => $user->bank_account_name,
                'email_verified_at' => $user->email_verified_at,
            ],
            'reputation' => $reputation,
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function reputationFromParticipations(int $count): array
    {
        $tiers = [
            ['name' => 'Starter', 'level' => 1, 'min' => 0, 'next_name' => 'Rising Rep', 'next_at' => 2],
            ['name' => 'Rising Rep', 'level' => 2, 'min' => 2, 'next_name' => 'Campus Mogul', 'next_at' => 4],
            ['name' => 'Campus Mogul', 'level' => 3, 'min' => 4, 'next_name' => null, 'next_at' => null],
        ];

        $current = $tiers[0];
        foreach ($tiers as $tier) {
            if ($count >= $tier['min']) {
                $current = $tier;
            }
        }

        $nextAt = $current['next_at'];
        $progress = $nextAt
            ? (int) round((($count - $current['min']) / max(1, $nextAt - $current['min'])) * 100)
            : 100;

        return [
            'name' => $current['name'],
            'level' => $current['level'],
            'progress' => max(0, min(100, $progress)),
            'next_name' => $current['next_name'],
            'remaining' => $nextAt ? max(0, $nextAt - $count) : 0,
        ];
    }
}
