<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tafsir;
use App\Models\Surah;

class TafsirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hafizList = [
            'Cheikh Ahmadou Bamba Mbacké',
            'Serigne Touba',
            'Imam Ousmane Kane',
            'Cheikh Tidiane Sy',
            'Serigne Sidy Moctar Mbacké'
        ];

        $surahs = Surah::take(10)->get();

        foreach ($surahs as $surah) {
            $hafiz = $hafizList[array_rand($hafizList)];

            Tafsir::create([
                'surah_id' => $surah->id,
                'hafiz_name' => $hafiz,
                'audio_file_path' => "tafsirs/surah_{$surah->number}_{$hafiz}.mp3",
                'audio_url' => "https://example.com/audio/tafsirs/surah_{$surah->number}_{$hafiz}.mp3",
                'duration_seconds' => rand(600, 3600),
                'file_size_bytes' => rand(5000000, 50000000),
                'language' => 'wo',
                'description' => "Tafsir de la sourate {$surah->name_french} par {$hafiz} en wolof",
                'is_available' => true
            ]);
        }
    }
}
