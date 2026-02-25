<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookHandled;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\Plan;

class StripeEventListener
{
    /**
     * Handle the event.
     */
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'];

        Log::info("Handled Stripe Webhook: {$type}");

        // Handle subscription created/updated
        if ($type === 'customer.subscription.created' || $type === 'customer.subscription.updated') {
            $subscription = $payload['data']['object'];
            $customerId = $subscription['customer'];

            $tenant = Tenant::where('stripe_id', $customerId)->first();

            if ($tenant) {
                // Determine plan by price id
                $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
                $status = $subscription['status'];
                
                $plan = Plan::where('price_id', $priceId)->first();

                $tenant->update([
                    'subscription_status' => $status,
                    'current_plan_id' => $plan ? $plan->plan_id : $tenant->current_plan_id,
                    'stripe_subscription_id' => $subscription['id'] ?? null,
                    'current_period_start' => isset($subscription['current_period_start']) ? \Carbon\Carbon::createFromTimestamp($subscription['current_period_start']) : $tenant->current_period_start,
                    'current_period_end' => isset($subscription['current_period_end']) ? \Carbon\Carbon::createFromTimestamp($subscription['current_period_end']) : $tenant->current_period_end,
                    'cancel_at_period_end' => $subscription['cancel_at_period_end'] ?? false,
                ]);
                Log::info("Tenant {$tenant->id} subscription updated to {$status}");
            }
        }

        // Handle deleted naturally
        if ($type === 'customer.subscription.deleted') {
            $subscription = $payload['data']['object'];
            $customerId = $subscription['customer'];

            $tenant = Tenant::where('stripe_id', $customerId)->first();

            if ($tenant) {
                $tenant->update([
                    'subscription_status' => 'canceled',
                    'current_plan_id' => 'free', // downgrade to free
                    'stripe_subscription_id' => null,
                ]);
                Log::info("Tenant {$tenant->id} subscription canceled. Downgraded to free.");
            }
        }
    }
}
