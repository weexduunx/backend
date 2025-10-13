<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Verse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $verses = Verse::with('surah:id,number,name_arabic,name_french')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $verses
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $verse = Verse::with('surah')->find($id);

        if (!$verse) {
            return response()->json([
                'success' => false,
                'message' => 'Verse not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $verse
        ]);
    }

    public function getBySurah(int $surahId): JsonResponse
    {
        $verses = Verse::where('surah_id', $surahId)
            ->with('surah:id,number,name_arabic,name_french')
            ->orderBy('verse_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $verses
        ]);
    }

    public function getByGlobalNumber(int $globalNumber): JsonResponse
    {
        $verse = Verse::with('surah')
            ->where('global_number', $globalNumber)
            ->first();

        if (!$verse) {
            return response()->json([
                'success' => false,
                'message' => 'Verse not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $verse
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $perPage = $request->get('per_page', 20);

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $verses = Verse::with('surah:id,number,name_arabic,name_french')
            ->where('text_arabic', 'LIKE', "%{$query}%")
            ->orWhere('text_french', 'LIKE', "%{$query}%")
            ->orWhere('text_transliteration', 'LIKE', "%{$query}%")
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $verses
        ]);
    }
}
