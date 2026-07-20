<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_trainers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('title_pt')->nullable()->after('name');
            $table->string('title_en')->nullable()->after('title_pt');
            $table->text('specialties_pt')->nullable()->after('title_en');
            $table->text('specialties_en')->nullable()->after('specialties_pt');
            $table->text('bio_pt')->nullable()->after('bio');
            $table->text('bio_en')->nullable()->after('bio_pt');
            $table->string('photo_path')->nullable()->after('bio_en');
            $table->boolean('show_email')->default(true)->after('photo_path');
            $table->boolean('show_phone')->default(true)->after('show_email');
            $table->boolean('show_whatsapp')->default(false)->after('show_phone');

            $table->unique('user_id');
        });

        DB::table('personal_trainers')->update([
            'bio_pt' => DB::raw('bio'),
            'bio_en' => DB::raw('bio'),
        ]);
    }

    public function down(): void
    {
        Schema::table('personal_trainers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn([
                'user_id',
                'title_pt', 'title_en', 'specialties_pt', 'specialties_en',
                'bio_pt', 'bio_en', 'photo_path', 'show_email', 'show_phone', 'show_whatsapp',
            ]);
        });
    }
};
