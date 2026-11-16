<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TafsirTranslation;
use Illuminate\Http\JsonResponse;

class TafsirTranslationController extends Controller
{
    /** Commentaire traduit d'un verset ; `translated: false` s'il n'est pas encore traduit. */
    public function show(string $source, int $surah, int $ayah): JsonResponse
    {
        // Un commentaire peut couvrir plusieurs versets : il est rangé au premier
        $row = TafsirTranslation::where('source', $source)
            ->where('language', 'fr')
            ->where('surah', $surah)
            ->where('ayah', '<=', $ayah)
            ->orderByDesc('ayah')
            ->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Commentaire introuvable'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'verse_key' => $row->verse_key,
                'translated' => $row->text_translated !== null,
                'text' => $row->text_translated,
                'text_original' => $row->text_original,
                'translator' => $row->translator,
            ],
        ]);
    }

    /** Avancement de la traduction. */
    public function stats(string $source): JsonResponse
    {
        $q = TafsirTranslation::where('source', $source)->where('language', 'fr');
        $total = (clone $q)->count();
        $done = (clone $q)->whereNotNull('text_translated')->count();

        return response()->json([
            'success' => true,
            'data' => ['total' => $total, 'translated' => $done],
        ]);
    }
}
