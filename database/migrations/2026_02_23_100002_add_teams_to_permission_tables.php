<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enables Spatie Teams Mode by adding tenant_id (team_foreign_key) to
 * the roles and model_has_* pivot tables in existing installations.
 */
return new class extends Migration
{
    public function up(): void
    {
        // If the 'roles' table already has 'tenant_id', we assume Spatie
        // migrated from scratch with teams enabled, so we do nothing.
        if (Schema::hasColumn('roles', 'tenant_id')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id', 'roles_tenant_id_index');
            $table->dropUnique('roles_name_guard_name_unique');
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('role_id');
                $table->index('tenant_id', 'model_has_roles_tenant_id_index');
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
            }
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->unique(
                ['tenant_id', 'role_id', 'model_id', 'model_type'],
                'model_has_roles_tenant_role_model_unique'
            );
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('model_has_permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('permission_id');
                $table->index('tenant_id', 'model_has_permissions_tenant_id_index');
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable()->change();
            }
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->unique(
                ['tenant_id', 'permission_id', 'model_id', 'model_type'],
                'model_has_permissions_tenant_permission_model_unique'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('roles', 'tenant_id')) {
            return;
        }

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('model_has_permissions_tenant_id_index');
            $table->dropColumn('tenant_id');
            $table->primary(
                ['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary'
            );
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('model_has_roles_tenant_id_index');
            $table->dropColumn('tenant_id');
            $table->primary(
                ['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary'
            );
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_tenant_id_name_guard_name_unique');
            $table->dropIndex('roles_tenant_id_index');
            $table->dropColumn('tenant_id');
            $table->unique(['name', 'guard_name']);
        });
    }
};
