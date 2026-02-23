<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->string('plan')->default('free')->after('slug');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('plan');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['slug', 'plan', 'status', 'owner_id']);
        });
    }
};
