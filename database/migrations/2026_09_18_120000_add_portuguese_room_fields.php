<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('name_pt', 120)->nullable();
            $table->text('description_pt')->nullable();
        });
        DB::table('rooms')->where('name', 'Private training room')->update([
            'name_pt' => 'Sala de treino privado',
        ]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['name_pt', 'description_pt']);
        });
    }
};
