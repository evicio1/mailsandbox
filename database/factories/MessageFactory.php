<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mailbox_id' => \App\Models\Mailbox::factory(),
            'dedupe_key' => $this->faker->unique()->sha256,
            'subject' => $this->faker->sentence,
            'from_name' => $this->faker->name,
            'from_email' => $this->faker->safeEmail,
            'to_raw' => [$this->faker->safeEmail],
            'text_body' => $this->faker->paragraph,
            'html_body_sanitized' => '<p>' . $this->faker->paragraph . '</p>',
            'received_at' => now(),
            'is_read' => false,
        ];
    }
}
