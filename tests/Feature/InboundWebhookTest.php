<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Message;
use App\Models\Mailbox;

class InboundWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_process_mailgun_webhook()
    {
        Storage::fake('local');

        $headers = [
            ["To", "test@test.evicio.site"],
            ["From", "sender@example.com"],
            ["Subject", "Test Webhook"],
            ["Message-Id", "<12345@example.com>"],
            ["X-Mailgun-Spf", "Pass"]
        ];

        $contentIdMap = [
            "<image001.png@01D>" => "attachment-1"
        ];

        $payload = [
            'recipient' => 'test@test.evicio.site',
            'sender' => 'sender@example.com',
            'subject' => 'Test Webhook',
            'from' => 'Sender <sender@example.com>',
            'timestamp' => time(),
            'body-plain' => 'Hello World',
            'body-html' => '<p>Hello <b>World</b>! <img src="cid:image001.png@01D"></p>',
            'Message-Id' => '<12345@example.com>',
            'message-headers' => json_encode($headers),
            'content-id-map' => json_encode($contentIdMap),
            'attachment-count' => 1,
        ];

        $file = UploadedFile::fake()->image('image001.png');

        $response = $this->post('/api/webhooks/inbound-email', array_merge($payload, [
            'attachment-1' => $file,
            'body-mime' => UploadedFile::fake()->create('raw.eml', 100, 'message/rfc822')
        ]));

        $response->assertStatus(200);

        $this->assertDatabaseHas('mailboxes', [
            'mailbox_key' => 'test@test.evicio.site'
        ]);

        $this->assertDatabaseHas('messages', [
            'subject' => 'Test Webhook',
            'from_email' => 'sender@example.com',
            'spf_result' => 'Pass',
            'is_read' => 0,
        ]);

        $message = Message::first();
        $this->assertNotNull($message->raw_file_path);
        
        $this->assertDatabaseHas('attachments', [
            'message_id' => $message->id,
            'filename' => 'image001.png',
            'content_id' => 'image001.png@01D'
        ]);
        
        Storage::disk('local')->assertExists($message->raw_file_path);
    }
}
