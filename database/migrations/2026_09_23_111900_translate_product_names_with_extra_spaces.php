<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $translations = [
            'hora individual' => 'Individual hour',
            'sessão única' => 'Single session',
            'pack 10 sessões' => '10-session pack',
            'pack 6' => 'Pack 6',
            'pack 12' => 'Pack 12',
            'mensalidade' => 'Monthly membership',
            'plano 15' => 'Plan 15',
            'plano 30' => 'Plan 30',
            'grupo privado' => 'Private group',
        ];
        foreach (DB::table('products')->whereNull('name_en')->get(['id', 'name']) as $product) {
            $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $product->name)));
            if (isset($translations[$normalized])) {
                DB::table('products')->where('id', $product->id)->whereNull('name_en')
                    ->update(['name_en' => $translations[$normalized]]);
            }
        }
    }

    public function down(): void
    {
        // Keep editable translations when rolling back this data repair.
    }
};
