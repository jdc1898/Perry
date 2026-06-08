<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\BinaryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallScriptController;
use Illuminate\Support\Facades\Route;

// Publicly served files — no auth required
Route::get('/install.sh', InstallScriptController::class)->name('install-script');
Route::get('/perry', [BinaryController::class, 'download'])->name('perry.download');

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/',                [AgentController::class, 'index'])->name('index');
        Route::get('/create',          [AgentController::class, 'create'])->name('create');
        Route::post('/',               [AgentController::class, 'store'])->name('store');
        Route::get('/{agent}',         [AgentController::class, 'show'])->name('show');
        Route::put('/{agent}',         [AgentController::class, 'update'])->name('update');
        Route::post('/{agent}/revoke', [AgentController::class, 'revoke'])->name('revoke');
        Route::delete('/{agent}',      [AgentController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';
