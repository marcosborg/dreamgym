<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('session_credits')->default(0)->after('is_admin');
            $table->timestamp('membership_expires_at')->nullable()->after('session_credits');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_type')->default('single_hour')->after('user_id');
            $table->unsignedInteger('seats_reserved')->default(1)->after('booking_type');
            $table->string('paid_with')->nullable()->after('payment_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('booking_id')->constrained()->nullOnDelete();
            $table->string('product_type')->default('single_hour')->after('user_id');
        });

        DB::statement('ALTER TABLE payments MODIFY booking_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'product_type']);
        });

        DB::statement('ALTER TABLE payments MODIFY booking_id BIGINT UNSIGNED NOT NULL');

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_type', 'seats_reserved', 'paid_with']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['session_credits', 'membership_expires_at']);
        });
    }
};
