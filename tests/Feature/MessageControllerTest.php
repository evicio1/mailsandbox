<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Attachment;
use App\Models\Tag;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $tenant;
    private $mailbox1;
    private $mailbox2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->mailbox1 = Mailbox::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->mailbox2 = Mailbox::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_index_shows_messages_from_all_tenant_mailboxes()
    {
        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'Mail 1']);
        Message::factory()->create(['mailbox_id' => $this->mailbox2->id, 'subject' => 'Mail 2']);
        
        $otherTenantMailbox = Mailbox::factory()->create();
        Message::factory()->create(['mailbox_id' => $otherTenantMailbox->id, 'subject' => 'Hidden']);

        $response = $this->actingAs($this->user)->get(route('messages.index'));

        $response->assertStatus(200);
        $response->assertSee('Mail 1');
        $response->assertSee('Mail 2');
        $response->assertDontSee('Hidden');
    }

    public function test_filter_by_mailbox()
    {
        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'Mail 1']);
        Message::factory()->create(['mailbox_id' => $this->mailbox2->id, 'subject' => 'Mail 2']);

        $response = $this->actingAs($this->user)->get(route('messages.index', ['mailbox_id' => $this->mailbox1->id]));

        $response->assertSee('Mail 1');
        $response->assertDontSee('Mail 2');
    }

    public function test_filter_by_sender()
    {
        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'from_email' => 'test@example.com', 'subject' => 'Target Send']);
        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'from_email' => 'other@example.com', 'subject' => 'Other Send']);

        $response = $this->actingAs($this->user)->get(route('messages.index', ['sender' => 'test@example.com']));

        $response->assertSee('Target Send');
        $response->assertDontSee('Other Send');
    }

    public function test_filter_by_has_attachment()
    {
        $msg1 = Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'With Att']);
        Attachment::factory()->create(['message_id' => $msg1->id]);
        
        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'Without Att']);

        $response = $this->actingAs($this->user)->get(route('messages.index', ['has_attachment' => 1]));

        $response->assertSee('With Att');
        $response->assertDontSee('Without Att');
    }

    public function test_filter_by_tag()
    {
        $tag = Tag::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Urgent']);
        $msg1 = Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'Tagged Msg']);
        $msg1->tags()->attach($tag);

        Message::factory()->create(['mailbox_id' => $this->mailbox1->id, 'subject' => 'Untagged Msg']);

        $response = $this->actingAs($this->user)->get(route('messages.index', ['tag_id' => $tag->id]));

        $response->assertSee('Tagged Msg');
        $response->assertDontSee('Untagged Msg');
    }
}
