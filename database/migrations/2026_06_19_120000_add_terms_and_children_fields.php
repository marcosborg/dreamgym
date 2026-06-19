<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('bringing_children')->default(false)->after('locale');
            $table->timestamp('children_responsibility_accepted_at')->nullable()->after('bringing_children');
            $table->timestamp('terms_accepted_at')->nullable()->after('children_responsibility_accepted_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'bringing_children',
                'children_responsibility_accepted_at',
                'terms_accepted_at',
            ]);
        });
    }
};
