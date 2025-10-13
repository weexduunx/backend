<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tafsir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TafsirController extends Controller
{
    public function index(): JsonResponse
    {
        $tafsirs = Tafsir::with('surah:id,number,name_arabic,name_french')
            ->where('is_available', true)
            ->orderBy('surah_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tafsirs
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tafsir = Tafsir::with('surah')->find($id);

        if (!$tafsir) {
            return response()->json([
                'success' => false,
                'message' => 'Tafsir non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tafsir
        ]);
    }

    public function getBySurah(int $surahId): JsonResponse
    {
        $tafsirs = Tafsir::where('surah_id', $surahId)
            ->where('is_available', true)
            ->with('surah:id,number,name_arabic,name_french')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tafsirs
        ]);
    }

    public function getByHafiz(string $hafizName): JsonResponse
    {
        $tafsirs = Tafsir::where('hafiz_name', 'LIKE', "%{$hafizName}%")
            ->where('is_available', true)
            ->with('surah:id,number,name_arabic,name_french')
            ->orderBy('surah_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tafsirs
        ]);
    }
}
