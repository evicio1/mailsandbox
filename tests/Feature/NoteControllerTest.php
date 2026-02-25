<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Mailbox;
use App\Models\Message;
use App\Models\Note;

class NoteControllerTest extends TestCase
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

    public function test_user_can_add_note_to_message()
    {
        $response = $this->actingAs($this->user)->post(route('messages.notes.store', $this->message->id), [
            'content' => 'Look into this conversation.'
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('notes', [
            'message_id' => $this->message->id,
            'user_id' => $this->user->id,
            'content' => 'Look into this conversation.'
        ]);
    }

    public function test_user_can_delete_own_note()
    {
        $note = Note::factory()->create([
            'message_id' => $this->message->id,
            'user_id' => $this->user->id,
            'content' => 'My secret note'
        ]);

        $response = $this->actingAs($this->user)->delete(route('notes.destroy', $note->id));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_user_cannot_delete_others_note()
    {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $note = Note::factory()->create([
            'message_id' => $this->message->id,
            'user_id' => $otherUser->id,
            'content' => 'Their note'
        ]);

        $response = $this->actingAs($this->user)->delete(route('notes.destroy', $note->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
    }
}
