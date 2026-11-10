<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\StockFueraDeRango;
use App\Exceptions\StockInsuficiente;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoInventarioRequest;
use App\Http\Resources\Api\MovimientoResource;
use App\Http\Resources\Api\PresentacionResource;
use App\Models\ProductoPresentacion;
use App\Services\Inventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class InventarioAdminController extends Controller
{
    public function critico(Request $request, Inventario $inventario): AnonymousResourceCollection
    {
        $criticas = $inventario->criticas();
        $pagina = max(1, $request->integer('page', 1));

        return PresentacionResource::collection(new LengthAwarePaginator($criticas->forPage($pagina, 20)->values(), $criticas->count(), 20, $pagina, ['path' => $request->url()]));
    }

    public function store(MovimientoInventarioRequest $request, Inventario $inventario): JsonResponse
    {
        $datos = $request->validated();
        try {
            $movimiento = $inventario->registrar(ProductoPresentacion::findOrFail($datos['presentacion_id']), $datos['tipo'], (int) $datos['cantidad'], $datos['motivo'], $request->user());
        } catch (StockInsuficiente|StockFueraDeRango $e) {
            throw ValidationException::withMessages(['cantidad' => $e->getMessage()]);
        }

        return (new MovimientoResource($movimiento))->response()->setStatusCode(201);
    }
}
