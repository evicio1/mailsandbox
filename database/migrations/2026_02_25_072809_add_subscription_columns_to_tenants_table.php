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
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('plan'); // Replaced by current_plan_id
            $table->string('subscription_status')->default('none')->after('slug');
            $table->string('current_plan_id')->nullable()->after('subscription_status');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_id');
            $table->timestamp('current_period_start')->nullable()->after('current_plan_id');
            $table->timestamp('current_period_end')->nullable()->after('current_period_start');
            $table->boolean('cancel_at_period_end')->default(false)->after('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan')->default('free')->after('slug');
            $table->dropColumn([
                'subscription_status',
                'current_plan_id',
                'stripe_subscription_id',
                'current_period_start',
                'current_period_end',
                'cancel_at_period_end',
            ]);
        });
    }
};
