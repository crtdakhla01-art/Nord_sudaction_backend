<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'titre')) {
                $table->string('titre')->nullable()->after('id');
            }

            if (! Schema::hasColumn('opportunities', 'ville')) {
                $table->string('ville')->nullable()->after('titre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn(['titre', 'ville']);
        });
    }
};
