<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientAnalysisController;
use App\Http\Controllers\ClassificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirigeix l'arrel al login
Route::get('/', function () {
    return redirect('/login');
});

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Rutes protegides per autenticació
Route::middleware('auth')->group(function () {

    // Anàlisi de pacient
    Route::get('/dashboard', [PatientAnalysisController::class, 'index'])->name('dashboard');
    Route::post('/analyze', [PatientAnalysisController::class, 'analyze'])->name('analyze');

    // Classificació de rols
    Route::get('/classificacio', [ClassificationController::class, 'index'])->name('classificacio');
});
