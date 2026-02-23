<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
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
        $query = Tenant::with('owner')->withCount('users');

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
        return view('admin.tenants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'plan'            => ['required', 'in:free,starter,pro,enterprise'],
            'owner_email'     => ['required', 'email', 'max:255'],
            'owner_name'      => ['required', 'string', 'max:255'],
        ]);

        $tenant = Tenant::create([
            'name'   => $validated['name'],
            'plan'   => $validated['plan'],
            'status' => 'active',
            'slug'   => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
        ]);

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
        $tenant->load(['owner', 'users', 'mailboxes']);

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
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'plan'   => ['required', 'in:free,starter,pro,enterprise'],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $tenant->update($validated);

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
}
