<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ───────────────────────────────────────────────────
        $permissions = [
            'manage-domains',
            'view-inboxes',
            'manage-api-keys',
            'configure-retention',
            'manage-members',
            'manage-webhooks',
            'view-messages',
            'delete-messages',
            'view-audit-log',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Roles (global / not tenant-scoped — SuperAdmin lives outside tenant context) ──
        // SuperAdmin: global role, assigned outside any tenant team
        setPermissionsTeamId(null);

        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // ── Tenant-scoped roles — we seed them with tenant_id = null as templates ──
        // In practice roles are created per-tenant via TenantController::store()
        // but we seed the "defaults" that can be cloned or referenced.

        // TenantAdmin: full tenant control
        $tenantAdmin = Role::firstOrCreate(['name' => 'TenantAdmin', 'guard_name' => 'web']);
        $tenantAdmin->syncPermissions([
            'manage-domains',
            'view-inboxes',
            'manage-api-keys',
            'configure-retention',
            'manage-members',
            'manage-webhooks',
            'view-messages',
            'delete-messages',
            'view-audit-log',
        ]);

        // Developer: read access + API keys
        $developer = Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);
        $developer->syncPermissions([
            'view-inboxes',
            'view-messages',
            'manage-api-keys',
            'manage-webhooks',
        ]);

        $this->command->info('Roles and permissions seeded.');
    }
}
