<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_id'     => 'free',
                'name'        => 'Free',
                'price_id'    => null, // No stripe price for free
                'inbox_limit' => 1,
                'status'      => 'active',
            ],
            [
                'plan_id'     => 'starter',
                'name'        => 'Starter',
                'price_id'    => env('STRIPE_PRICE_STARTER', 'price_1T4d5cGhINsgQXzbRqeO0O2c'),
                'inbox_limit' => 5,
                'status'      => 'active',
            ],
            [
                'plan_id'     => 'growing',
                'name'        => 'Growing',
                'price_id'    => env('STRIPE_PRICE_GROWING', 'price_1T4dETGhINsgQXzblprtBCAk'),
                'inbox_limit' => 15,
                'status'      => 'active',
            ],
            [
                'plan_id'     => 'premium',
                'name'        => 'Premium',
                'price_id'    => null, // Sales assisted
                'inbox_limit' => -1, // Unlimited or handle dynamically
                'status'      => 'active',
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['plan_id' => $plan['plan_id']],
                $plan
            );
        }
    }
}
