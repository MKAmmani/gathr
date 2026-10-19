<?php

namespace Tests\Feature;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_accessing_admin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_admins_can_access_dashboard_and_see_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        Collection::create([
            'owner_id' => $otherUser->id,
            'name' => 'Graduation Pool',
            'status' => 'active',
            'contribution_amount' => 5000,
            'target_amount' => 50000,
            'participant_goal' => 10,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats.active_users_count')
            ->has('stats.active_collections_count')
            ->has('stats.total_active_balance')
        );
    }

    public function test_admins_can_access_announcements_page(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/announcements');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Announcements')
            ->has('activeUsersCount')
            ->has('announcements')
        );
    }

    public function test_admins_can_send_announcement_to_active_users(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/admin/announcements', [
            'subject' => 'Major Update Released',
            'badge' => 'Platform Update',
            'message' => 'We have launched new collection features!',
            'action_text' => 'Check Collections',
            'action_url' => 'https://gathr.ng/collections',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('announcements', [
            'subject' => 'Major Update Released',
            'badge' => 'Platform Update',
        ]);

        Mail::assertSent(AnnouncementMail::class, function ($mail) use ($user1) {
            return $mail->hasTo($user1->email) && $mail->announcementSubject === 'Major Update Released';
        });
    }

    public function test_admins_can_send_test_announcement_only_to_themselves(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'email' => 'admin@gathr.ng',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $user1 = User::factory()->create([
            'email' => 'user1@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post('/admin/announcements', [
            'subject' => 'Test Email Subject',
            'badge' => 'Platform Update',
            'message' => 'Testing preview content.',
            'send_test_only' => true,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('announcements', [
            'subject' => 'Test Email Subject',
        ]);

        Mail::assertSent(AnnouncementMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        Mail::assertNotSent(AnnouncementMail::class, function ($mail) use ($user1) {
            return $mail->hasTo($user1->email);
        });
    }
}
