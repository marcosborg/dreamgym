<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_admin_login_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_authenticated_admin_dashboard_loads(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_authenticated_admin_users_page_loads(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $this->actingAs($user)->get('/admin/users')->assertOk();
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
