<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_credit_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('source_booking_id')->nullable()->unique()->constrained('bookings')->nullOnDelete();
            $table->unsignedInteger('credits_granted');
            $table->unsignedInteger('remaining_credits');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['user_id', 'expires_at']);
        });
        DB::table('products')->whereIn('type', ['single_hour', 'session_pack'])->update(['days' => 90]);
        Schema::table('bookings', fn (Blueprint $table) => $table->foreignId('session_credit_lot_id')->nullable()->constrained()->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('session_credit_lot_id'));
        Schema::dropIfExists('session_credit_lots');
    }
};
