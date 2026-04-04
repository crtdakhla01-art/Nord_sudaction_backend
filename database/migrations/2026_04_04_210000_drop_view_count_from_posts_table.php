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
        // Safety check: only alter the table if the column still exists.
        if (Schema::hasColumn('posts', 'view_count')) {
            Schema::table('posts', function (Blueprint $table) {
                // Remove the no-longer-used counter column.
                $table->dropColumn('view_count');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safety check: only recreate the column when it is missing.
        if (! Schema::hasColumn('posts', 'view_count')) {
            Schema::table('posts', function (Blueprint $table) {
                // Restore previous behavior with a non-null default value.
                $table->unsignedBigInteger('view_count')->default(0)->after('is_featured');
            });
        }
    }
};
