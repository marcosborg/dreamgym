<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('booking_id')->nullable();
            $t->unsignedBigInteger('actor_id')->nullable();
            $t->string('kind');
            $t->integer('amount');
            $t->integer('balance');
            $t->string('reason');
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
        Schema::table('bookings', fn (Blueprint $t) => $t->boolean('suppress_notifications')->default(false));
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_movements');
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn('suppress_notifications'));
    }
};
