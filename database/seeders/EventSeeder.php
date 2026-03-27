<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Forum de la solidarite locale',
                'description' => 'Rencontre entre associations, benevoles et partenaires pour presenter des projets a impact social.',
                'date' => now()->addDays(10)->toDateString(),
                'location' => 'Casablanca',
            ],
            [
                'title' => 'Journee portes ouvertes ONG',
                'description' => 'Session d information sur les actions terrain, le mentorat et les opportunites de collaboration.',
                'date' => now()->addDays(20)->toDateString(),
                'location' => 'Rabat',
            ],
            [
                'title' => 'Atelier financement de projets',
                'description' => 'Atelier pratique pour structurer un dossier de financement et mobiliser des partenaires institutionnels.',
                'date' => now()->addDays(35)->toDateString(),
                'location' => 'Marrakech',
            ],
        ];

        foreach ($events as $event) {
            Event::query()->updateOrCreate(
                [
                    'title' => $event['title'],
                    'date' => $event['date'],
                ],
                $event
            );
        }
    }
}
