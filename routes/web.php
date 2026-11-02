<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\AuditorController;
use Illuminate\Support\Facades\Route;

// Beranda & Riwayat
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/history', [HomeController::class, 'history'])->name('history');

// Fitur 1: Generator
Route::prefix('generator')->name('generator.')->group(function () {
    Route::get('/', [GeneratorController::class, 'index'])->name('index');
    Route::post('/', [GeneratorController::class, 'generate'])->name('generate');
    Route::get('/{id}', [GeneratorController::class, 'show'])->name('show');
    Route::get('/{id}/download', [GeneratorController::class, 'download'])->name('download');
});

// Fitur 2: Logic Auditor
Route::prefix('auditor')->name('auditor.')->group(function () {
    Route::get('/', [AuditorController::class, 'index'])->name('index');
    Route::post('/', [AuditorController::class, 'audit'])->name('audit');
    Route::get('/{id}', [AuditorController::class, 'show'])->name('show');
    Route::get('/{id}/download', [AuditorController::class, 'download'])->name('download');
});
