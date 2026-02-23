<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;

class MemberController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('permission:manage-members'),
        ];
    }

    public function index(Tenant $tenant)
    {
        $this->authorizeForTenant($tenant);

        $members = $tenant->users()->with('roles')->paginate(25);
        $roles   = Role::all();

        return view('admin.members.index', compact('tenant', 'members', 'roles'));
    }

    public function update(Request $request, Tenant $tenant, User $user)
    {
        $this->authorizeForTenant($tenant);

        $request->validate(['role' => ['required', 'string', 'exists:roles,name']]);

        // Prevent changing SuperAdmin's role via this interface
        if ($user->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Cannot modify SuperAdmin roles here.']);
        }

        // Scope Spatie to this tenant
        setPermissionsTeamId($tenant->id);

        $user->syncRoles([$request->role]);

        return back()->with('success', "{$user->name}'s role updated to {$request->role}.");
    }

    public function destroy(Tenant $tenant, User $user)
    {
        $this->authorizeForTenant($tenant);

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['error' => 'Cannot remove a SuperAdmin.']);
        }

        $user->update(['tenant_id' => null]);
        setPermissionsTeamId($tenant->id);
        $user->syncRoles([]);

        return back()->with('success', "{$user->name} removed from tenant.");
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function authorizeForTenant(Tenant $tenant): void
    {
        $user = auth()->user();

        // SuperAdmin can manage any tenant
        if ($user->isSuperAdmin()) {
            return;
        }

        // TenantAdmin can only manage their own tenant
        if ((int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'Unauthorized.');
        }
    }
}
