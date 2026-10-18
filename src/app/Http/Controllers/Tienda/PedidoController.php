<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\MensajeWhatsapp;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function show(Request $request, string $numero, MensajeWhatsapp $mensaje): View
    {
        $pedido = Pedido::where('numero', $numero)->firstOrFail();
        $propio = in_array($numero, $request->session()->get('tienda_pedidos', []), true)
            || ($pedido->cliente_id !== null && $request->user()?->cliente?->id === $pedido->cliente_id);
        abort_unless($propio, 404);
        $pedido->load(['detalles.presentacion.producto.marca', 'detalles.presentacion.tamano']);
        $whatsapp = $mensaje->url($pedido);

        return view('tienda.pedido-enviado', compact('pedido', 'whatsapp'));
    }
}
