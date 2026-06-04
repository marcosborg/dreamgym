<?php

namespace Database\Seeders;

use App\Models\OpeningHour;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

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
                'description' => 'Private gym room for focused 30-minute training sessions.',
                'capacity' => 1,
                'slot_price_cents' => 1200,
                'currency' => 'EUR',
                'is_active' => true,
            ]
        );

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            OpeningHour::updateOrCreate(
                ['room_id' => $room->id, 'weekday' => $weekday],
                ['opens_at' => '07:00', 'closes_at' => '22:00', 'is_active' => true]
            );
        }

        foreach ([0, 6] as $weekday) {
            OpeningHour::updateOrCreate(
                ['room_id' => $room->id, 'weekday' => $weekday],
                ['opens_at' => '09:00', 'closes_at' => '18:00', 'is_active' => true]
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
