<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('opportunities', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (! Schema::hasColumn('opportunities', 'budget')) {
                $table->decimal('budget', 12, 2)->nullable()->after('description');
            }

            if (! Schema::hasColumn('opportunities', 'phone')) {
                $table->string('phone', 50)->nullable()->after('budget');
            }

            if (! Schema::hasColumn('opportunities', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('opportunities', 'type_id')) {
                $table->foreignId('type_id')
                    ->nullable()
                    ->after('email')
                    ->constrained('types_opportunities')
                    ->nullOnDelete();
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('opportunities', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('opportunities', 'location')) {
                $table->dropColumn('location');
            }

            if (Schema::hasColumn('opportunities', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('opportunities', 'deadline')) {
                $table->dropColumn('deadline');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::table('opportunities')
                ->where('status', 'rejected')
                ->update(['status' => 'pending']);

            DB::statement("ALTER TABLE opportunities MODIFY status ENUM('pending','accepted') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (! Schema::hasColumn('opportunities', 'title')) {
                $table->string('title')->nullable();
            }

            if (! Schema::hasColumn('opportunities', 'location')) {
                $table->string('location')->nullable();
            }

            if (! Schema::hasColumn('opportunities', 'type')) {
                $table->string('type')->nullable();
            }

            if (! Schema::hasColumn('opportunities', 'deadline')) {
                $table->date('deadline')->nullable();
            }

            if (Schema::hasColumn('opportunities', 'type_id')) {
                $table->dropConstrainedForeignId('type_id');
            }

            if (Schema::hasColumn('opportunities', 'email')) {
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('opportunities', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('opportunities', 'budget')) {
                $table->dropColumn('budget');
            }

            if (Schema::hasColumn('opportunities', 'last_name')) {
                $table->dropColumn('last_name');
            }

            if (Schema::hasColumn('opportunities', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE opportunities MODIFY status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
