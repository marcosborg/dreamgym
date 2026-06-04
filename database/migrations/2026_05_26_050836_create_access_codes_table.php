<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->string('provision_status')->default('pending');
            $table->json('lock_response_log')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id']);
            $table->index(['code', 'valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_codes');
    }
};
