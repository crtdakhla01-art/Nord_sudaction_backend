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
            $table->date('birth_date')->nullable(false)->change();
            $table->string('profession', 150)->nullable(false)->change();
            $table->string('organization', 200)->nullable(false)->change();

            $table->json('participant_profiles')->nullable(false)->change();
            $table->json('investment_sectors')->nullable(false)->change();
            $table->json('confirmed_activities')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->change();
            $table->string('profession', 150)->nullable()->change();
            $table->string('organization', 200)->nullable()->change();

            $table->json('participant_profiles')->nullable()->change();
            $table->json('investment_sectors')->nullable()->change();
            $table->json('confirmed_activities')->nullable()->change();
        });
    }
};
