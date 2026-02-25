<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => \App\Models\Message::factory(),
            'filename' => $this->faker->word . '.pdf',
            'content_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1024, 5000000),
            'storage_path' => 'attachments/fake.pdf',
        ];
    }
}
