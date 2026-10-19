<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AdminAnnouncementController extends Controller
{
    public function index(): Response
    {
        $activeUsersCount = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsersCount = User::whereNull('email_verified_at')->count();
        $totalUsersCount = User::count();

        $announcements = Announcement::with('admin:id,name,email')
            ->latest()
            ->paginate(10)
            ->through(function (Announcement $announcement) {
                return [
                    'id' => $announcement->id,
                    'subject' => $announcement->subject,
                    'badge' => $announcement->badge,
                    'message' => $announcement->message,
                    'action_text' => $announcement->action_text,
                    'action_url' => $announcement->action_url,
                    'recipients_count' => $announcement->recipients_count,
                    'sent_at' => $announcement->sent_at?->format('d M Y, h:i A'),
                    'admin_name' => $announcement->admin?->name ?? 'Admin',
                ];
            });

        return Inertia::render('Admin/Announcements', [
            'activeUsersCount' => $activeUsersCount,
            'unverifiedUsersCount' => $unverifiedUsersCount,
            'totalUsersCount' => $totalUsersCount,
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'badge' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:10000'],
            'action_text' => ['nullable', 'string', 'max:80'],
            'action_url' => ['nullable', 'url', 'max:255'],
            'target_audience' => ['nullable', 'string', 'in:all,verified,unverified'],
            'send_test_only' => ['nullable', 'boolean'],
        ]);

        $admin = $request->user();

        // If admin wants to send a test email to themselves first
        if ($request->boolean('send_test_only')) {
            try {
                Mail::mailer('resend')->to($admin->email)->send(new AnnouncementMail(
                    announcementSubject: '[TEST] ' . $validated['subject'],
                    badge: $validated['badge'],
                    messageContent: $validated['message'],
                    actionText: $validated['action_text'] ?? null,
                    actionUrl: $validated['action_url'] ?? null,
                    recipientName: $admin->name,
                ));

                return back()->with('success', "Test email sent successfully via Resend to {$admin->email}. Please check your Inbox and Spam folder.");
            } catch (TransportExceptionInterface $e) {
                Log::error('Failed to send test announcement via Resend: ' . $e->getMessage());
                return back()->withErrors(['email' => 'Failed to deliver test email via Resend: ' . $e->getMessage()]);
            }
        }

        // Determine target audience
        $audience = $request->input('target_audience', 'all');
        if ($audience === 'verified') {
            $recipients = User::whereNotNull('email_verified_at')->get();
        } elseif ($audience === 'unverified') {
            $recipients = User::whereNull('email_verified_at')->get();
        } else {
            $recipients = User::all();
        }

        $announcement = Announcement::create([
            'admin_id' => $admin->id,
            'subject' => $validated['subject'],
            'badge' => $validated['badge'],
            'message' => $validated['message'],
            'action_text' => $validated['action_text'] ?? null,
            'action_url' => $validated['action_url'] ?? null,
            'recipients_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        $failedCount = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::mailer('resend')->to($recipient->email)->send(new AnnouncementMail(
                    announcementSubject: $announcement->subject,
                    badge: $announcement->badge,
                    messageContent: $announcement->message,
                    actionText: $announcement->action_text,
                    actionUrl: $announcement->action_url,
                    recipientName: $recipient->name,
                ));
            } catch (\Throwable $e) {
                $failedCount++;
                Log::warning("Failed to send announcement to user #{$recipient->id} ({$recipient->email}): " . $e->getMessage());
            }
        }

        $sentCount = $recipients->count() - $failedCount;

        return back()->with('success', "Announcement broadcasted successfully to {$sentCount} users via Resend!");
    }
}
