<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;

class BillingController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        
        // Always refresh from DB in case we just returned from a Stripe webhook update
        $tenant->refresh();
        
        // Only load active plans that have a price ID, or free/premium for display
        $plans = Plan::where('status', 'active')->orderBy('inbox_limit', 'asc')->get();
        
        return view('billing.index', compact('tenant', 'plans'));
    }

    public function checkout(Request $request, Plan $plan)
    {
        $tenant = auth()->user()->tenant;
        
        // Check if plan is subscribe-able
        if (!$plan->price_id) {
            return back()->with('error', 'This plan is not available for self-serve subscription.');
        }

        // Cashier checkout
        return $tenant->newSubscription('default', $plan->price_id)
            ->checkout([
                'success_url' => route('billing.index', ['success' => 1]) . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.index', ['canceled' => 1]),
            ]);
    }

    public function portal(Request $request)
    {
        $tenant = auth()->user()->tenant;

        if (!$tenant->stripe_id) {
            return back()->with('error', 'You do not have an active billing profile.');
        }

        return $tenant->redirectToBillingPortal(route('billing.index'));
    }
}
