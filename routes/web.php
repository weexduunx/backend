<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Avis des bêta-testeurs (mot de passe : FEEDBACK_ADMIN_PASSWORD dans .env)
Route::prefix('admin/avis')->name('admin.feedback.')->controller(\App\Http\Controllers\Admin\FeedbackAdminController::class)->group(function () {
    Route::get('/connexion', 'loginForm')->name('login');
    Route::post('/connexion', 'login')->middleware('throttle:5,1');
    Route::post('/deconnexion', 'logout')->name('logout');
    Route::get('/', 'index')->name('index');
    Route::get('/export', 'export')->name('export');
});
