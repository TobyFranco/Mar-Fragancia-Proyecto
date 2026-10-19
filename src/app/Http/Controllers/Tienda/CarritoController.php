<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\ProductoPresentacion;
use App\Services\Carrito;
use App\Services\Recomendador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarritoController extends Controller
{
    public function show(Carrito $carrito, Recomendador $recomendador): View
    {
        $lineas = $carrito->lineas();
        $ultimo = $carrito->ultimoAgregado();
        $recomendados = $ultimo && $lineas->isNotEmpty() ? $recomendador->parecidos($ultimo, 3) : collect();

        return view('tienda.carrito', ['lineas' => $lineas, 'ultimo' => $ultimo, 'recomendados' => $recomendados, 'subtotal' => (int) $lineas->sum('subtotal')]);
    }

    public function store(Request $request, Carrito $carrito): RedirectResponse
    {
        $datos = $request->validate([
            'presentacion_id' => ['required', 'integer', 'exists:producto_presentacion,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ], ['cantidad.*' => 'Elegí una cantidad entera mayor a cero.']);
        $carrito->agregar(ProductoPresentacion::with(['producto', 'tamano'])->findOrFail($datos['presentacion_id']), (int) $datos['cantidad']);

        return back(302, [], route('tienda.carrito'))->with('carrito_abierto', true);
    }

    public function update(Request $request, int $presentacion, Carrito $carrito): RedirectResponse
    {
        $datos = $request->validate(['cantidad' => ['required', 'integer', 'min:0']], ['cantidad.*' => 'Elegí una cantidad entera igual o mayor a cero.']);
        $carrito->actualizar($presentacion, (int) $datos['cantidad']);

        return back(302, [], route('tienda.carrito'))->with('carrito_abierto', parse_url(url()->previous(), PHP_URL_PATH) !== '/carrito');
    }

    public function destroy(int $presentacion, Carrito $carrito): RedirectResponse
    {
        $carrito->actualizar($presentacion, 0);

        return back(302, [], route('tienda.carrito'))->with('carrito_abierto', parse_url(url()->previous(), PHP_URL_PATH) !== '/carrito');
    }
}
