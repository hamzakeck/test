<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReclamationController;
use App\Http\Controllers\ReclamationResponseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Require authentication & email verification for all routes
Route::middleware(['auth', 'verified'])->group(function () {
    // View all reclamations
    Route::get('/reclamations', [ReclamationController::class, 'index'])->name('reclamations.index');
    
    // Create new reclamation
    Route::get('/reclamations/create', [ReclamationController::class, 'create'])->name('reclamations.create');
    Route::post('/reclamations', [ReclamationController::class, 'store'])->name('reclamations.store');
    
    // Edit existing reclamation
    Route::get('/reclamations/{id}/edit', [ReclamationController::class, 'edit'])->name('reclamations.edit');
    Route::put('/reclamations/{id}', [ReclamationController::class, 'update'])->name('reclamations.update');
    
    // Delete reclamation
    Route::delete('/reclamations/{id}', [ReclamationController::class, 'destroy'])->name('reclamations.destroy');
    
    // Retry single send
    Route::post('/reclamations/{id}/retry', [ReclamationController::class, 'retry'])->name('reclamations.retry');
    
    // Retry all unsent
Route::post('/reclamations/retry-all', [ReclamationController::class, 'retryAll'])->name('reclamations.retry-all');
    
    // Import from Excel
    Route::post('/reclamations/import', [ReclamationController::class, 'import'])->name('reclamations.import');
   
    // Export to Excel
    Route::get('/reclamations/export', [ReclamationController::class, 'export'])->name('reclamations.export');
    
    // Fetch responses from external API
Route::post('/reclamations/{id}/fetch-responses', [ReclamationController::class, 'fetchReclamationResponses'])->name('reclamations.fetch-responses');
    
    // ReclamationResponse routes
    Route::delete('/reclamation-responses/{id}', [ReclamationResponseController::class, 'destroy'])->name('reclamation-responses.destroy');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';