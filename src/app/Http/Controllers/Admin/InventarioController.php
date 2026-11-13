<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockFueraDeRango;
use App\Exceptions\StockInsuficiente;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoInventarioRequest;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Services\Inventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request, Inventario $inventario): View
    {
        $datos = $request->validate(['filtro' => ['nullable', Rule::in(['entrada', 'salida', 'critico'])], 'producto_id' => ['nullable', 'integer', 'exists:productos,id']]);
        $filtro = $datos['filtro'] ?? null;
        if ($filtro === 'critico') {
            $criticas = $inventario->criticas();
            if ($id = $datos['producto_id'] ?? null) {
                $criticas = $criticas->where('producto_id', $id);
            }

            return view('admin.inventario.index', ['criticas' => $criticas, 'productos' => Producto::orderBy('nombre')->get()]);
        }

        $query = MovimientoInventario::with(['presentacion.producto', 'presentacion.tamano', 'usuario']);
        if (in_array($filtro, ['entrada', 'salida'], true)) {
            $query->where('tipo', $filtro);
        }
        if ($id = $datos['producto_id'] ?? null) {
            $query->whereHas('presentacion', fn ($q) => $q->where('producto_id', $id));
        }

        return view('admin.inventario.index', ['movimientos' => $query->orderByDesc('fecha')->orderByDesc('id')->paginate(20)->withQueryString(), 'productos' => Producto::orderBy('nombre')->get()]);
    }

    public function create(Request $request): View
    {
        $presentaciones = ProductoPresentacion::with(['producto', 'tamano'])->whereHas('tamano', fn ($q) => $q->whereIn('nombre', ['5 ml', '10 ml']))->get()->sortBy(fn ($p) => $p->producto->nombre.' '.$p->tamano->nombre);
        $preseleccion = $request->validate([
            'presentacion_id' => ['nullable', 'integer', Rule::in($presentaciones->modelKeys())],
            'tipo' => ['nullable', Rule::in(['entrada', 'salida'])],
        ]);

        return view('admin.inventario.create', compact('presentaciones', 'preseleccion'));
    }

    public function store(MovimientoInventarioRequest $request, Inventario $inventario): RedirectResponse
    {
        $datos = $request->validated();
        try {
            $inventario->registrar(ProductoPresentacion::findOrFail($datos['presentacion_id']), $datos['tipo'], (int) $datos['cantidad'], $datos['motivo'], $request->user());
        } catch (StockInsuficiente|StockFueraDeRango $e) {
            return to_route('admin.inventario.create')->withInput()->withErrors(['cantidad' => $e->getMessage()]);
        }

        return to_route('admin.inventario.index')->with('success', 'Movimiento registrado.');
    }
}
