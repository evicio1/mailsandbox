<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Tag;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $tenant;
    private $mailbox;
    private $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->mailbox = Mailbox::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->message = Message::factory()->create(['mailbox_id' => $this->mailbox->id]);
    }

    public function test_can_toggle_tag_on_message()
    {
        $response = $this->actingAs($this->user)->post(route('messages.tags.toggle', $this->message->id), [
            'name' => 'Review Needed',
            'color' => '#ff0000',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tags', [
            'tenant_id' => $this->tenant->id,
            'name' => 'review needed', 
            'color' => '#ff0000'
        ]);
        
        $this->assertEquals(1, $this->message->tags()->count());

        // Toggle off
        $response = $this->actingAs($this->user)->post(route('messages.tags.toggle', $this->message->id), [
            'name' => 'Review Needed',
        ]);

        $this->assertEquals(0, $this->message->refresh()->tags()->count());
    }

    public function test_cannot_tag_message_from_other_tenant()
    {
        $otherTenantMailbox = Mailbox::factory()->create();
        $otherMessage = Message::factory()->create(['mailbox_id' => $otherTenantMailbox->id]);

        $response = $this->actingAs($this->user)->post(route('messages.tags.toggle', $otherMessage->id), [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }
}
