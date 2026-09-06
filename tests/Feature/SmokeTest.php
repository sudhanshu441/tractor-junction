<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Krishi Junction', false)
            ->assertSee('Find the right tractor');
    }

    public function test_the_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Log in or sign up');
    }

    public function test_the_full_seed_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('states', 36);
        $this->assertDatabaseHas('users', ['email' => 'admin@krishijunction.com']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name']);
        $this->assertDatabaseHas('notification_templates', ['event_key' => 'auth.otp']);
    }
}
