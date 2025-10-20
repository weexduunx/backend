<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SurahController;
use App\Http\Controllers\API\VerseController;
use App\Http\Controllers\API\TafsirController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\WordSyncController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/profile', [AuthController::class, 'profile']);
        });
    });

    Route::prefix('surahs')->group(function () {
        Route::get('/', [SurahController::class, 'index']);
        Route::get('/search', [SurahController::class, 'search']);
        Route::get('/{id}', [SurahController::class, 'show']);
        Route::get('/number/{number}', [SurahController::class, 'getByNumber']);
    });

    Route::prefix('verses')->group(function () {
        Route::get('/', [VerseController::class, 'index']);
        Route::get('/search', [VerseController::class, 'search']);
        Route::get('/{id}', [VerseController::class, 'show']);
        Route::get('/surah/{surahId}', [VerseController::class, 'getBySurah']);
        Route::get('/global/{globalNumber}', [VerseController::class, 'getByGlobalNumber']);
    });

    Route::prefix('tafsirs')->group(function () {
        Route::get('/', [TafsirController::class, 'index']);
        Route::get('/{id}', [TafsirController::class, 'show']);
        Route::get('/surah/{surahId}', [TafsirController::class, 'getBySurah']);
        Route::get('/hafiz/{hafizName}', [TafsirController::class, 'getByHafiz']);
    });

    Route::middleware('auth:sanctum')->prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{id}', [FavoriteController::class, 'destroy']);
        Route::get('/verses', [FavoriteController::class, 'getFavoriteVerses']);
        Route::get('/surahs', [FavoriteController::class, 'getFavoriteSurahs']);
    });

    // Word synchronization routes
    Route::prefix('word-sync')->group(function () {
        Route::post('/timing-data', [WordSyncController::class, 'getVerseTimingData']);
        Route::post('/current-word', [WordSyncController::class, 'getCurrentWord']);
        Route::get('/reciters', [WordSyncController::class, 'getReciterProfiles']);
        Route::get('/reciters/{code}', [WordSyncController::class, 'getReciterProfile']);
        Route::delete('/cache', [WordSyncController::class, 'clearCache']);
        Route::get('/stats', [WordSyncController::class, 'getStats']);
    });
});