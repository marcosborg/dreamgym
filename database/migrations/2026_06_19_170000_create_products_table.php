<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('credits')->nullable();
            $table->unsignedInteger('days')->nullable();
            $table->unsignedInteger('seats')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        DB::table('products')->insert([
            [
                'name' => 'Hora individual',
                'type' => 'single_hour',
                'price_cents' => 1200,
                'currency' => 'EUR',
                'is_active' => true,
                'sort_order' => 10,
                'credits' => null,
                'days' => null,
                'seats' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pack 10 sessões',
                'type' => 'session_pack',
                'price_cents' => 10800,
                'currency' => 'EUR',
                'is_active' => true,
                'sort_order' => 20,
                'credits' => 10,
                'days' => null,
                'seats' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Mensalidade',
                'type' => 'membership',
                'price_cents' => 14400,
                'currency' => 'EUR',
                'is_active' => false,
                'sort_order' => 30,
                'credits' => null,
                'days' => 30,
                'seats' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Grupo privado',
                'type' => 'group_hour',
                'price_cents' => 10200,
                'currency' => 'EUR',
                'is_active' => true,
                'sort_order' => 40,
                'credits' => null,
                'days' => null,
                'seats' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
