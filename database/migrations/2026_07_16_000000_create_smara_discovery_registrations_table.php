<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smara_discovery_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 200);
            $table->string('city', 120);
            $table->string('phone', 50);
            $table->string('email', 255);
            $table->string('age_group', 30);
            $table->boolean('has_visited_es_smara');
            $table->string('interest_level', 30);
            $table->string('participants_count', 20);
            $table->string('preferred_duration', 40);
            $table->string('departure_city', 120);
            $table->string('budget', 100);
            $table->json('preferred_activities');
            $table->boolean('notify_first_date');
            $table->timestamps();

            $table->index('email');
            $table->index(['interest_level', 'created_at']);
            $table->index('notify_first_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smara_discovery_registrations');
    }
};
