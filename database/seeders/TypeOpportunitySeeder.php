<?php

namespace Database\Seeders;

use App\Models\TypeOpportunity;
use Illuminate\Database\Seeder;

class TypeOpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $typeNames = [
            'Projets d\'investissement',
            'Fonds de commerce (tourisme, restauration...)',
            'Partenariats',
            'Appels a projets',
        ];

        foreach ($typeNames as $typeName) {
            TypeOpportunity::query()->firstOrCreate([
                'name' => $typeName,
            ]);
        }
    }
}
