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
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 200);
            $table->date('birth_date')->nullable();
            $table->string('city', 120);
            $table->string('phone', 50);
            $table->string('email', 255);
            $table->string('profession', 150)->nullable();
            $table->string('organization', 200)->nullable();

            $table->json('participant_profiles')->nullable();
            $table->string('participant_profile_other', 120)->nullable();

            $table->json('investment_sectors')->nullable();
            $table->string('investment_sector_other', 120)->nullable();

            $table->json('confirmed_activities')->nullable();

            $table->unsignedInteger('participation_fee')->default(1500);
            $table->boolean('is_information_confirmed')->default(false);
            $table->boolean('is_terms_accepted')->default(false);

            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['is_paid', 'created_at']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
