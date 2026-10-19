<?php

namespace App\Http\Controllers\Tienda;

use App\Actions\CrearPedidoDesdeCarrito;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Carrito;
use App\Services\MensajeWhatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, Carrito $carrito): View|RedirectResponse
    {
        if ($carrito->unidades() === 0) {
            return to_route('tienda.carrito');
        }
        $usuario = $request->user();
        $perfil = $usuario?->cliente;
        $precarga = ['nombre_contacto' => $usuario?->nombre, 'telefono' => $perfil?->telefono, 'email' => $usuario?->email, 'direccion' => $perfil?->direccion];
        $lineas = $carrito->lineas();
        $subtotal = (int) $lineas->sum('subtotal');

        return view('tienda.checkout', compact('precarga', 'lineas', 'subtotal'));
    }

    public function store(CheckoutRequest $request, Carrito $carrito, CrearPedidoDesdeCarrito $crear, MensajeWhatsapp $mensaje): RedirectResponse
    {
        if ($carrito->unidades() === 0) {
            return to_route('tienda.carrito');
        }
        $pedido = $crear->handle($carrito, $request->validated(), $request->user()?->cliente);
        $permitidos = $request->session()->get('tienda_pedidos', []);
        $permitidos[] = $pedido->numero;
        $request->session()->put('tienda_pedidos', $permitidos);
        $request->session()->put('tienda_ultimo_pedido', $pedido->numero);

        return redirect()->away($mensaje->url($pedido));
    }
}
