<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Surah;
use App\Models\Verse;

class QuranApiService
{
    /** Titres français des sourates (intitulés de la traduction Hamidullah) */
    private const FRENCH_TITLES = [
        "L'Ouverture",
        "La Vache",
        "La Famille d'Imran",
        "Les Femmes",
        "La Table servie",
        "Les Bestiaux",
        "Les Murailles",
        "Le Butin",
        "Le Repentir",
        "Jonas",
        "Hûd",
        "Joseph",
        "Le Tonnerre",
        "Abraham",
        "Al-Hijr",
        "Les Abeilles",
        "Le Voyage nocturne",
        "La Caverne",
        "Marie",
        "Tâ-Hâ",
        "Les Prophètes",
        "Le Pèlerinage",
        "Les Croyants",
        "La Lumière",
        "Le Discernement",
        "Les Poètes",
        "Les Fourmis",
        "Le Récit",
        "L'Araignée",
        "Les Romains",
        "Luqmân",
        "La Prosternation",
        "Les Coalisés",
        "Saba",
        "Le Créateur",
        "Yâ-Sîn",
        "Les Rangés",
        "Sâd",
        "Les Groupes",
        "Le Pardonneur",
        "Les Versets détaillés",
        "La Consultation",
        "L'Ornement",
        "La Fumée",
        "L'Agenouillée",
        "Al-Ahqâf",
        "Muhammad",
        "La Victoire éclatante",
        "Les Appartements",
        "Qâf",
        "Qui éparpillent",
        "Le Mont Tûr",
        "L'Étoile",
        "La Lune",
        "Le Tout Miséricordieux",
        "L'Événement",
        "Le Fer",
        "La Discussion",
        "L'Exode",
        "L'Éprouvée",
        "Le Rang",
        "Le Vendredi",
        "Les Hypocrites",
        "La Grande Perte",
        "Le Divorce",
        "L'Interdiction",
        "La Royauté",
        "La Plume",
        "Celle qui montre la vérité",
        "Les Voies d'ascension",
        "Noé",
        "Les Djinns",
        "L'Enveloppé",
        "Le Revêtu d'un manteau",
        "La Résurrection",
        "L'Homme",
        "Les Envoyés",
        "La Nouvelle",
        "Les Anges qui arrachent les âmes",
        "Il s'est renfrogné",
        "L'Obscurcissement",
        "La Rupture",
        "Les Fraudeurs",
        "La Déchirure",
        "Les Constellations",
        "L'Astre nocturne",
        "Le Très-Haut",
        "L'Enveloppante",
        "L'Aube",
        "La Cité",
        "Le Soleil",
        "La Nuit",
        "Le Jour montant",
        "Le Réconfort",
        "Le Figuier",
        "L'Adhérence",
        "La Destinée",
        "La Preuve",
        "La Secousse",
        "Les Coursiers",
        "Le Fracas",
        "La Course aux richesses",
        "Le Temps",
        "Les Calomniateurs",
        "L'Éléphant",
        "Coraïch",
        "L'Ustensile",
        "L'Abondance",
        "Les Infidèles",
        "Le Secours",
        "Les Fibres",
        "Le Monothéisme pur",
        "L'Aube naissante",
        "Les Hommes",
    ];

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
                    // Avant : name_french recevait la translittération et
                    // name_transliteration le nom anglais (« The Opening »)
                    'name_french' => self::FRENCH_TITLES[$surahNumber - 1],
                    'name_transliteration' => $arabicData['englishName'],
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
