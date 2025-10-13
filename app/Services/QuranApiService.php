<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Surah;
use App\Models\Verse;

class QuranApiService
{
    private string $baseUrl = 'https://api.alquran.cloud/v1';
    private int $cacheTimeHours = 24;

    public function fetchQuranData(): array
    {
        $arabicData = $this->fetchEdition('ar.uthmani');
        $frenchData = $this->fetchEdition('fr.hamidullah');

        return [
            'arabic' => $arabicData,
            'french' => $frenchData
        ];
    }

    public function fetchEdition(string $edition): array
    {
        $cacheKey = "quran_edition_{$edition}";

        return Cache::remember($cacheKey, $this->cacheTimeHours * 3600, function () use ($edition) {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/quran/{$edition}");

            if ($response->successful()) {
                return $response->json()['data'];
            }

            throw new \Exception("Failed to fetch Quran data for edition: {$edition}");
        });
    }

    public function fetchSurahData(int $surahNumber): array
    {
        $cacheKey = "surah_data_{$surahNumber}";

        return Cache::remember($cacheKey, $this->cacheTimeHours * 3600, function () use ($surahNumber) {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/surah/{$surahNumber}");

            if ($response->successful()) {
                return $response->json()['data'];
            }

            throw new \Exception("Failed to fetch Surah data for number: {$surahNumber}");
        });
    }

    public function syncQuranData(): void
    {
        for ($surahNumber = 1; $surahNumber <= 114; $surahNumber++) {
            $arabicData = $this->fetchSurahWithEdition($surahNumber, 'ar.uthmani');
            $frenchData = $this->fetchSurahWithEdition($surahNumber, 'fr.hamidullah');

            $surah = Surah::updateOrCreate(
                ['number' => $arabicData['number']],
                [
                    'name_arabic' => $arabicData['name'],
                    'name_french' => $frenchData['englishName'],
                    'name_transliteration' => $arabicData['englishNameTranslation'],
                    'verses_count' => $arabicData['numberOfAyahs'],
                    'revelation_type' => $arabicData['revelationType'],
                    'revelation_place' => $arabicData['revelationType'] === 'Meccan' ? 'Mecca' : 'Medina'
                ]
            );

            $this->syncVerses($arabicData, $frenchData, $surah);

            echo "Synced Surah {$surahNumber}: {$arabicData['name']}\n";
        }
    }

    public function fetchSurahWithEdition(int $surahNumber, string $edition): array
    {
        $cacheKey = "surah_{$surahNumber}_{$edition}";

        return Cache::remember($cacheKey, $this->cacheTimeHours * 3600, function () use ($surahNumber, $edition) {
            $response = Http::withoutVerifying()->get("{$this->baseUrl}/surah/{$surahNumber}/{$edition}");

            if ($response->successful()) {
                return $response->json()['data'];
            }

            throw new \Exception("Failed to fetch Surah {$surahNumber} with edition {$edition}");
        });
    }

    private function syncVerses(array $arabicSurah, array $frenchSurah, Surah $surah): void
    {
        foreach ($arabicSurah['ayahs'] as $index => $arabicVerse) {
            $frenchVerse = $frenchSurah['ayahs'][$index];

            Verse::updateOrCreate(
                [
                    'surah_id' => $surah->id,
                    'verse_number' => $arabicVerse['numberInSurah']
                ],
                [
                    'global_number' => $arabicVerse['number'],
                    'text_arabic' => trim($arabicVerse['text']),
                    'text_french' => trim($frenchVerse['text']),
                    'juz' => $arabicVerse['juz'] ?? null,
                    'page' => $arabicVerse['page'] ?? null,
                    'rub' => $arabicVerse['ruku'] ?? null,
                    'hizb' => $arabicVerse['hizbQuarter'] ?? null
                ]
            );
        }
    }
}
