<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use App\Notifications\TenantWelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class TenantController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('super_admin'),
        ];
    }

    public function index(Request $request)
    {
        $query = Tenant::with(['owner', 'plan'])->withCount('users');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tenants = $query->latest()->paginate(20);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = Plan::all();
        return view('admin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'plan'            => ['required', 'exists:plans,plan_id'],
            'owner_email'     => ['required', 'email', 'max:255'],
            'owner_name'      => ['required', 'string', 'max:255'],
        ]);

        $tenant = Tenant::create([
            'name'            => $validated['name'],
            'current_plan_id' => $validated['plan'],
            'status'          => 'active',
            'slug'            => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
        ]);

        // Auto-provision a platform subdomain
        $tenant->domains()->create([
            'domain' => $tenant->slug . '.' . config('imap.domain', 'evicio.site'),
            'is_verified' => true,
            'is_platform_provided' => true,
            'catch_all_enabled' => true,
        ]);

        // Auto-create the Stripe Customer in the background
        try {
            $tenant->createAsStripeCustomer([
                'email' => $validated['owner_email'],
                'name' => $validated['name'],
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Stripe Customer Creation failed: ' . $e->getMessage());
        }

        // Create or find the owner user and assign them
        $owner = User::firstOrCreate(
            ['email' => $validated['owner_email']],
            ['name' => $validated['owner_name'], 'password' => bcrypt(Str::random(24))]
        );

        $owner->update(['tenant_id' => $tenant->id]);
        $tenant->update(['owner_id' => $owner->id]);

        // Set team context and assign TenantAdmin role
        setPermissionsTeamId($tenant->id);
        $owner->assignRole('TenantAdmin');

        // Send welcome email with password reset link
        $owner->notify(new TenantWelcomeNotification($tenant));

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', "Tenant \"{$tenant->name}\" created and welcome email sent to {$owner->email}.");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['owner', 'users', 'mailboxes', 'plan']);

        $metrics = [
            'users'        => $tenant->users()->count(),
            'mailboxes'    => $tenant->mailboxes()->count(),
            'messages'     => $tenant->totalMessages(),
            'storage_mb'   => round($tenant->totalStorageBytes() / 1048576, 2),
        ];

        return view('admin.tenants.show', compact('tenant', 'metrics'));
    }

    public function edit(Tenant $tenant)
    {
        $plans = Plan::all();
        return view('admin.tenants.edit', compact('tenant', 'plans'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'plan'                 => ['required', 'exists:plans,plan_id'],
            'status'               => ['required', 'in:active,suspended'],
            'inbox_limit_override' => ['nullable', 'integer', 'min:-1'],
        ]);

        $oldPlan = $tenant->current_plan_id;
        $oldLimit = $tenant->inbox_limit_override;

        $tenant->update([
            'name'                 => $validated['name'],
            'current_plan_id'      => $validated['plan'],
            'status'               => $validated['status'],
            'inbox_limit_override' => $validated['inbox_limit_override'],
        ]);

        if ($oldPlan !== $validated['plan'] || $oldLimit !== $validated['inbox_limit_override']) {
            \Illuminate\Support\Facades\Log::info('SuperAdmin updated tenant billing/limits', [
                'tenant_id' => $tenant->id,
                'admin_id'  => auth()->id(),
                'old_plan'  => $oldPlan,
                'new_plan'  => $validated['plan'],
                'old_override' => $oldLimit,
                'new_override' => $validated['inbox_limit_override'],
            ]);
        }

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->suspend();
        return back()->with('success', "Tenant \"{$tenant->name}\" suspended.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->activate();
        return back()->with('success', "Tenant \"{$tenant->name}\" activated.");
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted.');
    }

    public function resendInvite(Tenant $tenant)
    {
        $owner = $tenant->owner;

        if (! $owner) {
            return back()->withErrors(['error' => 'This tenant has no owner to invite.']);
        }

        $owner->notify(new TenantWelcomeNotification($tenant));

        return back()->with('success', "Invite email resent to {$owner->email}.");
    }
}
