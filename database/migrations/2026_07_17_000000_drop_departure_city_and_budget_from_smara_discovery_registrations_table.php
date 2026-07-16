<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smara_discovery_registrations', function (Blueprint $table) {
            $table->dropColumn(['departure_city', 'budget']);
        });
    }

    public function down(): void
    {
        Schema::table('smara_discovery_registrations', function (Blueprint $table) {
            $table->string('departure_city', 120)->after('preferred_duration');
            $table->string('budget', 100)->after('departure_city');
        });
    }
};
