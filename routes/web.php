<?php

use App\Http\Controllers\Api\AccountVerificationController;
use App\Http\Controllers\Collection\CollectionController;
use App\Http\Controllers\Collection\CollectionIndexController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestCollectionController;
use App\Http\Controllers\GuestPaymentController;
use App\Http\Controllers\GuestReminderController;
use App\Http\Controllers\Payment\WithdrawController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Collection creation routes
Route::middleware('auth')->group(function () {
    Route::get('/collections', [CollectionIndexController::class, 'index'])
        ->name('collections.index');
    Route::get('/collections/{collection}', [CollectionController::class, 'show'])
        ->name('collections.show');
    Route::get('/collections/create/page1', [CollectionController::class, 'createPage1'])
        ->name('collections.create.page1');
    Route::post('/collections/create/page1', [CollectionController::class, 'storePage1'])
        ->name('collections.create.page1.store');
    Route::get('/collections/create/page2', [CollectionController::class, 'createPage2'])
        ->name('collections.create.page2');
    Route::post('/collections/create', [CollectionController::class, 'store'])
        ->name('collections.store');
    Route::get('/collections/{collection}/live', [CollectionController::class, 'live'])
        ->name('collections.live');
    Route::get('/collections/{collection}/edit', [CollectionController::class, 'editPage1'])
        ->name('collections.edit.page1');
    Route::put('/collections/{collection}/edit', [CollectionController::class, 'updatePage1'])
        ->name('collections.edit.page1.update');
    Route::get('/collections/{collection}/edit/page2', [CollectionController::class, 'editPage2'])
        ->name('collections.edit.page2');
    Route::put('/collections/{collection}', [CollectionController::class, 'update'])
        ->name('collections.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/settings', function () {
        $user = request()->user();
        $participationCount = $user->participations()->count() + $user->collections()->count();
        $tiers = [
            ['name' => 'Starter', 'level' => 1, 'min' => 0],
            ['name' => 'Rising Rep', 'level' => 2, 'min' => 2],
            ['name' => 'Campus Mogul', 'level' => 3, 'min' => 4],
        ];
        $current = $tiers[0];
        foreach ($tiers as $tier) {
            if ($participationCount >= $tier['min']) $current = $tier;
        }
        return \Inertia\Inertia::render('Settings', [
            'user' => ['name' => $user->name, 'email' => $user->email],
            'reputation' => ['name' => $current['name'], 'level' => $current['level'], 'progress' => 0, 'next_name' => null, 'remaining' => 0],
        ]);
    })->name('settings');
});

// Payment/Withdrawal routes
Route::middleware('auth')->group(function () {
    Route::get('/collections/{collection}/withdraw', [WithdrawController::class, 'show'])
        ->name('collections.withdraw');
    Route::post('/collections/{collection}/withdraw', [WithdrawController::class, 'store'])
        ->name('collections.withdraw.store');
    Route::post('/collections/{collection}/extend-deadline', [WithdrawController::class, 'extendDeadline'])
        ->name('collections.extend-deadline');
    Route::get('/collections/{collection}/remainder', [WithdrawController::class, 'remainder'])
        ->name('collections.remainder');
    Route::post('/collections/{collection}/send-reminder', [WithdrawController::class, 'sendReminder'])
        ->name('collections.send-reminder');
    Route::post('/update-bank', [WithdrawController::class, 'updateBank'])
        ->name('update-bank');
});

// API routes
Route::middleware('auth')->group(function () {
    Route::post('/api/verify-account', [AccountVerificationController::class, 'verify']);
    Route::get('/api/banks', [AccountVerificationController::class, 'getBanks']);
});

// Guest collection route (no auth required)
Route::get('/c/{slug}', [GuestCollectionController::class, 'show'])
    ->name('collections.guest');

// Guest payment routes (no auth required)
Route::get('/c/{slug}/pay', [GuestPaymentController::class, 'show'])
    ->name('collections.guest.pay');
Route::get('/c/{slug}/pay/method', [GuestPaymentController::class, 'showMethod'])
    ->name('collections.guest.pay.method');
Route::post('/c/{slug}/pay/initiate', [GuestPaymentController::class, 'initiatePayment'])
    ->name('collections.guest.pay.initiate');
Route::get('/c/{slug}/pay/callback', [GuestPaymentController::class, 'handleMonnifyCallback'])
    ->name('collections.guest.pay.callback');
Route::get('/c/{slug}/receipt/{paymentRef}', [GuestPaymentController::class, 'showReceipt'])
    ->name('collections.guest.receipt');

// Monnify webhook (no CSRF protection)
Route::post('/webhooks/monnify', [GuestPaymentController::class, 'handleMonnifyWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhooks.monnify');

// Guest reminder routes (no auth required)
Route::get('/c/{slug}/reminder', [GuestReminderController::class, 'show'])
    ->name('collections.guest.reminder');
Route::post('/c/{slug}/reminder', [GuestReminderController::class, 'submit'])
    ->name('collections.guest.reminder.submit');

// Admin: View pending reminders (for testing)
Route::get('/reminders', function () {
    $reminders = \App\Models\Reminder::with(['collection:id,name'])
        ->orderByDesc('created_at')
        ->get()
        ->map(function ($reminder) {
            return [
                'id' => $reminder->id,
                'email' => $reminder->email,
                'reminder_type' => $reminder->reminder_type,
                'scheduled_at' => $reminder->scheduled_at->format('Y-m-d H:i:s'),
                'is_sent' => $reminder->is_sent,
                'sent_at' => $reminder->sent_at?->format('Y-m-d H:i:s'),
                'collection_name' => $reminder->collection->name ?? 'N/A',
                'created_at' => $reminder->created_at->format('Y-m-d H:i:s'),
            ];
        });

    return response()->json([
        'total' => $reminders->count(),
        'pending' => $reminders->where('is_sent', false)->count(),
        'sent' => $reminders->where('is_sent', true)->count(),
        'reminders' => $reminders,
    ], 200, [], JSON_PRETTY_PRINT);
})->name('reminders.index');

require __DIR__.'/auth.php';
