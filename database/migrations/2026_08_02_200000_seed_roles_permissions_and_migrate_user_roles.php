<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $roles = ['student', 'lecturer', 'admin', 'super'];

    private array $permissions = [
        'manage registrations',
        'manage schedules',
        'manage tools',
        'manage courses',
        'manage users',
        'manage roles',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->pluck('id', 'name');
        $permIds = DB::table('permissions')->pluck('id', 'name');

        // super gets everything; admin gets everything except managing roles
        foreach ($permIds as $name => $id) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $id,
                'role_id' => $roleIds['super'],
            ]);
            if ($name !== 'manage roles') {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $id,
                    'role_id' => $roleIds['admin'],
                ]);
            }
        }

        // Copy the legacy users.role column into model_has_roles, then drop it
        foreach (DB::table('users')->get(['id', 'role']) as $user) {
            $roleId = $roleIds[$user->role] ?? $roleIds['student'];
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $user->id,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->after('email');
        });

        $assignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_type', 'App\\Models\\User')
            ->pluck('roles.name', 'model_has_roles.model_id');

        foreach ($assignments as $userId => $role) {
            DB::table('users')->where('id', $userId)->update(['role' => $role]);
        }
    }
};
