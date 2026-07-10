<?php

namespace App\Services;

use App\Models\Verse;
use App\Models\ReciterProfile;
use App\Models\VerseWordTiming;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnhancedWordSyncService extends WordSyncService
{
    private const ENHANCED_CACHE_PREFIX = 'enhanced_word_sync_';
    private const PACKAGE_DATA_CACHE_PREFIX = 'package_word_data_';
    private const CACHE_DURATION = 86400; // 24 hours in seconds
    private const QURAN_COM_API_BASE = 'https://api.quran.com/api/v4';

    /**
     * Get enhanced timing data using both our algorithm and package data
     */
    public function getEnhancedVerseTimingData(
        int $globalNumber,
        string $reciterCode,
        array $packageWords,
        int $audioDuration
    ): array {
        $cacheKey = self::ENHANCED_CACHE_PREFIX . "{$globalNumber}_{$reciterCode}_enhanced";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use (
            $globalNumber, $reciterCode, $packageWords, $audioDuration
        ) {
            $verse = Verse::where('global_number', $globalNumber)->first();
            if (!$verse) {
                throw new Exception("Verse not found with global number: {$globalNumber}");
            }

            $reciterProfile = ReciterProfile::where('code', $reciterCode)->active()->first();
            if (!$reciterProfile) {
                throw new Exception("Reciter profile not found: {$reciterCode}");
            }

            // Only short-circuit on real Quran.com segment timing: an existing
            // heuristic row must never block a later upgrade to real data (e.g.
            // rows created before this reciter got mapped to Quran.com, or from
            // a transient API failure)
            $existingQuranComTiming = VerseWordTiming::where('verse_id', $verse->id)
                ->where('reciter_profile_id', $reciterProfile->id)
                ->where('source', 'quran_com_segments')
                ->first();

            if ($existingQuranComTiming) {
                Log::info("Using cached Quran.com segment timing for verse {$globalNumber}");
                return $this->formatTimingResponse($existingQuranComTiming);
            }

            // Try real word-by-word timing from Quran.com's public API first
            $timingData = $this->fetchQuranComSegments($verse, $reciterProfile, $audioDuration);

            if (!$timingData) {
                // No real timing available (unmapped reciter or API failure):
                // reuse a previously generated heuristic row if we have one,
                // otherwise generate a fresh one
                $timingData = VerseWordTiming::where('verse_id', $verse->id)
                    ->where('reciter_profile_id', $reciterProfile->id)
                    ->where('source', 'enhanced_package')
                    ->first()
                    ?? $this->generateEnhancedTimingFromPackage(
                        $verse, $reciterProfile, $packageWords, $audioDuration
                    );
            }

            return $this->formatTimingResponse($timingData);
        });
    }

    /**
     * Fetch real per-word audio timing segments from the public Quran.com API,
     * the same data Quran.com itself uses for word-by-word highlighting.
     * Returns null (never throws) when unavailable, so callers can fall back
     * to the heuristic timing generator.
     */
    private function fetchQuranComSegments(
        Verse $verse,
        ReciterProfile $reciterProfile,
        int $audioDuration
    ): ?VerseWordTiming {
        if (!$reciterProfile->quran_com_recitation_id) {
            return null;
        }

        try {
            $verseKey = "{$verse->surah->number}:{$verse->verse_number}";

            $response = Http::timeout(5)->get(
                self::QURAN_COM_API_BASE . "/verses/by_key/{$verseKey}",
                [
                    'words' => 'true',
                    'audio' => $reciterProfile->quran_com_recitation_id,
                    'word_fields' => 'text_uthmani,translation',
                ]
            );

            if (!$response->successful()) {
                Log::warning("Quran.com API request failed for verse {$verseKey}: HTTP {$response->status()}");
                return null;
            }

            $data = $response->json('verse');
            $segments = $data['audio']['segments'] ?? null;
            $allWords = $data['words'] ?? [];

            if (!$segments || !$allWords) {
                return null;
            }

            $words = array_values(array_filter($allWords, fn ($w) => ($w['char_type_name'] ?? null) === 'word'));

            if (count($words) !== count($segments)) {
                Log::warning("Quran.com segments/word count mismatch for verse {$verseKey}: " . count($segments) . " segments vs " . count($words) . " words");
                return null;
            }

            $wordsTimingData = [];
            foreach ($segments as $index => $segment) {
                $word = $words[$index];
                $startTime = (int) $segment[2];
                $endTime = (int) $segment[3];

                $wordsTimingData[] = [
                    'word_index' => $index,
                    'package_word_id' => $word['id'] ?? $index + 1,
                    'position' => $word['position'] ?? $index + 1,
                    'arabic_text' => $word['text_uthmani'] ?? $word['text'] ?? '',
                    'transliteration' => $word['transliteration']['text'] ?? '',
                    'translation' => $word['translation']['text'] ?? '',
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration' => $endTime - $startTime,
                    'confidence' => 0.98, // Real recitation-aligned timing data
                    'char_type' => $word['char_type_name'] ?? 'word',
                    'page_number' => $word['page_number'] ?? null,
                    'line_number' => $word['line_number'] ?? null,
                    'audio_url' => $word['audio_url'] ?? null,
                    'enhanced_features' => $this->extractEnhancedFeatures([
                        'char_type_name' => $word['char_type_name'] ?? 'word',
                        'audio_url' => $word['audio_url'] ?? null,
                        'position' => $word['position'] ?? $index + 1,
                        'page_number' => $word['page_number'] ?? null,
                        'line_number' => $word['line_number'] ?? null,
                        'transliteration' => $word['transliteration'] ?? [],
                        'translation' => $word['translation'] ?? [],
                    ]),
                    'tajweed_info' => $this->detectEnhancedTajweedInfo([
                        'text' => $word['text_uthmani'] ?? $word['text'] ?? '',
                        'char_type_name' => $word['char_type_name'] ?? 'word',
                        'audio_url' => $word['audio_url'] ?? null,
                    ]),
                ];
            }

            // updateOrCreate: (verse_id, reciter_profile_id) is unique, and a
            // heuristic 'enhanced_package' row may already exist for this pair
            // from before it was upgradable to real Quran.com timing
            return VerseWordTiming::updateOrCreate(
                [
                    'verse_id' => $verse->id,
                    'reciter_profile_id' => $reciterProfile->id,
                ],
                [
                    'total_duration' => $audioDuration,
                    'words_data' => $wordsTimingData,
                    'source' => 'quran_com_segments',
                    'accuracy' => 0.98,
                    'metadata' => [
                        'generated_at' => now()->toISOString(),
                        'word_count' => count($wordsTimingData),
                        'reciter_style' => $reciterProfile->tajweed_style,
                        'quran_com_recitation_id' => $reciterProfile->quran_com_recitation_id,
                        'verse_key' => $verseKey,
                    ],
                ]
            );
        } catch (Exception $e) {
            Log::warning("Failed to fetch Quran.com segments for verse {$verse->global_number}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate enhanced timing using package word data
     */
    private function generateEnhancedTimingFromPackage(
        Verse $verse,
        ReciterProfile $reciterProfile,
        array $packageWords,
        int $audioDuration
    ): VerseWordTiming {
        $enhancedWordsData = [];
        $totalWeight = 0;

        // First pass: calculate weights for each word using package data
        foreach ($packageWords as $index => $wordData) {
            $weight = $this->calculateEnhancedWordWeight($wordData, $index, $packageWords);
            $totalWeight += $weight;

            $enhancedWordsData[] = [
                'package_data' => $wordData,
                'weight' => $weight,
                'enhanced_features' => $this->extractEnhancedFeatures($wordData)
            ];
        }

        // Second pass: calculate timing for each word
        $wordsTimingData = [];
        $currentTime = 0;

        foreach ($enhancedWordsData as $index => $wordInfo) {
            $baseDuration = ($audioDuration / $totalWeight) * $wordInfo['weight'];

            // Apply reciter-specific adjustments
            $adjustedDuration = $this->applyReciterAdjustments(
                $baseDuration,
                $wordInfo['enhanced_features'],
                $reciterProfile
            );

            $startTime = round($currentTime);
            $endTime = round($currentTime + $adjustedDuration);

            $wordsTimingData[] = [
                'word_index' => $index,
                'package_word_id' => $wordInfo['package_data']['id'],
                'position' => $wordInfo['package_data']['position'],
                'arabic_text' => $wordInfo['package_data']['text'],
                'transliteration' => $wordInfo['package_data']['transliteration']['text'] ?? '',
                'translation' => $wordInfo['package_data']['translation']['text'] ?? '',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $endTime - $startTime,
                'confidence' => 0.85, // Higher confidence with package data
                'char_type' => $wordInfo['package_data']['char_type_name'] ?? 'word',
                'page_number' => $wordInfo['package_data']['page_number'] ?? null,
                'line_number' => $wordInfo['package_data']['line_number'] ?? null,
                'audio_url' => $wordInfo['package_data']['audio_url'] ?? null,
                'enhanced_features' => $wordInfo['enhanced_features'],
                'tajweed_info' => $this->detectEnhancedTajweedInfo($wordInfo['package_data'])
            ];

            $currentTime += $adjustedDuration;
        }

        // Create and save the enhanced timing data (updateOrCreate: (verse_id,
        // reciter_profile_id) is unique)
        $verseWordTiming = VerseWordTiming::updateOrCreate(
            [
                'verse_id' => $verse->id,
                'reciter_profile_id' => $reciterProfile->id,
            ],
            [
                'total_duration' => $audioDuration,
                'words_data' => $wordsTimingData,
                'source' => 'enhanced_package',
                'accuracy' => 0.85,
                'metadata' => [
                    'generated_at' => now()->toISOString(),
                    'word_count' => count($packageWords),
                    'reciter_style' => $reciterProfile->tajweed_style,
                    'package_version' => '1.0.6',
                    'enhancement_features' => [
                        'char_type_analysis',
                        'position_based_timing',
                        'advanced_tajweed_detection',
                        'multilingual_support'
                    ]
                ]
            ]
        );

        return $verseWordTiming;
    }

    /**
     * Calculate enhanced word weight using package data
     */
    private function calculateEnhancedWordWeight(array $wordData, int $index, array $allWords): float
    {
        $weight = 1.0;
        $text = $wordData['text'] ?? '';

        // Base weight from our original algorithm
        $baseWeight = $this->calculateWordWeight($text, $index, array_column($allWords, 'text'));

        // Enhanced features from package data
        $charType = $wordData['char_type_name'] ?? 'word';
        $position = $wordData['position'] ?? $index + 1;

        // Character type adjustments
        switch ($charType) {
            case 'word':
                $weight = $baseWeight;
                break;
            case 'end':
            case 'pause':
                $weight = $baseWeight * 1.5; // Longer pause for end markers
                break;
            case 'sajda':
                $weight = $baseWeight * 2.0; // Much longer for sajda
                break;
            default:
                $weight = $baseWeight;
        }

        // Position-based refinement using package position data
        if (isset($wordData['line_number']) && isset($wordData['page_number'])) {
            $lineNumber = $wordData['line_number'];

            // First word of line tends to be slower
            if ($index === 0 || ($index > 0 && ($allWords[$index - 1]['line_number'] ?? 0) < $lineNumber)) {
                $weight *= 1.2;
            }

            // Last word of line may have pause
            if ($index < count($allWords) - 1 && ($allWords[$index + 1]['line_number'] ?? $lineNumber) > $lineNumber) {
                $weight *= 1.3;
            }
        }

        // Translation length impact (longer translations = more complex words)
        $translationLength = mb_strlen($wordData['translation']['text'] ?? '');
        if ($translationLength > 10) {
            $weight *= 1.1;
        } elseif ($translationLength > 20) {
            $weight *= 1.2;
        }

        // Audio URL availability (if word has individual audio, it might be emphasized)
        if (!empty($wordData['audio_url'])) {
            $weight *= 1.05;
        }

        return max(0.3, min(3.0, $weight));
    }

    /**
     * Extract enhanced features from package word data
     */
    private function extractEnhancedFeatures(array $wordData): array
    {
        return [
            'char_type' => $wordData['char_type_name'] ?? 'word',
            'has_audio' => !empty($wordData['audio_url']),
            'position_in_verse' => $wordData['position'] ?? 0,
            'page_info' => [
                'page' => $wordData['page_number'] ?? null,
                'line' => $wordData['line_number'] ?? null
            ],
            'transliteration_complexity' => mb_strlen($wordData['transliteration']['text'] ?? ''),
            'translation_complexity' => mb_strlen($wordData['translation']['text'] ?? ''),
            'code_v1' => $wordData['code_v1'] ?? null
        ];
    }

    /**
     * Apply reciter-specific adjustments with enhanced features
     */
    private function applyReciterAdjustments(
        float $baseDuration,
        array $enhancedFeatures,
        ReciterProfile $reciterProfile
    ): float {
        $adjustedDuration = $baseDuration;

        // Character type adjustments
        switch ($enhancedFeatures['char_type']) {
            case 'end':
            case 'pause':
                $adjustedDuration *= $reciterProfile->pause_multiplier * 1.2;
                break;
            case 'sajda':
                $adjustedDuration *= $reciterProfile->pause_multiplier * 2.0;
                break;
        }

        // Complexity adjustments
        if ($enhancedFeatures['transliteration_complexity'] > 15) {
            $adjustedDuration *= 1.1;
        }

        if ($enhancedFeatures['translation_complexity'] > 20) {
            $adjustedDuration *= 1.05;
        }

        // Reciter style adjustments
        switch ($reciterProfile->tajweed_style) {
            case 'slow':
                $adjustedDuration *= 1.3;
                break;
            case 'fast':
                $adjustedDuration *= 0.8;
                break;
            case 'moderate':
            default:
                // No additional adjustment
                break;
        }

        return $adjustedDuration;
    }

    /**
     * Enhanced tajweed detection using package data
     */
    private function detectEnhancedTajweedInfo(array $wordData): array
    {
        $text = $wordData['text'] ?? '';
        $charType = $wordData['char_type_name'] ?? 'word';

        // Base tajweed from our original method
        $baseTajweed = $this->detectTajweedInfo($text);

        // Enhanced detection using package metadata
        $enhancedTajweed = $baseTajweed;

        // Character type specific tajweed
        switch ($charType) {
            case 'sajda':
                $enhancedTajweed = [
                    'type' => 'sajda',
                    'weight' => 2.5,
                    'description' => 'Prostration marker'
                ];
                break;
            case 'end':
                $enhancedTajweed = [
                    'type' => 'verse_end',
                    'weight' => 1.8,
                    'description' => 'Verse ending'
                ];
                break;
            case 'pause':
                $enhancedTajweed = [
                    'type' => 'pause_marker',
                    'weight' => 1.6,
                    'description' => 'Pause indicator'
                ];
                break;
        }

        // Add package-specific metadata
        $enhancedTajweed['package_char_type'] = $charType;
        $enhancedTajweed['has_audio'] = !empty($wordData['audio_url']);

        return $enhancedTajweed;
    }

    /**
     * Get word position mapping for synchronization
     */
    public function getWordPositionMapping(int $globalNumber): array
    {
        $cacheKey = self::PACKAGE_DATA_CACHE_PREFIX . "positions_{$globalNumber}";

        return Cache::remember($cacheKey, 86400, function () use ($globalNumber) {
            // This would typically fetch from the package data
            // For now, return a placeholder structure
            return [
                'global_number' => $globalNumber,
                'word_count' => 0,
                'positions' => [],
                'metadata' => [
                    'source' => 'package_data',
                    'version' => '1.0.6'
                ]
            ];
        });
    }

    /**
     * Clear enhanced cache
     */
    public function clearEnhancedCache(int $globalNumber = null, string $reciterCode = null): void
    {
        if ($globalNumber && $reciterCode) {
            $cacheKey = self::ENHANCED_CACHE_PREFIX . "{$globalNumber}_{$reciterCode}_enhanced";
            Cache::forget($cacheKey);
        }

        // Clear package data cache
        if ($globalNumber) {
            $packageCacheKey = self::PACKAGE_DATA_CACHE_PREFIX . "positions_{$globalNumber}";
            Cache::forget($packageCacheKey);
        }

        // Call parent clear cache
        parent::clearCache($globalNumber, $reciterCode);
    }

    /**
     * Get enhanced statistics
     */
    public function getEnhancedStats(): array
    {
        $baseStats = parent::getStats();

        return array_merge($baseStats, [
            'enhanced_timings' => VerseWordTiming::where('source', 'enhanced_package')->count(),
            'package_version' => '1.0.6',
            'enhancement_features' => [
                'char_type_analysis',
                'position_based_timing',
                'advanced_tajweed_detection',
                'multilingual_support'
            ]
        ]);
    }
}