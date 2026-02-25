<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Mailbox;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_inbox_limit_enforcement()
    {
        $plan = Plan::create(['plan_id' => 'free', 'name' => 'Free', 'inbox_limit' => 1]);
        $tenant = Tenant::create(['name' => 'Acme Corp', 'current_plan_id' => 'free', 'slug' => 'acme-corp']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user);

        // Create first mailbox (should succeed)
        $response1 = $this->postJson('/mailboxes', ['mailbox_key' => 'hello']);
        $response1->assertStatus(201);
        
        $this->assertEquals(1, $tenant->mailboxes()->active()->count());

        // Create second mailbox (should fail with 403 INBOX_QUOTA_EXCEEDED)
        $response2 = $this->postJson('/mailboxes', ['mailbox_key' => 'support']);
        $response2->assertStatus(403);
        $response2->assertJson(['error' => 'INBOX_QUOTA_EXCEEDED']);

        $this->assertEquals(1, $tenant->mailboxes()->active()->count());
    }

    public function test_tenant_enforce_inbox_quota_disables_extras_on_downgrade()
    {
        $planPro = Plan::create(['plan_id' => 'pro', 'name' => 'Pro', 'inbox_limit' => 5]);
        $planFree = Plan::create(['plan_id' => 'free', 'name' => 'Free', 'inbox_limit' => 1]);

        $tenant = Tenant::create(['name' => 'Acme Corp', 'current_plan_id' => 'pro', 'slug' => 'acme-corp']);

        // Create 3 mailboxes (allowed under Pro limit)
        $mb1 = Mailbox::create(['tenant_id' => $tenant->id, 'mailbox_key' => 'hello', 'status' => 'active']);
        $mb2 = Mailbox::create(['tenant_id' => $tenant->id, 'mailbox_key' => 'support', 'status' => 'active']);
        $mb3 = Mailbox::create(['tenant_id' => $tenant->id, 'mailbox_key' => 'sales', 'status' => 'active']);

        $this->assertEquals(3, $tenant->mailboxes()->active()->count());

        // Downgrade to Free plan
        $tenant->update(['current_plan_id' => 'free']);
        
        // Enforce quota
        $tenant->refresh();
        $tenant->enforceInboxQuota();

        // 2 mailboxes should be disabled (the 2 oldest, if we use created_at, they are effectively the first 2 created)
        $this->assertEquals(1, $tenant->mailboxes()->active()->count());
        $this->assertEquals(2, $tenant->mailboxes()->disabled()->count());
    }
}
