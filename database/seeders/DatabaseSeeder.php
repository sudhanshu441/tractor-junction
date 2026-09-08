<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GeographySeeder::class,
            SettingSeeder::class,
            NotificationTemplateSeeder::class,
            AdminUserSeeder::class,
            CatalogMasterSeeder::class,
            DemoProductSeeder::class,
            MarketplaceMasterSeeder::class,
            FinanceMasterSeeder::class,
            DemoListingSeeder::class,
            DemoDealerSeeder::class,
            DemoFinanceSeeder::class,
        ]);
    }
}
