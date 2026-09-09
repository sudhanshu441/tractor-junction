<?php

namespace Database\Seeders;

use App\Models\Dealer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demo user per role, so the permission system can actually be exercised.
 *
 * Without these, eleven roles exist but only super-admin has anybody in it —
 * which means nobody can check that a moderator cannot reach the finance desk,
 * or that a sales executive sees masked contact numbers. Sample data: delete
 * before launch along with the other demo seeders.
 */
class DemoStaffSeeder extends Seeder
{
    public const PASSWORD = 'KrishiStaff@2026';

    /** role => [display name, mobile] */
    private const STAFF = [
        'admin' => ['Anita Sharma', '9000000002'],
        'catalog-manager' => ['Rakesh Verma', '9000000003'],
        'content-editor' => ['Priya Nair', '9000000004'],
        'moderator' => ['Imran Sheikh', '9000000005'],
        'sales-executive' => ['Deepak Rao', '9000000006'],
        'finance-executive' => ['Meena Joshi', '9000000007'],
        'inspector' => ['Balwinder Gill', '9000000008'],
    ];

    public function run(): void
    {
        foreach (self::STAFF as $role => [$name, $mobile]) {
            $user = User::updateOrCreate(
                ['mobile' => $mobile],
                [
                    'name' => $name,
                    // Prefixed: the super admin already holds admin@krishijunction.com.
                    'email' => 'staff.'.$role.'@krishijunction.com',
                    'password' => Hash::make(self::PASSWORD),
                    'user_type' => 'staff',
                    'mobile_verified_at' => now(),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$role]);
        }

        $this->seedDealerStaff();

        $this->command?->info('  Demo staff: '.count(self::STAFF).' role accounts + 1 dealer staff'
            .' · password '.self::PASSWORD);
        $this->command?->warn('  Sample accounts — remove before launch.');
    }

    /**
     * A dealer employee who is not the owner, so the difference between
     * dealer-owner and dealer-staff is visible in the dealer panel.
     */
    private function seedDealerStaff(): void
    {
        $dealer = Dealer::where('verification_status', 'verified')->first();

        if (! $dealer) {
            return;
        }

        $user = User::updateOrCreate(
            ['mobile' => '9811100009'],
            [
                'name' => 'Sunita Patil',
                'email' => 'dealer-staff@example.com',
                'password' => Hash::make(self::PASSWORD),
                'user_type' => 'dealer',
                'mobile_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $user->syncRoles(['dealer-staff']);

        $dealer->staff()->updateOrCreate(
            ['user_id' => $user->id],
            // dealer_users.role is owner|manager|sales — not the Spatie role name.
            ['role' => 'sales', 'is_active' => true],
        );
    }
}
