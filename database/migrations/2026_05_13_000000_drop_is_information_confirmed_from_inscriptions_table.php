<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('inscriptions', 'is_information_confirmed')) {
                $table->dropColumn('is_information_confirmed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('inscriptions', 'is_information_confirmed')) {
                $table->boolean('is_information_confirmed')->default(false)->after('participation_fee');
            }
        });
    }
};
