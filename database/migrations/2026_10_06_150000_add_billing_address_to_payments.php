<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('billing_address')->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            $table->string('billing_city', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['billing_address', 'billing_postal_code', 'billing_city']));
    }
};
