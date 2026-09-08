<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /** module => actions available on it */
    private const MATRIX = [
        'dashboard' => ['view'],
        'brands' => ['view', 'create', 'edit', 'delete'],
        'categories' => ['view', 'create', 'edit', 'delete'],
        'specs' => ['view', 'create', 'edit', 'delete'],
        'products' => ['view', 'create', 'edit', 'delete', 'export'],
        'prices' => ['view', 'create', 'edit', 'delete', 'export'],
        'listings' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
        'inspections' => ['view', 'create', 'edit', 'assign', 'approve'],
        'valuation' => ['view', 'edit'],
        'dealers' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],
        'dealer_inventory' => ['view', 'create', 'edit', 'delete'],
        'plans' => ['view', 'create', 'edit', 'delete'],
        'leads' => ['view', 'create', 'edit', 'delete', 'assign', 'export', 'view_contact'],
        'routing' => ['view', 'create', 'edit', 'delete'],
        'loans' => ['view', 'create', 'edit', 'approve', 'assign', 'export'],
        'lenders' => ['view', 'create', 'edit', 'delete'],
        'insurance' => ['view', 'create', 'edit', 'assign'],
        'reviews' => ['view', 'edit', 'delete', 'approve'],
        'pages' => ['view', 'create', 'edit', 'delete'],
        'blogs' => ['view', 'create', 'edit', 'delete', 'approve'],
        'videos' => ['view', 'create', 'edit', 'delete'],
        'faqs' => ['view', 'create', 'edit', 'delete'],
        'banners' => ['view', 'create', 'edit', 'delete'],
        'offers' => ['view', 'create', 'edit', 'delete'],
        'menus' => ['view', 'edit'],
        'testimonials' => ['view', 'create', 'edit', 'delete'],
        'contact' => ['view', 'edit', 'export'],
        'seo' => ['view', 'edit'],
        'redirects' => ['view', 'create', 'edit', 'delete'],
        'geography' => ['view', 'create', 'edit', 'delete'],
        'users' => ['view', 'create', 'edit', 'delete', 'export'],
        'roles' => ['view', 'create', 'edit', 'delete'],
        'notifications' => ['view', 'create', 'edit', 'delete'],
        'reports' => ['view', 'export'],
        'settings' => ['view', 'configure'],
        'activity' => ['view'],
    ];

    /** role => permissions ('*' = everything, or an explicit list of module.action) */
    private const ROLES = [
        'super-admin' => '*',
        'admin' => [
            'dashboard.*', 'brands.*', 'categories.*', 'specs.*', 'products.*', 'prices.*',
            'listings.*', 'inspections.*', 'valuation.*', 'dealers.*', 'dealer_inventory.*',
            'plans.*', 'leads.*', 'routing.*', 'loans.*', 'lenders.*', 'insurance.*',
            'reviews.*', 'pages.*', 'blogs.*', 'videos.*', 'faqs.*', 'banners.*', 'offers.*',
            'testimonials.*', 'contact.*',
            'menus.*', 'seo.*', 'redirects.*', 'geography.*', 'users.*', 'roles.view',
            'notifications.*', 'reports.*', 'activity.view',
        ],
        'catalog-manager' => [
            'dashboard.view', 'brands.*', 'categories.*', 'specs.*', 'products.*', 'prices.*',
            'dealer_inventory.view', 'dealer_inventory.edit', 'banners.view', 'banners.edit',
            'offers.*', 'seo.view', 'seo.edit', 'reports.view',
        ],
        'content-editor' => [
            'dashboard.view', 'pages.*', 'blogs.*', 'videos.*', 'faqs.*', 'banners.*',
            'testimonials.*', 'contact.*', 'offers.view', 'offers.edit', 'menus.*',
            'seo.*', 'redirects.*',
            'reviews.view', 'reviews.edit', 'notifications.view', 'notifications.edit',
            'reports.view', 'products.view',
        ],
        'moderator' => [
            'dashboard.view', 'listings.view', 'listings.edit', 'listings.approve', 'listings.export',
            'inspections.view', 'inspections.create', 'inspections.edit', 'inspections.assign',
            'reviews.view', 'reviews.edit', 'reviews.approve', 'dealers.view', 'dealers.approve',
            'leads.view', 'users.view', 'products.view', 'reports.view',
        ],
        'sales-executive' => [
            'dashboard.view', 'leads.view', 'leads.edit', 'leads.assign', 'leads.view_contact',
            'leads.export', 'dealers.view', 'listings.view', 'products.view', 'prices.view',
            'loans.view', 'users.view', 'reports.view',
        ],
        'finance-executive' => [
            'dashboard.view', 'loans.*', 'lenders.*', 'insurance.*',
            'leads.view', 'leads.edit', 'leads.view_contact', 'users.view',
            'products.view', 'prices.view', 'reports.view', 'reports.export',
        ],
        'inspector' => [
            'dashboard.view', 'inspections.view', 'inspections.edit', 'listings.view',
        ],
        'dealer-owner' => [],   // dealer panel is gated by user_type + ownership, not admin permissions
        'dealer-staff' => [],
        'customer' => [],
    ];

    public function run(): void
    {
        $guard = 'web';

        foreach (self::MATRIX as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$module}.{$action}", $guard);
            }
        }

        $all = Permission::pluck('name')->all();

        foreach (self::ROLES as $roleName => $grants) {
            $role = Role::findOrCreate($roleName, $guard);

            if ($grants === '*') {
                $role->syncPermissions($all);

                continue;
            }

            $resolved = [];

            foreach ($grants as $grant) {
                if (str_ends_with($grant, '.*')) {
                    $module = substr($grant, 0, -2);
                    foreach (self::MATRIX[$module] ?? [] as $action) {
                        $resolved[] = "{$module}.{$action}";
                    }
                } else {
                    $resolved[] = $grant;
                }
            }

            $role->syncPermissions(array_values(array_unique(array_intersect($resolved, $all))));
        }

        Artisan::call('permission:cache-reset');

        $this->command?->info('Roles: '.Role::count().' · Permissions: '.Permission::count());
    }
}
