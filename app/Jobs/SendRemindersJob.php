<?php

namespace App\Jobs;

use App\Mail\CollectionReminder;
use App\Models\Reminder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all reminders that are due and not yet sent
        $dueReminders = Reminder::with(['collection.owner'])
            ->where('is_sent', false)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueReminders as $reminder) {
            try {
                // Send the reminder email
                $slug = \Illuminate\Support\Str::slug($reminder->collection->name) . '-' . $reminder->collection->id;
                
                Mail::to($reminder->email)->send(
                    new CollectionReminder(
                        collectionName: $reminder->collection->name,
                        organizerName: $reminder->collection->owner->name ?? 'Unknown',
                        deadline: $reminder->collection->ends_at?->format('l, F j, Y g:i A') ?? 'No deadline set',
                        amount: $reminder->collection->contribution_amount,
                        slug: $slug,
                    )
                );

                // Mark the reminder as sent
                $reminder->update([
                    'is_sent' => true,
                    'sent_at' => now(),
                ]);

                \Log::info("Reminder email sent successfully to {$reminder->email} for collection {$reminder->collection->name}");
            } catch (\Exception $e) {
                \Log::error("Failed to send reminder email to {$reminder->email}: " . $e->getMessage());
                // The job will retry on the next run if it fails
            }
        }
    }
}
