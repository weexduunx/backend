<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EnhancedWordSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\Validator;

class EnhancedWordSyncController extends Controller
{
    private EnhancedWordSyncService $enhancedWordSyncService;

    public function __construct(EnhancedWordSyncService $enhancedWordSyncService)
    {
        $this->enhancedWordSyncService = $enhancedWordSyncService;
    }

    /**
     * Get enhanced timing data using package word data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEnhancedTimingData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_number' => 'required|integer|min:1|max:6236',
            'reciter_code' => 'required|string',
            'package_words' => 'required|array',
            'package_words.*.id' => 'required|integer',
            'package_words.*.text' => 'required|string',
            'package_words.*.position' => 'required|integer',
            'package_words.*.char_type_name' => 'nullable|string',
            'package_words.*.translation' => 'required|array',
            'package_words.*.translation.text' => 'nullable|string',
            'package_words.*.transliteration' => 'required|array',
            'package_words.*.transliteration.text' => 'nullable|string',
            'audio_duration' => 'required|integer|min:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        try {
            $timingData = $this->enhancedWordSyncService->getEnhancedVerseTimingData(
                $request->global_number,
                $request->reciter_code,
                $request->package_words,
                $request->audio_duration
            );

            return response()->json([
                'success' => true,
                'data' => $timingData,
                'metadata' => [
                    'enhancement_type' => 'package_based',
                    'confidence_level' => 'high',
                    'features_used' => [
                        'char_type_analysis',
                        'position_mapping',
                        'multilingual_data'
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ENHANCED_TIMING_ERROR'
            ], 400);
        }
    }

    /**
     * Get current word with enhanced features
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEnhancedCurrentWord(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_number' => 'required|integer|min:1|max:6236',
            'reciter_code' => 'required|string',
            'current_time' => 'required|integer|min:0',
            'package_words' => 'required|array',
            'audio_duration' => 'required|integer|min:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        try {
            // Get enhanced timing data
            $timingData = $this->enhancedWordSyncService->getEnhancedVerseTimingData(
                $request->global_number,
                $request->reciter_code,
                $request->package_words,
                $request->audio_duration
            );

            // Find current word
            $currentWordIndex = $this->enhancedWordSyncService->findCurrentWord(
                $timingData,
                $request->current_time
            );

            $currentWord = $timingData['words'][$currentWordIndex] ?? null;

            // Enhanced response with additional context
            $response = [
                'current_word_index' => $currentWordIndex,
                'current_word' => $currentWord,
                'total_words' => count($timingData['words']),
                'progress_percentage' => $timingData['total_duration'] > 0
                    ? round(($request->current_time / $timingData['total_duration']) * 100, 2)
                    : 0
            ];

            // Add context words (previous and next)
            if ($currentWordIndex > 0) {
                $response['previous_word'] = $timingData['words'][$currentWordIndex - 1];
            }

            if ($currentWordIndex < count($timingData['words']) - 1) {
                $response['next_word'] = $timingData['words'][$currentWordIndex + 1];
            }

            // Add enhanced features
            if ($currentWord) {
                $response['enhanced_features'] = [
                    'char_type' => $currentWord['char_type'] ?? 'word',
                    'has_audio' => !empty($currentWord['audio_url']),
                    'translation_available' => !empty($currentWord['translation']),
                    'transliteration_available' => !empty($currentWord['transliteration']),
                    'tajweed_info' => $currentWord['tajweed_info'] ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $response,
                'metadata' => [
                    'enhancement_type' => 'package_based',
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ENHANCED_CURRENT_WORD_ERROR'
            ], 400);
        }
    }

    /**
     * Get word position mapping for a verse
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWordPositionMapping(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_number' => 'required|integer|min:1|max:6236'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        try {
            $mapping = $this->enhancedWordSyncService->getWordPositionMapping(
                $request->global_number
            );

            return response()->json([
                'success' => true,
                'data' => $mapping
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'POSITION_MAPPING_ERROR'
            ], 400);
        }
    }

    /**
     * Bulk process multiple verses for enhanced timing
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkProcessVerses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verses' => 'required|array|max:10', // Limit to 10 verses per request
            'verses.*.global_number' => 'required|integer|min:1|max:6236',
            'verses.*.package_words' => 'required|array',
            'reciter_code' => 'required|string',
            'audio_durations' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        try {
            $results = [];
            $errors = [];

            foreach ($request->verses as $index => $verseData) {
                try {
                    $audioDuration = $request->audio_durations[$index] ?? 5000;

                    $timingData = $this->enhancedWordSyncService->getEnhancedVerseTimingData(
                        $verseData['global_number'],
                        $request->reciter_code,
                        $verseData['package_words'],
                        $audioDuration
                    );

                    $results[] = [
                        'global_number' => $verseData['global_number'],
                        'success' => true,
                        'data' => $timingData
                    ];

                } catch (Exception $e) {
                    $errors[] = [
                        'global_number' => $verseData['global_number'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'processed_count' => count($results),
                    'error_count' => count($errors),
                    'results' => $results,
                    'errors' => $errors
                ],
                'metadata' => [
                    'processing_type' => 'bulk_enhanced',
                    'total_requested' => count($request->verses)
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'BULK_PROCESSING_ERROR'
            ], 500);
        }
    }

    /**
     * Clear enhanced cache
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearEnhancedCache(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'global_number' => 'nullable|integer|min:1|max:6236',
            'reciter_code' => 'nullable|string',
            'clear_all' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        }

        try {
            if ($request->clear_all) {
                $this->enhancedWordSyncService->clearEnhancedCache();
                $message = 'All enhanced cache cleared successfully';
            } else {
                $this->enhancedWordSyncService->clearEnhancedCache(
                    $request->global_number,
                    $request->reciter_code
                );
                $message = $request->global_number && $request->reciter_code
                    ? 'Specific enhanced cache cleared successfully'
                    : 'Enhanced cache cleared successfully';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing enhanced cache',
                'error_code' => 'ENHANCED_CACHE_CLEAR_ERROR'
            ], 500);
        }
    }

    /**
     * Get enhanced statistics
     *
     * @return JsonResponse
     */
    public function getEnhancedStats(): JsonResponse
    {
        try {
            $stats = $this->enhancedWordSyncService->getEnhancedStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching enhanced stats',
                'error_code' => 'ENHANCED_STATS_ERROR'
            ], 500);
        }
    }
}