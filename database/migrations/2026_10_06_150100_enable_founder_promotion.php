<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Setting::whereKey('promotion_image')->exists()) {
            $path = 'promotions/founder-october-2026.jpg';
            Storage::disk('public')->put($path, file_get_contents(public_path($path)));
            Setting::setValue('promotion_image', $path);
            Setting::setValue('promotion_alt', 'Sócio Fundador Dream Gym: Plano 30, 30 sessões por mês, 40 euros por mês para os primeiros 30 membros. Mantém o preço com renovação contínua e três dias de tolerância.');
            Setting::setValue('promotion_enabled', true);
        }
    }

    public function down(): void
    {
        // Keep administrator-managed content when rolling back code.
    }
};
