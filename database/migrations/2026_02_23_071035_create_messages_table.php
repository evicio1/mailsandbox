<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->string('dedupe_key')->unique();
            $table->string('message_id')->nullable();
            $table->text('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->json('to_raw')->nullable();
            $table->json('cc_raw')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->text('snippet')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('html_body_sanitized')->nullable();
            $table->longText('headers_raw')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
