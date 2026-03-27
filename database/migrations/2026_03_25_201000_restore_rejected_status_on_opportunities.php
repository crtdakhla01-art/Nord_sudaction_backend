<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE opportunities MODIFY status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('opportunities')
                ->where('status', 'rejected')
                ->update(['status' => 'pending']);

            DB::statement("ALTER TABLE opportunities MODIFY status ENUM('pending','accepted') NOT NULL DEFAULT 'pending'");
        }
    }
};
