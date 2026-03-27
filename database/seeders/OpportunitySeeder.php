<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\TypeOpportunity;
use Illuminate\Database\Seeder;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $typesByName = TypeOpportunity::query()->pluck('id', 'name');

        $opportunities = [
            [
                'titre' => 'Centre de formation numerique',
                'ville' => 'Casablanca',
                'first_name' => 'Amina',
                'last_name' => 'Tazi',
                'description' => 'Recherche partenaire pour un projet de centre de formation en competences numeriques.',
                'budget' => 250000.00,
                'phone' => '+212600000111',
                'email' => 'amina.tazi@example.com',
                'type_name' => 'Projets d\'investissement',
                'status' => 'accepted',
            ],
            [
                'titre' => 'Restauration solidaire periurbaine',
                'ville' => 'Rabat',
                'first_name' => 'Hassan',
                'last_name' => 'El Fassi',
                'description' => 'Ouverture d un fonds de commerce dans la restauration solidaire en zone periurbaine.',
                'budget' => 180000.00,
                'phone' => '+212600000222',
                'email' => 'hassan.fassi@example.com',
                'type_name' => 'Fonds de commerce (tourisme, restauration...)',
                'status' => 'accepted',
            ],
            [
                'titre' => 'Partenariat cooperatives locales',
                'ville' => 'Fes',
                'first_name' => 'Sara',
                'last_name' => 'Naji',
                'description' => 'Proposition de partenariat avec des cooperatives locales pour des activites de formation.',
                'budget' => 90000.00,
                'phone' => '+212600000333',
                'email' => 'sara.naji@example.com',
                'type_name' => 'Partenariats',
                'status' => 'accepted',
            ],
            [
                'titre' => 'Appel a projets communautaires',
                'ville' => 'Marrakech',
                'first_name' => 'Karim',
                'last_name' => 'Berrada',
                'description' => 'Montage d un appel a projets pour le developpement de micro-initiatives communautaires.',
                'budget' => 120000.00,
                'phone' => '+212600000444',
                'email' => 'karim.berrada@example.com',
                'type_name' => 'Appels a projets',
                'status' => 'accepted',
            ],
        ];

        foreach ($opportunities as $entry) {
            $typeId = $typesByName[$entry['type_name']] ?? null;
            if (! $typeId) {
                continue;
            }

            Opportunity::query()->updateOrCreate(
                [
                    'email' => $entry['email'],
                ],
                [
                    'titre' => $entry['titre'],
                    'ville' => $entry['ville'],
                    'first_name' => $entry['first_name'],
                    'last_name' => $entry['last_name'],
                    'description' => $entry['description'],
                    'budget' => $entry['budget'],
                    'phone' => $entry['phone'],
                    'type_id' => $typeId,
                    'status' => $entry['status'],
                    'image' => null,
                ]
            );
        }
    }
}
