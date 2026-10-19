<?php

use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

test('forgot password screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('an otp is emailed when the account exists', function () {
    Mail::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response->assertRedirect(route('password.otp'));
    Mail::assertSent(OtpCodeMail::class);
});

test('an error is shown when the account does not exist', function () {
    Mail::fake();

    $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

    $response->assertSessionHasErrors('email');
    Mail::assertNotSent(OtpCodeMail::class);
});

test('otp screen cannot be viewed without a pending reset', function () {
    $response = $this->get('/forgot-password/otp');

    $response->assertRedirect(route('password.request'));
});

test('an invalid otp is rejected', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $response = $this->post('/forgot-password/otp/verify', ['code' => '0000']);

    $response->assertSessionHasErrors('code');
});

test('password can be reset after a valid otp is verified', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $code = Cache::get('password-reset-otp:'.$user->id);

    $this->post('/forgot-password/otp/verify', ['code' => $code])
        ->assertRedirect(route('password.reset'));

    $response = $this->post('/reset-password', [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect($user->fresh()->password)->not->toBe($user->password);
});

test('reset password screen cannot be viewed before the otp is verified', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $response = $this->get('/reset-password');

    $response->assertRedirect(route('password.request'));
});
