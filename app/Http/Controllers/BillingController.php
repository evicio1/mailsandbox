<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\SalesInquiry;
use Illuminate\Support\Facades\Mail;
use App\Mail\SalesInquiryNotification;

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

        // Prevent double subscriptions
        if ($tenant->subscribed('default')) {
            return redirect()->route('billing.index')->with('error', 'You already have an active subscription. Please use the Billing Portal to switch plans.');
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

    public function contactSales(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $planId = $request->input('plan_id', 'premium');

        // Prevent spamming requests
        $existing = SalesInquiry::where('tenant_id', $tenant->id)
            ->where('status', 'new')
            ->first();

        if ($existing) {
            return back()->with('success', 'Our sales team already has your request! We will reach out to you shortly.');
        }

        $inquiry = SalesInquiry::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'current_plan' => $tenant->current_plan_id,
            'requested_plan' => $planId,
            'status' => 'new',
        ]);

        // Notify SuperAdmin
        Mail::to('hello@skilleyez.io')->send(new SalesInquiryNotification($inquiry));

        return back()->with('success', 'Thank you! Our sales team has been notified and will reach out to the owner\'s email address shortly.');
    }
}
