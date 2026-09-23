<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Room;
use App\Services\ProductCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_uses_editable_names_in_each_language_without_changing_prices(): void
    {
        $product = Product::where('type', Product::TYPE_GROUP_HOUR)->firstOrFail();
        $product->update(['name' => 'Grupo à minha maneira', 'name_en' => 'My private group']);
        $room = new Room(['capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR']);
        app()->setLocale('pt');
        $portuguese = app(ProductCatalog::class)->groupHour($room);
        app()->setLocale('en');
        $english = app(ProductCatalog::class)->groupHour($room);
        $this->assertSame('Grupo à minha maneira', $portuguese['name']);
        $this->assertSame('My private group', $english['name']);
        $this->assertSame($portuguese['price_cents'], $english['price_cents']);
        $product->update(['name_en' => null]);
        $this->assertSame('Grupo à minha maneira', app(ProductCatalog::class)->groupHour($room)['name']);
    }

    public function test_migration_translates_existing_standard_products(): void
    {
        $this->assertSame('Private group', Product::where('type', Product::TYPE_GROUP_HOUR)->firstOrFail()->name_en);
    }
}
