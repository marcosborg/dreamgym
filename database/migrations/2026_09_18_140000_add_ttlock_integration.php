<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ttlock_connections', function (Blueprint $table) {
            $table->id();
            $table->text('credentials');
            $table->timestamps();
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('ttlock_lock_id')->nullable();
        });
        Schema::table('access_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('ttlock_lock_id')->nullable();
            $table->unsignedBigInteger('ttlock_passcode_id')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('access_notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('access_codes', fn (Blueprint $table) => $table->dropColumn(['ttlock_lock_id', 'ttlock_passcode_id', 'revoked_at', 'access_notified_at']));
        Schema::table('rooms', fn (Blueprint $table) => $table->dropColumn('ttlock_lock_id'));
        Schema::dropIfExists('ttlock_connections');
    }
};
