<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockInsuficiente;
use App\Exceptions\TransicionInvalida;
use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\CicloPedido;
use App\Support\Texto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['estado' => ['nullable', Rule::in(Pedido::ESTADOS)], 'q' => ['nullable', 'string', 'max:150']]);
        $consulta = Pedido::with('cliente.usuario');
        if ($estado = $datos['estado'] ?? null) {
            $consulta->where('estado', $estado);
        }
        if ($q = Texto::normalizar($datos['q'] ?? '')) {
            $ids = Pedido::with('cliente.usuario')->get()->filter(fn ($p) => str_contains(Texto::normalizar($p->numero.' '.($p->nombre_contacto ?? $p->cliente?->usuario?->nombre).' '.($p->telefono ?? $p->cliente?->telefono)), $q))->modelKeys();
            $consulta->whereIn('id', $ids);
        }
        $contadores = array_replace(array_fill_keys(Pedido::ESTADOS, 0), Pedido::selectRaw('estado, COUNT(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado')->map(fn ($n) => (int) $n)->all());

        return view('admin.pedidos.index', ['pedidos' => $consulta->orderByDesc('fecha')->orderByDesc('id')->paginate(15)->withQueryString(), 'contadores' => $contadores]);
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['detalles.presentacion.producto.marca', 'detalles.presentacion.tamano', 'estados.usuario', 'cliente.usuario']);
        $telefono = preg_replace('/\s+/u', '', $pedido->telefono ?? $pedido->cliente?->telefono ?? '');
        $whatsapp = preg_match('/^09\d{8}$/', $telefono) ? 'https://wa.me/595'.substr($telefono, 1) : null;

        return view('admin.pedidos.show', compact('pedido', 'whatsapp'));
    }

    public function transicion(Request $request, Pedido $pedido, CicloPedido $ciclo): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['bail', 'required', 'string', Rule::in(Pedido::ESTADOS)]], ['estado.*' => 'Elegí un estado válido.']);
        try {
            $ciclo->pasarA($pedido, $datos['estado'], $request->user());
        } catch (StockInsuficiente|TransicionInvalida $e) {
            return to_route('admin.pedidos.show', $pedido->id)->withErrors(['estado' => $e->getMessage()]);
        }

        return to_route('admin.pedidos.show', $pedido->id)->with('success', 'Estado del pedido actualizado.');
    }
}
