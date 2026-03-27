<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'name' => 'Sami Benali',
                'email' => 'sami@example.com',
                'message' => 'Bonjour, je souhaite devenir benevole dans vos actions locales.',
            ],
            [
                'name' => 'Leila Amrani',
                'email' => 'leila@example.com',
                'message' => 'Nous souhaitons proposer un partenariat avec notre association regionale.',
            ],
            [
                'name' => 'Youssef Idrissi',
                'email' => 'youssef@example.com',
                'message' => 'Pouvez-vous partager les conditions pour publier une opportunite ?',
            ],
        ];

        foreach ($messages as $message) {
            ContactMessage::query()->firstOrCreate($message);
        }
    }
}
