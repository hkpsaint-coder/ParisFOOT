<?php

use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\OddController;
use App\Http\Controllers\Api\RefreshController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/matches', [MatchController::class, 'index']);
    Route::get('/matches/{match}', [MatchController::class, 'show']);
    Route::get('/matches/{match}/odds', [OddController::class, 'forMatch']);
    Route::get('/matches/{match}/analysis', [OddController::class, 'analysis']);
    Route::get('/value-bets', [OddController::class, 'valueBets']);
    Route::get('/arbitrages', [OddController::class, 'arbitrages']);
    Route::get('/history/{oddId}', [OddController::class, 'history']);

    Route::middleware('admin.password')->group(function () {
        Route::post('/matches', [MatchController::class, 'store']);
        Route::put('/matches/{match}', [MatchController::class, 'update']);
        Route::delete('/matches/{match}', [MatchController::class, 'destroy']);
        Route::post('/matches/{match}/odds', [OddController::class, 'store']);
        Route::put('/odds/{odd}', [OddController::class, 'update']);
        Route::delete('/odds/{odd}', [OddController::class, 'destroy']);
        Route::post('/refresh', RefreshController::class);
    });
});