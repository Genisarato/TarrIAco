<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientAnalysisController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\PatientAuthController;
use App\Http\Controllers\PatientPortalController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirigeix l'arrel al login
Route::get('/', function () {
    return redirect('/login');
});

// Auth Professionals
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Auth Pacients
Route::get('/pacient/login', [PatientAuthController::class, 'showLoginForm'])->name('pacient.login');
Route::post('/pacient/login', [PatientAuthController::class, 'login']);

// Portal Pacient
Route::middleware('web')->group(function () {
    Route::get('/meu-espai', [PatientPortalController::class, 'index'])->name('pacient.dashboard');
    Route::post('/pacient/logout', [PatientAuthController::class, 'logout'])->name('pacient.logout');
});

// Rutes protegides per autenticació (Professionals)
Route::middleware('auth')->group(function () {

    // Anàlisi de pacient
    Route::get('/dashboard', [PatientAnalysisController::class, 'index'])->name('dashboard');
    Route::post('/analyze', [PatientAnalysisController::class, 'analyze'])->name('analyze');

    // Classificació de rols
    Route::get('/classificacio', [ClassificationController::class, 'index'])->name('classificacio');
});
