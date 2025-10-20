<?php

namespace App\Services;

use App\Models\Verse;
use App\Models\ReciterProfile;
use App\Models\VerseWordTiming;
use App\Models\WordTiming;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordSyncService
{
    private const CACHE_PREFIX = 'word_sync_';
    private const CACHE_DURATION = 3600; // 1 heure

    /**
     * Récupère les données de timing pour un verset avec un récitateur donné
     */
    public function getVerseTimingData(
        int $globalNumber,
        string $reciterCode,
        string $arabicText,
        int $audioDuration
    ): array {
        $cacheKey = self::CACHE_PREFIX . "{$globalNumber}_{$reciterCode}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
            $globalNumber, $reciterCode, $arabicText, $audioDuration
        ) {
            // Récupérer le verset
            $verse = Verse::where('global_number', $globalNumber)->first();
            if (!$verse) {
                throw new Exception("Verse not found with global number: {$globalNumber}");
            }

            // Récupérer le profil du récitateur
            $reciterProfile = ReciterProfile::where('code', $reciterCode)->active()->first();
            if (!$reciterProfile) {
                throw new Exception("Reciter profile not found: {$reciterCode}");
            }

            // Vérifier si on a déjà des données en cache DB
            $existingTiming = VerseWordTiming::where('verse_id', $verse->id)
                ->where('reciter_profile_id', $reciterProfile->id)
                ->first();

            if ($existingTiming) {
                Log::info("Using cached timing data for verse {$globalNumber} with reciter {$reciterCode}");
                return $this->formatTimingResponse($existingTiming);
            }

            try {
                // 1. Essayer d'obtenir les données précises de quran-align
                $timingData = $this->fetchQuranAlignData($globalNumber, $reciterCode, $verse, $reciterProfile);
                Log::info("✅ Using quran-align data for verse {$globalNumber}");
            } catch (Exception $e) {
                // 2. Fallback : générer un timing intelligent
                Log::info("⚠️ Falling back to intelligent estimation for verse {$globalNumber}: " . $e->getMessage());
                $timingData = $this->generateIntelligentTiming(
                    $verse, $reciterProfile, $arabicText, $audioDuration
                );
            }

            return $this->formatTimingResponse($timingData);
        });
    }

    /**
     * Récupère les données de quran-align
     */
    private function fetchQuranAlignData(
        int $globalNumber,
        string $reciterCode,
        Verse $verse,
        ReciterProfile $reciterProfile
    ): VerseWordTiming {
        // Pour l'instant, simulation de l'indisponibilité
        // TODO: Implémenter la vraie récupération quand les données seront disponibles
        throw new Exception('Quran-align data temporarily unavailable');
    }

    /**
     * Génère un timing intelligent basé sur l'analyse du texte
     */
    private function generateIntelligentTiming(
        Verse $verse,
        ReciterProfile $reciterProfile,
        string $arabicText,
        int $audioDuration
    ): VerseWordTiming {
        // Nettoyer et diviser le texte en mots
        $cleanText = $this->cleanArabicText($arabicText);
        $words = array_filter(explode(' ', $cleanText));

        // Analyser chaque mot
        $wordAnalysis = [];
        foreach ($words as $index => $word) {
            $wordAnalysis[] = [
                'text' => $word,
                'index' => $index,
                'weight' => $this->calculateWordWeight($word, $index, $words),
                'tajweed_info' => $this->detectTajweedInfo($word)
            ];
        }

        // Calculer la durée totale pondérée
        $totalWeight = array_sum(array_column($wordAnalysis, 'weight'));

        // Générer les timings
        $wordsData = [];
        $currentTime = 0;

        foreach ($wordAnalysis as $wordInfo) {
            $baseDuration = ($audioDuration / $totalWeight) * $wordInfo['weight'];

            // Appliquer les modifications du profil récitateur
            $adjustedDuration = $baseDuration;
            if (isset($wordInfo['tajweed_info']['weight'])) {
                $adjustedDuration *= $wordInfo['tajweed_info']['weight'];
            }
            if ($wordInfo['tajweed_info']['type'] === 'pause') {
                $adjustedDuration *= $reciterProfile->pause_multiplier;
            }

            $startTime = round($currentTime);
            $endTime = round($currentTime + $adjustedDuration);

            $wordsData[] = [
                'word_index' => $wordInfo['index'],
                'arabic_text' => $wordInfo['text'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'confidence' => 0.75,
                'tajweed_info' => $wordInfo['tajweed_info']
            ];

            $currentTime += $adjustedDuration;
        }

        // Créer et sauvegarder les données de timing
        $verseWordTiming = VerseWordTiming::create([
            'verse_id' => $verse->id,
            'reciter_profile_id' => $reciterProfile->id,
            'total_duration' => $audioDuration,
            'words_data' => $wordsData,
            'source' => 'estimated',
            'accuracy' => 0.75,
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'word_count' => count($words),
                'reciter_style' => $reciterProfile->tajweed_style
            ]
        ]);

        return $verseWordTiming;
    }

    /**
     * Nettoie le texte arabe
     */
    private function cleanArabicText(string $text): string
    {
        return trim(preg_replace([
            '/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', // Supprimer diacritiques
            '/\s+/', // Normaliser espaces
        ], [
            '',
            ' '
        ], $text));
    }

    /**
     * Calcule le poids d'un mot pour déterminer sa durée relative
     */
    private function calculateWordWeight(string $word, int $index, array $allWords): float
    {
        $weight = 1.0;

        // Longueur du mot (sans diacritiques)
        $baseLength = mb_strlen(preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $word));
        $weight += $baseLength * 0.15;

        // Position dans le verset
        $position = count($allWords) > 1 ? $index / (count($allWords) - 1) : 0;
        if ($position < 0.1 || $position > 0.9) {
            $weight *= 1.3; // Premier et dernier mots plus lents
        } elseif ($position > 0.4 && $position < 0.6) {
            $weight *= 1.1; // Milieu légèrement plus lent
        }

        // Voyelles longues
        $prolongationSigns = ['ا', 'و', 'ي', 'آ', 'أ', 'إ', 'ى'];
        $prolongationCount = 0;
        foreach ($prolongationSigns as $sign) {
            $prolongationCount += mb_substr_count($word, $sign);
        }
        if ($prolongationCount > 1) {
            $weight *= (1 + $prolongationCount * 0.2);
        }

        // Signes de pause et arrêt
        if (mb_strpos($word, 'ۚ') !== false) $weight *= 1.5; // Pause obligatoire
        if (mb_strpos($word, 'ۖ') !== false) $weight *= 1.3; // Pause recommandée
        if (mb_strpos($word, 'ۗ') !== false) $weight *= 1.2; // Pause permise
        if (mb_strpos($word, 'ۘ') !== false) $weight *= 1.4; // Arrêt obligatoire

        // Mots de liaison plus rapides
        $quickWords = ['وَ', 'فَ', 'بِ', 'لِ', 'مِن', 'إِلَى', 'عَلَى', 'فِي', 'مَع', 'قَد', 'لَا', 'مَا'];
        foreach ($quickWords as $qw) {
            if (mb_strpos($word, $qw) === 0 || $word === $qw) {
                $weight *= 0.7;
                break;
            }
        }

        // Noms d'Allah et mots sacrés plus lents
        $sacredWords = ['اللَّهُ', 'اللَّهِ', 'اللَّه', 'الرَّحْمَٰنِ', 'الرَّحِيمِ', 'رَبِّ'];
        foreach ($sacredWords as $sw) {
            $cleanSw = preg_replace('/[\x{064B}-\x{0652}\x{0670}]/u', '', $sw);
            if (mb_strpos($word, $cleanSw) !== false) {
                $weight *= 1.4;
                break;
            }
        }

        return max(0.4, min(2.5, $weight));
    }

    /**
     * Détecte les informations tajweed dans un mot
     */
    private function detectTajweedInfo(string $word): array
    {
        // Pauses obligatoires et arrêts
        if (mb_strpos($word, 'ۚ') !== false || mb_strpos($word, 'ۘ') !== false) {
            return ['type' => 'pause', 'weight' => 1.8];
        }

        // Pauses recommandées
        if (mb_strpos($word, 'ۖ') !== false || mb_strpos($word, 'ۗ') !== false || mb_strpos($word, 'ۙ') !== false) {
            return ['type' => 'pause', 'weight' => 1.4];
        }

        // Prolongations naturelles
        $longVowels = ['ا', 'و', 'ي', 'آ', 'أ', 'إ', 'ى', 'ؤ', 'ئ'];
        $prolongationScore = 0;
        foreach ($longVowels as $vowel) {
            $prolongationScore += mb_substr_count($word, $vowel);
        }

        if ($prolongationScore >= 2) {
            return ['type' => 'prolongation', 'weight' => 1.2 + ($prolongationScore * 0.1)];
        }

        // Emphases et lettres solaires
        $solarLetters = ['ت', 'ث', 'د', 'ذ', 'ر', 'ز', 'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ل', 'ن'];
        $hasEmphasis = mb_strpos($word, 'ۧ') !== false || mb_strpos($word, 'ۨ') !== false;

        if ($hasEmphasis) {
            return ['type' => 'emphasis', 'weight' => 1.2];
        }

        foreach ($solarLetters as $letter) {
            if (mb_strpos($word, 'ال' . $letter) === 0) {
                return ['type' => 'emphasis', 'weight' => 1.2];
            }
        }

        // Lettres de gorge
        $throatLetters = ['ء', 'ه', 'ع', 'ح', 'غ', 'خ'];
        foreach ($throatLetters as $letter) {
            if (mb_strpos($word, $letter) !== false) {
                return ['type' => 'emphasis', 'weight' => 1.1];
            }
        }

        // Shadda
        if (mb_strpos($word, 'ّ') !== false) {
            return ['type' => 'emphasis', 'weight' => 1.15];
        }

        return ['type' => 'normal', 'weight' => 1.0];
    }

    /**
     * Formate la réponse des données de timing
     */
    private function formatTimingResponse(VerseWordTiming $timing): array
    {
        return [
            'verse_id' => $timing->verse_id,
            'global_number' => $timing->verse->global_number,
            'reciter_code' => $timing->reciterProfile->code,
            'total_duration' => $timing->total_duration,
            'words' => $timing->words_data,
            'source' => $timing->source,
            'accuracy' => (float) $timing->accuracy,
            'last_updated' => $timing->updated_at->toISOString(),
            'metadata' => $timing->metadata
        ];
    }

    /**
     * Trouve le mot actuel basé sur le temps
     */
    public function findCurrentWord(array $timingData, int $currentTime): int
    {
        $words = $timingData['words'] ?? [];

        foreach ($words as $index => $word) {
            if ($currentTime >= $word['start_time'] && $currentTime <= $word['end_time']) {
                return $index;
            }
        }

        // Si aucun mot exact, trouver le plus proche
        for ($i = 0; $i < count($words); $i++) {
            if ($words[$i]['start_time'] > $currentTime) {
                return max(0, $i - 1);
            }
        }

        return count($words) - 1;
    }

    /**
     * Nettoie le cache
     */
    public function clearCache(int $globalNumber = null, string $reciterCode = null): void
    {
        if ($globalNumber && $reciterCode) {
            $cacheKey = self::CACHE_PREFIX . "{$globalNumber}_{$reciterCode}";
            Cache::forget($cacheKey);
        } else {
            // Nettoyer tout le cache word_sync
            // Note: Cette approche simple peut être optimisée avec tags de cache
            Cache::flush();
        }
    }

    /**
     * Obtient les statistiques du service
     */
    public function getStats(): array
    {
        return [
            'total_verse_timings' => VerseWordTiming::count(),
            'by_source' => VerseWordTiming::selectRaw('source, count(*) as count')
                ->groupBy('source')
                ->pluck('count', 'source')
                ->toArray(),
            'active_reciters' => ReciterProfile::active()->count(),
            'cache_prefix' => self::CACHE_PREFIX
        ];
    }
}