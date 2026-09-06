<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.env') === 'production'
            ? Str::password(16)
            : 'KrishiAdmin@2026';

        $admin = User::updateOrCreate(
            ['mobile' => '9000000001'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@krishijunction.com',
                'password' => Hash::make($password),
                'user_type' => 'staff',
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $admin->syncRoles(['super-admin']);

        $this->command?->info('Super admin: admin@krishijunction.com / mobile 9000000001');

        if (config('app.env') === 'production') {
            $this->command?->warn("Generated password: {$password}  — store it now, it is not shown again.");
        } else {
            $this->command?->comment("Local password: {$password}");
        }
    }
}
