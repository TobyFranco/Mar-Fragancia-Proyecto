<?php

namespace App\Http\Controllers;

use App\Http\Requests\DatosClienteRequest;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function home(Request $request): View
    {
        return view('cliente.home', ['usuario' => $request->user()]);
    }

    public function datos(Request $request): View
    {
        return view('cliente.datos', ['usuario' => $request->user()]);
    }

    public function actualizar(DatosClienteRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $usuario = $request->user();
        $usuario->update(['nombre' => $datos['nombre']]);
        $usuario->cliente()->firstOrCreate()->update(['telefono' => $datos['telefono'] ?? null, 'direccion' => $datos['direccion'] ?? null]);

        return redirect()->route('cliente.datos')->with('estado', 'Tus datos se guardaron.');
    }

    public function pedidos(Request $request): View
    {
        $pedidos = Pedido::whereHas('cliente', fn ($q) => $q->where('usuario_id', $request->user()->id))
            ->orderByDesc('fecha')->orderByDesc('id')->paginate(15);

        return view('cliente.pedidos', compact('pedidos'));
    }
}
