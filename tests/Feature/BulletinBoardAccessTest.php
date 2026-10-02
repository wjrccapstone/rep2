<?php

namespace Tests\Feature;

use App\Models\BulletinNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_and_cashier_can_view_their_assigned_bulletins_in_real_time(): void
    {
        $technician = User::factory()->create([
            'name' => 'Tech User',
            'role' => 'technician',
            'status' => 'active',
        ]);

        $cashier = User::factory()->create([
            'name' => 'Cashier User',
            'role' => 'cashier',
            'status' => 'active',
        ]);

        BulletinNotice::create([
            'title' => 'Tech only bulletin',
            'message' => 'Assigned to technicians only.',
            'priority' => 'important',
            'category' => 'announcement',
            'audience' => 'technician',
            'is_pinned' => false,
            'requires_ack' => false,
            'posted_by' => $technician->id,
        ]);

        BulletinNotice::create([
            'title' => 'Cashier only bulletin',
            'message' => 'Assigned to cashiers only.',
            'priority' => 'urgent',
            'category' => 'announcement',
            'audience' => 'cashier',
            'is_pinned' => false,
            'requires_ack' => false,
            'posted_by' => $cashier->id,
        ]);

        $this->actingAs($technician)
            ->getJson('/bulletin/feed')
            ->assertOk()
            ->assertJsonPath('html', fn ($html) => str_contains((string) $html, 'Tech only bulletin'))
            ->assertJsonPath('html', fn ($html) => ! str_contains((string) $html, 'Cashier only bulletin'));

        $this->actingAs($cashier)
            ->getJson('/bulletin/feed')
            ->assertOk()
            ->assertJsonPath('html', fn ($html) => str_contains((string) $html, 'Cashier only bulletin'))
            ->assertJsonPath('html', fn ($html) => ! str_contains((string) $html, 'Tech only bulletin'));
    }

    public function test_non_admin_users_cannot_post_new_bulletins(): void
    {
        $technician = User::factory()->create([
            'role' => 'technician',
            'status' => 'active',
        ]);

        $this->actingAs($technician)
            ->postJson('/bulletin', [
                'title' => 'Blocked',
                'message' => 'Should not be created by a technician.',
                'priority' => 'info',
                'category' => 'announcement',
                'audience' => 'all',
            ])
            ->assertForbidden();
    }
}
