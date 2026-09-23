<?php

namespace Database\Seeders;

use App\Models\OpeningHour;
use App\Models\Product;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $room = Room::updateOrCreate(
            ['name' => 'Dream Gym Private Room'],
            [
                'description' => 'Private gym room for focused 1-hour training sessions.',
                'capacity' => 5,
                'slot_price_cents' => 800,
                'currency' => 'EUR',
                'is_active' => true,
            ]
        );

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            OpeningHour::updateOrCreate(
                ['room_id' => $room->id, 'weekday' => $weekday],
                ['opens_at' => '06:00', 'closes_at' => '22:00', 'is_active' => true]
            );
        }

        foreach ([0, 6] as $weekday) {
            OpeningHour::updateOrCreate(
                ['room_id' => $room->id, 'weekday' => $weekday],
                ['opens_at' => '08:00', 'closes_at' => '18:00', 'is_active' => true]
            );
        }

        Product::query()
            ->where('type', Product::TYPE_GROUP_HOUR)
            ->where('sort_order', 40)
            ->update(['sort_order' => 100]);

        $products = [
            ['name' => 'Sessão Única', 'name_en' => 'Single session', 'type' => Product::TYPE_SINGLE_HOUR, 'price_cents' => 800, 'sort_order' => 10, 'credits' => 1, 'days' => 60, 'seats' => 1, 'is_active' => true],
            ['name' => 'Pack 6', 'name_en' => 'Pack 6', 'type' => Product::TYPE_SESSION_PACK, 'price_cents' => 3600, 'sort_order' => 20, 'credits' => 6, 'days' => 60, 'seats' => 1, 'is_active' => true],
            ['name' => 'Pack 12', 'name_en' => 'Pack 12', 'type' => Product::TYPE_SESSION_PACK, 'price_cents' => 4800, 'sort_order' => 30, 'credits' => 12, 'days' => 60, 'seats' => 1, 'is_active' => true],
            ['name' => 'Plano 15', 'name_en' => 'Plan 15', 'type' => Product::TYPE_MEMBERSHIP, 'price_cents' => 4500, 'sort_order' => 50, 'credits' => 15, 'days' => 30, 'seats' => 1, 'is_active' => false],
            ['name' => 'Plano 30', 'name_en' => 'Plan 30', 'type' => Product::TYPE_MEMBERSHIP, 'price_cents' => 6000, 'sort_order' => 60, 'credits' => 30, 'days' => 30, 'seats' => 1, 'is_active' => true],
            ['name' => 'Grupo Privado', 'name_en' => 'Private group', 'type' => Product::TYPE_GROUP_HOUR, 'price_cents' => 2900, 'sort_order' => 100, 'credits' => 1, 'days' => 60, 'seats' => 5, 'is_active' => true],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sort_order' => $product['sort_order']],
                [...$product, 'currency' => 'EUR']
            );
        }

        User::updateOrCreate([
            'email' => 'admin@dreamgym.test',
        ], [
            'name' => 'Dream Gym Admin',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
    }
}
