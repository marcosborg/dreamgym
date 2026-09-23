<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en', 120)->nullable()->after('name');
        });

        foreach ([
            'Hora individual' => 'Individual hour',
            'Sessão Única' => 'Single session',
            'Pack 10 sessões' => '10-session pack',
            'Pack 6' => 'Pack 6',
            'Pack 12' => 'Pack 12',
            'Mensalidade' => 'Monthly membership',
            'Plano 15' => 'Plan 15',
            'Plano 30' => 'Plan 30',
            'Grupo privado' => 'Private group',
            'Grupo Privado' => 'Private group',
        ] as $portuguese => $english) {
            DB::table('products')->where('name', $portuguese)->whereNull('name_en')->update(['name_en' => $english]);
        }
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('name_en'));
    }
};
