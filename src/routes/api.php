<?php

use App\Http\Controllers\Api\V1\CatalogoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/marcas', [CatalogoController::class, 'marcas']);
    Route::get('/categorias', [CatalogoController::class, 'categorias']);
    Route::get('/productos', [CatalogoController::class, 'index']);
    Route::get('/productos/{slug}', [CatalogoController::class, 'show']);
    Route::get('/auth/me', fn (Request $request) => $request->user())->middleware('auth:sanctum');
});
