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

                if (!$plan) {
                    Log::warning("Webhook received with unknown Stripe Price ID: {$priceId}. Please update your .env and re-run PlanSeeder.");
                }

                // Check if they scheduled a downgrade for the end of the period
                $pendingPlanId = null;
                if (isset($subscription['pending_update']['subscription_items'][0]['price']['id'])) {
                    $pendingPriceId = $subscription['pending_update']['subscription_items'][0]['price']['id'];
                    $pendingPlanId = Plan::where('price_id', $pendingPriceId)->value('plan_id');
                } elseif (!empty($subscription['schedule'])) {
                    // Customer Portal uses Schedules to enact end-of-period downgrades
                    try {
                        $schedule = \Laravel\Cashier\Cashier::stripe()->subscriptionSchedules->retrieve($subscription['schedule']);
                        if (!empty($schedule->phases)) {
                            // Find the final phase to see what the plan WILL downgrade to
                            $lastPhase = end($schedule->phases);
                            $pendingPriceId = $lastPhase->items[0]->price ?? null;
                            if ($pendingPriceId && $pendingPriceId !== $priceId) {
                                $pendingPlanId = Plan::where('price_id', $pendingPriceId)->value('plan_id');
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to retrieve Stripe schedule: " . $e->getMessage());
                    }
                }

                // Stripe moves period start/end to items.data in some payload versions (especially when schedules exist)
                $periodStart = $subscription['current_period_start'] ?? $subscription['items']['data'][0]['current_period_start'] ?? null;
                $periodEnd = $subscription['current_period_end'] ?? $subscription['items']['data'][0]['current_period_end'] ?? null;

                $tenant->update([
                    'subscription_status' => $status,
                    'current_plan_id' => $plan ? $plan->plan_id : $tenant->current_plan_id,
                    'pending_plan_id' => $pendingPlanId,
                    'stripe_subscription_id' => $subscription['id'] ?? null,
                    'current_period_start' => $periodStart ? \Carbon\Carbon::createFromTimestamp($periodStart) : $tenant->current_period_start,
                    'current_period_end' => $periodEnd ? \Carbon\Carbon::createFromTimestamp($periodEnd) : $tenant->current_period_end,
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
