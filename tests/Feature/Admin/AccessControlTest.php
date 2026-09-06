<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['user_type' => 'staff']);
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_customer_cannot_reach_the_admin_panel(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer']);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_a_dealer_cannot_reach_the_admin_panel(): void
    {
        $dealer = User::factory()->create(['user_type' => 'dealer']);

        $this->actingAs($dealer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_staff_can_reach_the_dashboard(): void
    {
        $this->actingAs($this->staff('admin'))->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_moderator_cannot_manage_staff_users(): void
    {
        $this->actingAs($this->staff('moderator'))
            ->get(route('admin.staff.create'))
            ->assertForbidden();
    }

    public function test_an_admin_can_manage_staff_users(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get(route('admin.staff.create'))
            ->assertOk();
    }

    public function test_a_content_editor_cannot_edit_roles(): void
    {
        $this->actingAs($this->staff('content-editor'))
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_a_blocked_staff_user_is_logged_out(): void
    {
        $user = $this->staff('admin');
        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_super_admin_role_cannot_be_edited(): void
    {
        $superAdmin = $this->staff('super-admin');
        $role = Role::findByName('super-admin');

        $this->actingAs($superAdmin)
            ->put(route('admin.roles.update', $role), ['permissions' => []])
            ->assertForbidden();

        $this->assertGreaterThan(0, $role->fresh()->permissions->count());
    }
}
