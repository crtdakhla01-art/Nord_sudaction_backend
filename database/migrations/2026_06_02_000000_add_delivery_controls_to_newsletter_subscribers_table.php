<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('unsubscribe_token', 64)->nullable()->unique()->after('email');
            $table->timestamp('unsubscribed_at')->nullable()->index()->after('consent');
            $table->boolean('is_suppressed')->default(false)->index()->after('unsubscribed_at');
            $table->timestamp('suppressed_at')->nullable()->after('is_suppressed');
            $table->string('suppression_reason', 255)->nullable()->after('suppressed_at');
        });

        DB::table('newsletter_subscribers')
            ->select(['id'])
            ->whereNull('unsubscribe_token')
            ->orderBy('id')
            ->chunkById(100, function ($subscribers): void {
                foreach ($subscribers as $subscriber) {
                    $token = $this->generateUniqueToken();

                    DB::table('newsletter_subscribers')
                        ->where('id', $subscriber->id)
                        ->update(['unsubscribe_token' => $token]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn([
                'unsubscribe_token',
                'unsubscribed_at',
                'is_suppressed',
                'suppressed_at',
                'suppression_reason',
            ]);
        });
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::lower(Str::random(48));
        } while (DB::table('newsletter_subscribers')->where('unsubscribe_token', $token)->exists());

        return $token;
    }
};
