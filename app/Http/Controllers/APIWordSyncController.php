<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WordSyncService;
use App\Models\ReciterProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class WordSyncController extends Controller
{
    private WordSyncService $wordSyncService;

    public function __construct(WordSyncService $wordSyncService)
    {
        $this->wordSyncService = $wordSyncService;
    }

    /**
     * Récupère les données de timing pour un verset
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getVerseTimingData(Request $request): JsonResponse
    {
        $request->validate([
            'global_number' => 'required|integer|min:1|max:6236',
            'reciter_code' => 'required|string',
            'arabic_text' => 'required|string',
            'audio_duration' => 'required|integer|min:1000' // minimum 1 seconde
        ]);

        try {
            $timingData = $this->wordSyncService->getVerseTimingData(
                $request->global_number,
                $request->reciter_code,
                $request->arabic_text,
                $request->audio_duration
            );

            return response()->json([
                'success' => true,
                'data' => $timingData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'TIMING_DATA_ERROR'
            ], 400);
        }
    }

    /**
     * Trouve le mot actuel basé sur le temps de lecture
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentWord(Request $request): JsonResponse
    {
        $request->validate([
            'global_number' => 'required|integer|min:1|max:6236',
            'reciter_code' => 'required|string',
            'current_time' => 'required|integer|min:0',
            'arabic_text' => 'required|string',
            'audio_duration' => 'required|integer|min:1000'
        ]);

        try {
            // Récupérer les données de timing
            $timingData = $this->wordSyncService->getVerseTimingData(
                $request->global_number,
                $request->reciter_code,
                $request->arabic_text,
                $request->audio_duration
            );

            // Trouver le mot actuel
            $currentWordIndex = $this->wordSyncService->findCurrentWord(
                $timingData,
                $request->current_time
            );

            $currentWord = $timingData['words'][$currentWordIndex] ?? null;

            return response()->json([
                'success' => true,
                'data' => [
                    'current_word_index' => $currentWordIndex,
                    'current_word' => $currentWord,
                    'total_words' => count($timingData['words']),
                    'progress_percentage' => $timingData['total_duration'] > 0
                        ? round(($request->current_time / $timingData['total_duration']) * 100, 2)
                        : 0
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'CURRENT_WORD_ERROR'
            ], 400);
        }
    }

    /**
     * Liste les profils de récitateurs disponibles
     *
     * @return JsonResponse
     */
    public function getReciterProfiles(): JsonResponse
    {
        try {
            $profiles = ReciterProfile::active()
                ->select(['id', 'code', 'name', 'average_speed', 'pause_multiplier', 'tajweed_style'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $profiles
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reciter profiles',
                'error_code' => 'RECITERS_ERROR'
            ], 500);
        }
    }

    /**
     * Obtient un profil de récitateur spécifique
     *
     * @param string $code
     * @return JsonResponse
     */
    public function getReciterProfile(string $code): JsonResponse
    {
        try {
            $profile = ReciterProfile::where('code', $code)->active()->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reciter profile not found',
                    'error_code' => 'RECITER_NOT_FOUND'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $profile
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reciter profile',
                'error_code' => 'RECITER_PROFILE_ERROR'
            ], 500);
        }
    }

    /**
     * Nettoie le cache pour un verset et récitateur spécifique ou tout le cache
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'global_number' => 'nullable|integer|min:1|max:6236',
            'reciter_code' => 'nullable|string'
        ]);

        try {
            $this->wordSyncService->clearCache(
                $request->global_number,
                $request->reciter_code
            );

            return response()->json([
                'success' => true,
                'message' => $request->global_number && $request->reciter_code
                    ? 'Specific cache cleared successfully'
                    : 'All cache cleared successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache',
                'error_code' => 'CACHE_CLEAR_ERROR'
            ], 500);
        }
    }

    /**
     * Obtient les statistiques du service
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->wordSyncService->getStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching stats',
                'error_code' => 'STATS_ERROR'
            ], 500);
        }
    }
}
