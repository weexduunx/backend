<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReciterProfile;

class ReciterProfileSeeder extends Seeder
{
    public function run(): void
    {
        $reciters = [
            [
                'code' => 'ar.alafasy',
                'name' => 'Mishary Rashid Alafasy',
                'average_speed' => 85,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'extensive',
                'supported_verses' => [], // À remplir selon disponibilité
                'is_active' => true
            ],
            [
                'code' => 'ar.husary',
                'name' => 'Mahmoud Khalil Al-Husary',
                'average_speed' => 75,
                'pause_multiplier' => 1.50,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.sudais',
                'name' => 'Abdul Rahman Al-Sudais',
                'average_speed' => 90,
                'pause_multiplier' => 1.20,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.abdulsamad',
                'name' => 'Abdulbasit Abdussamad',
                'average_speed' => 70,
                'pause_multiplier' => 1.60,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.minshawi',
                'name' => 'Mohamed Siddiq Al-Minshawi',
                'average_speed' => 68,
                'pause_multiplier' => 1.70,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ]
        ];

        foreach ($reciters as $reciter) {
            ReciterProfile::updateOrCreate(
                ['code' => $reciter['code']],
                $reciter
            );
        }
    }
}
