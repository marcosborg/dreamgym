<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_image_is_served_and_admin_can_disable_it(): void
    {
        Setting::setValue('maintenance_enabled', false);
        Setting::setValue('promotion_image', 'promotions/founder-october-2026.jpg');
        Setting::setValue('promotion_enabled', true);
        $this->get('/')->assertOk()->assertSee('data-promotion-version', false)->assertSee('/promotion/founder-october-2026.jpg');
        $this->get('/promotion/founder-october-2026.jpg')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(Settings::class)->set('data.promotion_enabled', false)->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertDontSee('data-promotion-version', false);
    }
}
