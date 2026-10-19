<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\TamanoController;
use App\Http\Controllers\Tienda\BusquedaController;
use App\Http\Controllers\Tienda\CarritoController;
use App\Http\Controllers\Tienda\CatalogoController;
use App\Http\Controllers\Tienda\CheckoutController;
use App\Http\Controllers\Tienda\InicioController;
use App\Http\Controllers\Tienda\PedidoController;
use App\Http\Controllers\Tienda\PerfumeController;
use App\Http\Controllers\Tienda\TermometroController;
use Illuminate\Support\Facades\Route;

Route::get('/', InicioController::class)->name('tienda.inicio');
Route::get('/marcas', [App\Http\Controllers\Tienda\MarcaController::class, 'index'])->name('tienda.marcas.index');
Route::get('/marcas/{marca}', [App\Http\Controllers\Tienda\MarcaController::class, 'show'])->name('tienda.marcas.show');
Route::get('/perfumes', [CatalogoController::class, 'index'])->name('tienda.catalogo');
Route::get('/perfumes/{producto}', [PerfumeController::class, 'show'])->name('tienda.perfume');
Route::get('/termometro-olfativo', TermometroController::class)->name('tienda.termometro');
Route::view('/como-funciona', 'tienda.como-funciona')->name('tienda.como-funciona');
Route::get('/buscar', [BusquedaController::class, 'index'])->name('tienda.buscar');

Route::get('/carrito', [CarritoController::class, 'show'])->name('tienda.carrito');
Route::post('/carrito', [CarritoController::class, 'store'])->name('tienda.carrito.agregar');
Route::patch('/carrito/{presentacion}', [CarritoController::class, 'update'])->whereNumber('presentacion')->name('tienda.carrito.actualizar');
Route::delete('/carrito/{presentacion}', [CarritoController::class, 'destroy'])->whereNumber('presentacion')->name('tienda.carrito.quitar');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('tienda.checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->block()->name('tienda.checkout.enviar');
Route::get('/pedido/{numero}', [PedidoController::class, 'show'])->name('tienda.pedido');

// Autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Panel administrador
Route::middleware(['auth', 'rol:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/pedidos', [App\Http\Controllers\Admin\PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/{pedido}', [App\Http\Controllers\Admin\PedidoController::class, 'show'])->name('pedidos.show');
    Route::post('/pedidos/{pedido}/estado', [App\Http\Controllers\Admin\PedidoController::class, 'transicion'])->name('pedidos.transicion');

    Route::resource('marcas', MarcaController::class)->except(['show'])->scoped(['marca' => 'id']);
    Route::resource('categorias', CategoriaController::class)->except(['show']);
    Route::resource('tamanos', TamanoController::class)->except(['show']);
    Route::resource('productos', ProductoController::class)->except(['show'])->scoped(['producto' => 'id']);
});

// Área cliente
Route::middleware(['auth', 'rol:cliente'])->prefix('cliente')->group(function () {
    Route::get('/home', function () {
        return view('cliente.home');
    })->name('cliente.home');
});
