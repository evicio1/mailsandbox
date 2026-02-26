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
        Schema::create('external_mailbox_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_mailbox_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // success, failed
            $table->integer('emails_found')->default(0);
            $table->integer('emails_imported')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_mailbox_sync_logs');
    }
};
