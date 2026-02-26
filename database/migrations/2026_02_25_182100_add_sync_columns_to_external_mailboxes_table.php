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
        Schema::table('external_mailboxes', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('email');
            $table->string('folder')->default('INBOX')->after('password');
            $table->unsignedBigInteger('last_seen_uid')->default(0)->after('last_sync_at');
            $table->integer('error_count')->default(0)->after('last_error');
            $table->timestamp('sync_lock_until')->nullable()->after('error_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_mailboxes', function (Blueprint $table) {
            $table->dropColumn([
                'domain',
                'folder',
                'last_seen_uid',
                'error_count',
                'sync_lock_until'
            ]);
        });
    }
};
