<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('opportunity_images')) {
            Schema::create('opportunity_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
                $table->string('path');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('opportunities', 'image')) {
            DB::table('opportunities')
                ->select('id', 'image')
                ->whereNotNull('image')
                ->where('image', '!=', '')
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    $inserts = [];

                    foreach ($rows as $row) {
                        $inserts[] = [
                            'opportunity_id' => $row->id,
                            'path' => $row->image,
                            'sort_order' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (! empty($inserts)) {
                        DB::table('opportunity_images')->insert($inserts);
                    }
                });

            Schema::table('opportunities', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('opportunities', 'image')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->string('image')->nullable();
            });
        }

        $firstImages = DB::table('opportunity_images')
            ->select('opportunity_id', 'path')
            ->orderBy('opportunity_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('opportunity_id')
            ->map(fn ($items) => $items->first()->path);

        foreach ($firstImages as $opportunityId => $path) {
            DB::table('opportunities')
                ->where('id', $opportunityId)
                ->update(['image' => $path]);
        }

        Schema::dropIfExists('opportunity_images');
    }
};
