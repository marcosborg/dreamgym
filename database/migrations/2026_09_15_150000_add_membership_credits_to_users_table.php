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
            $table->unsignedInteger('membership_credits')->default(0)->after('session_credits');
        });

        if (Schema::hasTable('products')) {
            $credits = (int) (DB::table('products')
                ->where('type', 'membership')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->value('credits') ?: 30);

            DB::table('users')
                ->where('membership_expires_at', '>', now())
                ->update(['membership_credits' => $credits]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('membership_credits');
        });
    }
};
