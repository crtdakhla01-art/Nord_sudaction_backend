<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->foreignId('gallery_categorie_id')
                ->nullable()
                ->after('disk_path')
                ->constrained('gallery_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gallery_images', function (Blueprint $table) {
            $table->dropForeign(['gallery_categorie_id']);
            $table->dropColumn('gallery_categorie_id');
        });
    }
};
