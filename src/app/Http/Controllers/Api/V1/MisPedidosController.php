<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PedidoResource;
use App\Models\Pedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MisPedidosController extends Controller
{
    private function propios(Request $request): Builder
    {
        return Pedido::whereHas('cliente', fn ($q) => $q->where('usuario_id', $request->user()->id));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return PedidoResource::collection($this->propios($request)->orderByDesc('fecha')->orderByDesc('id')->paginate(20));
    }

    public function show(Request $request, string $pedido): PedidoResource
    {
        return new PedidoResource($this->propios($request)->with(['cliente.usuario', 'detalles.presentacion.producto', 'detalles.presentacion.tamano', 'estados'])->findOrFail($pedido));
    }
}
