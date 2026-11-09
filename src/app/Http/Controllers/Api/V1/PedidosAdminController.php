<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockInsuficiente;
use App\Exceptions\TransicionInvalida;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EstadoPedidoRequest;
use App\Http\Requests\Api\ListaPedidosRequest;
use App\Http\Resources\Api\PedidoResource;
use App\Models\Pedido;
use App\Services\CicloPedido;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class PedidosAdminController extends Controller
{
    public function index(ListaPedidosRequest $request): AnonymousResourceCollection
    {
        $estado = $request->validated('estado');

        return PedidoResource::collection(Pedido::query()->when($estado, fn ($q) => $q->where('estado', $estado))->orderByDesc('fecha')->orderByDesc('id')->paginate(20)->withQueryString());
    }

    public function show(Pedido $pedido): PedidoResource
    {
        return new PedidoResource($pedido->load(['cliente.usuario', 'detalles.presentacion.producto', 'detalles.presentacion.tamano', 'estados']));
    }

    public function estado(EstadoPedidoRequest $request, Pedido $pedido, CicloPedido $ciclo): PedidoResource
    {
        try {
            $actual = $ciclo->pasarA($pedido, $request->validated('estado'), $request->user());
        } catch (StockInsuficiente|TransicionInvalida $e) {
            throw ValidationException::withMessages(['estado' => $e->getMessage()]);
        }

        return $this->show($actual);
    }
}
