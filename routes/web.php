<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\Agents\RevokeAgentController;
use App\Http\Controllers\Binary\DownloadBinaryController;
use App\Http\Controllers\InstallScriptController;
use App\Http\Controllers\ShowDashboardController;
use Illuminate\Support\Facades\Route;

// Publicly served files — no auth required
Route::get('/install.sh', InstallScriptController::class)->name('install-script');
Route::get('/perry', DownloadBinaryController::class)->name('perry.download');

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', ShowDashboardController::class)->name('dashboard');

    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::get('/create', [AgentController::class, 'create'])->name('create');
        Route::post('/', [AgentController::class, 'store'])->name('store');
        Route::get('/{agent}', [AgentController::class, 'show'])->name('show');
        Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
        Route::post('/{agent}/revoke', RevokeAgentController::class)->name('revoke');
        Route::delete('/{agent}', [AgentController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';
