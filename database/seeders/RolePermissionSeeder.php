<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Tạo các vai trò
        $roles = [
            'admin',
            'staff',
            'user',
        ];

        // Tạo các quyền
        $permissions = [
            'view users', 'edit users', 'delete users', 'assign roles', 'view user',
            'create brands', 'edit brands', 'delete brands', 'view brands', 'view brand', 'view products of a brand',
            'create products', 'edit products', 'delete products', 'change product status', 'view products', 'view product',
            'create hashtags', 'edit hashtags', 'delete hashtags', 'view hashtags', 'view hashtag',
            'view orders', 'update order status', 'view total payments', 'view canceled orders', 'confirm delivery',
            'create blogs', 'edit blogs', 'delete blogs', 'change blog status', 'view blogs', 'view blog',
            'create shippings', 'edit shippings', 'delete shippings', 'view shippings', 'view shipping',
            'create vouchers', 'edit vouchers', 'delete vouchers', 'change voucher status', 'view vouchers', 'view voucher',
            'create surveys', 'edit surveys', 'delete surveys', 'view surveys', 'view survey',
            'create questions', 'edit questions', 'delete questions', 'view questions', 'view question',
            'view responses', 'view response', 'delete responses',
        ];

        // Tạo các quyền vào bảng Permission
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'permission' => $permissionName,
                'guard_name' => 'web',  // Đảm bảo có guard_name là 'web'
            ]);
        }

        // Tìm các vai trò từ bảng roles
        $adminRole = Role::where('role_name', 'admin')->first();
        $staffRole = Role::where('role_name', 'staff')->first();
        $userRole = Role::where('role_name', 'user')->first();

        // Gán quyền cho vai trò admin
        if ($adminRole) {
            $adminPermissions = Permission::all();
            foreach ($adminPermissions as $permission) {
                if ($permission->id) {
                    $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
                }
            }
        }

        // Gán quyền cho vai trò staff
        if ($staffRole) {
            $staffPermissions = Permission::whereIn('permission', [
                'create survey', 'view surveys', 'update survey', 'delete survey',
                'create question', 'view questions', 'update question', 'delete question',
                'view responses', 'delete response', 'change blog status', 'view blog', 'confirm delivery'
            ])->get();

            foreach ($staffPermissions as $permission) {
                if ($permission->id) {
                    $staffRole->permissions()->syncWithoutDetaching([$permission->id]);
                }
            }
        }

        // Gán quyền cho vai trò user
        if ($userRole) {
            $userPermissions = Permission::whereIn('permission', [
                'view surveys', 'view questions', 'view blog', 'view brands', 'view brand',
                'view products', 'view product', 'view hashtags', 'view hashtag', 'create hashtags', 'view orders',
                'create blogs', 'edit blogs', 'delete blogs', 'view blogs', 'view blog', 'view shippings', 'view shipping',
                'view vouchers', 'view voucher',
                'like blog', 'unlike blog', 'view my blogs', 'change password',
                'submit survey response', 'update survey response', 'view my responses', 'recommend items', 'view cart',
                'add item to cart', 'view cart item', 'update cart item', 'delete cart item', 'complete cart',
                'create order', 'view orders',
                'view product reviews', 'create review', 'update review', 'delete review',
                'view all blogs', 'create blog', 'update blog', 'view specific blog', 'like blog post',
                'view hashtags', 'view specific hashtag'
            ])->get();

            foreach ($userPermissions as $permission) {
                if ($permission->id) {
                    $userRole->permissions()->syncWithoutDetaching([$permission->id]);
                }
            }
        }
    }
}
