<?php

namespace App\Http\Controllers;

use App\Mail\CollectionReminder;
use App\Models\Collection;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class GuestReminderController extends Controller
{
    /**
     * Display the reminder page for guests.
     */
    public function show(string $slug): Response
    {
        // Parse slug to extract collection ID (format: slug-name-id)
        $parts = explode('-', $slug);
        $collectionId = end($parts);

        // Fetch collection with relationships
        $collection = Collection::with(['owner', 'participants.user', 'payments.user'])
            ->findOrFail($collectionId);

        // Calculate stats
        $allParticipants = $collection->participants;
        $paidCount = $allParticipants->where('is_paid', true)->count();
        $totalParticipants = $allParticipants->count();
        $raisedAmount = $collection->payments->sum('amount');

        // Calculate days left
        $daysLeft = 0;
        if ($collection->ends_at) {
            $daysLeft = (int) round(max(0, now()->diffInDays($collection->ends_at, false)));
        }

        // Calculate progress percentage
        $targetAmount = $collection->target_amount ?? 0;
        $progressPercentage = $targetAmount > 0
            ? min(100, ($raisedAmount / $targetAmount) * 100)
            : 0;

        // Get owner initials
        $ownerName = $collection->owner->name ?? 'Unknown';
        $ownerInitials = strtoupper(substr($ownerName, 0, 2));

        return Inertia::render('guest/Reminder', [
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $slug,
                'icon' => $collection->icon ?? 'group',
                'contribution_amount' => $collection->contribution_amount,
                'target_amount' => $targetAmount,
                'participant_goal' => $collection->participant_goal,
                'ends_at' => $collection->ends_at?->format('l M j ga'),
            ],
            'owner' => [
                'id' => $collection->owner->id,
                'name' => $ownerName,
                'initials' => $ownerInitials,
            ],
            'stats' => [
                'paid_count' => $paidCount,
                'total_participants' => $totalParticipants,
                'raised_amount' => $raisedAmount,
                'days_left' => $daysLeft,
                'progress_percentage' => round($progressPercentage, 1),
            ],
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Submit reminder request and schedule email.
     */
    public function submit(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'reminder_type' => 'required|in:1_hour,tomorrow,2_days,before_deadline',
        ], [
            'reminder_type.required' => 'Please select when you want to be reminded.',
        ]);

        // Parse slug to extract collection ID
        $parts = explode('-', $slug);
        $collectionId = end($parts);

        // Fetch collection
        $collection = Collection::with('owner')->findOrFail($collectionId);

        // Calculate reminder time based on type
        $reminderTime = $this->calculateReminderTime($validated['reminder_type'], $collection->ends_at);

        // Store reminder in database for scheduled sending
        $reminder = Reminder::create([
            'collection_id' => $collectionId,
            'email' => $validated['email'],
            'reminder_type' => $validated['reminder_type'],
            'scheduled_at' => $reminderTime,
            'is_sent' => false,
        ]);

        \Log::info("Reminder created for {$validated['email']} on collection {$collection->name}. Scheduled at: {$reminderTime->toIso8601String()}");

        // Format the scheduled time for user display
        $scheduledTimeDisplay = $this->formatScheduledTime($reminderTime, $validated['reminder_type']);

        return redirect()->back()->with('success', "Reminder set! We'll email you at {$validated['email']} {$scheduledTimeDisplay}.");
    }

    /**
     * Calculate when to send reminder based on type.
     */
    private function calculateReminderTime(string $type, $deadline): \Carbon\Carbon
    {
        return match($type) {
            '1_hour' => now()->addHour(),
            'tomorrow' => now()->addDay()->setTime(9, 0), // Tomorrow at 9 AM
            '2_days' => now()->addDays(2)->setTime(9, 0), // In 2 days at 9 AM
            'before_deadline' => $deadline ? $deadline->copy()->subDay()->setTime(9, 0) : now()->addDay()->setTime(9, 0),
            default => now()->addDay()->setTime(9, 0),
        };
    }

    /**
     * Format scheduled time for user display.
     */
    private function formatScheduledTime(\Carbon\Carbon $time, string $type): string
    {
        return match($type) {
            '1_hour' => 'in 1 hour',
            'tomorrow' => 'tomorrow morning at 9 AM',
            '2_days' => 'in 2 days at 9 AM',
            'before_deadline' => 'the day before the deadline at 9 AM',
            default => 'soon',
        };
    }
}
