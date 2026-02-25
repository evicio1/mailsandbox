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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('raw_file_path')->nullable()->after('headers_raw');
            $table->json('bcc_raw')->nullable()->after('cc_raw');
            $table->string('spf_result')->nullable();
            $table->string('dkim_result')->nullable();
            $table->string('dmarc_result')->nullable();
            $table->string('spam_score')->nullable();
            $table->json('tls_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn([
                'raw_file_path',
                'bcc_raw',
                'spf_result',
                'dkim_result',
                'dmarc_result',
                'spam_score',
                'tls_info',
            ]);
        });
    }
};
