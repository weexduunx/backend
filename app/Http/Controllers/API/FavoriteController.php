<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with(['verse.surah', 'surah'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:verse,surah',
            'verse_id' => 'required_if:type,verse|exists:verses,id',
            'surah_id' => 'required_if:type,surah|exists:surahs,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $existingFavorite = Favorite::where('user_id', $request->user()->id)
            ->where('type', $request->type)
            ->when($request->type === 'verse', function ($query) use ($request) {
                return $query->where('verse_id', $request->verse_id);
            })
            ->when($request->type === 'surah', function ($query) use ($request) {
                return $query->where('surah_id', $request->surah_id);
            })
            ->first();

        if ($existingFavorite) {
            return response()->json([
                'success' => false,
                'message' => 'Déjà ajouté aux favoris'
            ], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'verse_id' => $request->type === 'verse' ? $request->verse_id : null,
            'surah_id' => $request->surah_id,
            'notes' => $request->notes
        ]);

        $favorite->load(['verse.surah', 'surah']);

        return response()->json([
            'success' => true,
            'message' => 'Ajouté aux favoris',
            'data' => $favorite
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $favorite = Favorite::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Favori non trouvé'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supprimé des favoris'
        ]);
    }

    public function getFavoriteVerses(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->where('type', 'verse')
            ->with(['verse.surah'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    public function getFavoriteSurahs(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->where('type', 'surah')
            ->with(['surah'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }
}
