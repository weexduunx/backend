<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Surah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurahController extends Controller
{
    public function index(): JsonResponse
    {
        $surahs = Surah::select([
            'id', 'number', 'name_arabic', 'name_french',
            'name_transliteration', 'verses_count', 'revelation_type'
        ])->orderBy('number')->get();

        return response()->json([
            'success' => true,
            'data' => $surahs
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $surah = Surah::with(['verses' => function($query) {
            $query->select(['id', 'surah_id', 'verse_number', 'text_arabic', 'text_french', 'global_number']);
        }])->find($id);

        if (!$surah) {
            return response()->json([
                'success' => false,
                'message' => 'Surah not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $surah
        ]);
    }

    public function getByNumber(int $number): JsonResponse
    {
        $surah = Surah::with(['verses' => function($query) {
            $query->select(['id', 'surah_id', 'verse_number', 'text_arabic', 'text_french', 'global_number']);
        }])->where('number', $number)->first();

        if (!$surah) {
            return response()->json([
                'success' => false,
                'message' => 'Surah not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $surah
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $surahs = Surah::where('name_arabic', 'LIKE', "%{$query}%")
            ->orWhere('name_french', 'LIKE', "%{$query}%")
            ->orWhere('name_transliteration', 'LIKE', "%{$query}%")
            ->select(['id', 'number', 'name_arabic', 'name_french', 'name_transliteration', 'verses_count'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $surahs
        ]);
    }
}
