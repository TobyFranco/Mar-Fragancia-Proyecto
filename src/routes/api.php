<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\MisPedidosController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('/marcas', [CatalogoController::class, 'marcas']);
    Route::get('/categorias', [CatalogoController::class, 'categorias']);
    Route::get('/productos', [CatalogoController::class, 'index']);
    Route::get('/productos/{slug}', [CatalogoController::class, 'show']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/mis-pedidos', [MisPedidosController::class, 'index']);
        Route::get('/mis-pedidos/{pedido}', [MisPedidosController::class, 'show']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
