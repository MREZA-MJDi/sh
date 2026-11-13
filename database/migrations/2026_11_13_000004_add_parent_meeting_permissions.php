<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $permissions = [
        'meetings.manage' => ['مدیریت جلسات اولیا', 'meetings'],
        'meetings.create' => ['ثبت درخواست جلسه اولیا', 'meetings'],
        'meetings.view.own' => ['مشاهده جلسات اولیای فرزند', 'meetings'],
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name => [$label, $group]) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['label' => $label, 'group' => $group, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissionIds = DB::table('permissions')->whereIn('name', array_keys($this->permissions))->pluck('id');
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        $parentId = DB::table('roles')->where('name', 'parent')->value('id');

        if ($adminId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $adminId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        if ($parentId) {
            foreach (DB::table('permissions')->whereIn('name', ['meetings.create', 'meetings.view.own'])->pluck('id') as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $parentId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys($this->permissions))
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('name', array_keys($this->permissions))->delete();
    }
};
