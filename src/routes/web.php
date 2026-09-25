<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\TamanoController;
use App\Http\Controllers\ProductoController;

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Panel administrador
Route::middleware(['auth', 'rol:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('marcas', MarcaController::class)->except(['show']);
    Route::resource('categorias', CategoriaController::class)->except(['show']);
    Route::resource('tamanos', TamanoController::class)->except(['show']);
    Route::resource('productos', ProductoController::class)->except(['show']);
});

// Área cliente
Route::middleware(['auth', 'rol:cliente'])->prefix('cliente')->group(function () {
    Route::get('/home', function () {
        return view('cliente.home');
    })->name('cliente.home');
});