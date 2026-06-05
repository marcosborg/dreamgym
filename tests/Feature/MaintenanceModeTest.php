<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_shows_maintenance_page_when_enabled(): void
    {
        Setting::setValue(SiteSettings::MAINTENANCE_ENABLED, true);
        Setting::setValue(SiteSettings::MAINTENANCE_ALLOWED_IPS, []);

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Estamos a preparar tudo para sí.')
            ->assertSee('brand/logo.png');
    }

    public function test_allowed_ip_can_see_frontend_during_maintenance(): void
    {
        Setting::setValue(SiteSettings::MAINTENANCE_ENABLED, true);
        Setting::setValue(SiteSettings::MAINTENANCE_ALLOWED_IPS, ['127.0.0.1']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dream Gym');
    }

    public function test_admin_stays_available_during_maintenance(): void
    {
        Setting::setValue(SiteSettings::MAINTENANCE_ENABLED, true);
        Setting::setValue(SiteSettings::MAINTENANCE_ALLOWED_IPS, []);

        $this->get('/admin/login')
            ->assertOk();
    }
}
