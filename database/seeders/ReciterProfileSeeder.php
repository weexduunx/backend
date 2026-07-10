<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReciterProfile;

class ReciterProfileSeeder extends Seeder
{
    public function run(): void
    {
        // Codes alignés sur quran-app/stores/reciterStore.ts (champ audioApiId).
        // quran_com_recitation_id : identifiant de récitation sur api.quran.com/api/v4,
        // utilisé pour récupérer les vrais timings audio par mot (segments). Laissé à null
        // quand le récitateur n'existe pas sur Quran.com (repli sur l'heuristique locale).
        $reciters = [
            [
                'code' => 'ar.alafasy',
                'quran_com_recitation_id' => 7,
                'name' => 'Mishary Rashid Alafasy',
                'average_speed' => 85,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.abdurrahmaansudais',
                'quran_com_recitation_id' => 3,
                'name' => 'Abdul Rahman Al-Sudais',
                'average_speed' => 90,
                'pause_multiplier' => 1.20,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.saadalghamdi',
                'quran_com_recitation_id' => null,
                'name' => 'Saad Al-Ghamidi',
                'average_speed' => 80,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.ahmedalajamy',
                'quran_com_recitation_id' => null,
                'name' => 'Ahmed Ali Al-Ajmi',
                'average_speed' => 82,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.maheralmuaiqly',
                'quran_com_recitation_id' => null,
                'name' => 'Maher Al-Muaiqly',
                'average_speed' => 80,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.naseralqatami',
                'quran_com_recitation_id' => null,
                'name' => 'Nasser Al-Qatami',
                'average_speed' => 78,
                'pause_multiplier' => 1.30,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.abdulbasitmurattal',
                'quran_com_recitation_id' => 2,
                'name' => 'Abdulbasit Abdussamad',
                'average_speed' => 70,
                'pause_multiplier' => 1.60,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.minshawi',
                'quran_com_recitation_id' => 9,
                'name' => 'Mohamed Siddiq Al-Minshawi',
                'average_speed' => 68,
                'pause_multiplier' => 1.70,
                'tajweed_style' => 'extensive',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.yasserdosari',
                'quran_com_recitation_id' => null,
                'name' => 'Yasser Al-Dosari',
                'average_speed' => 85,
                'pause_multiplier' => 1.25,
                'tajweed_style' => 'moderate',
                'supported_verses' => [],
                'is_active' => true
            ],
            [
                'code' => 'ar.hanirifai',
                'quran_com_recitation_id' => 5,
                'name' => 'Hani Ar-Rifai',
                'average_speed' => 75,
                'pause_multiplier' => 1.40,
                'tajweed_style' => 'moderate',
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
