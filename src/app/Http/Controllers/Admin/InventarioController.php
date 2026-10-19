<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockInsuficiente;
use App\Http\Controllers\Controller;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use App\Services\Inventario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['filtro' => ['nullable', Rule::in(['entrada', 'salida', 'critico'])], 'producto_id' => ['nullable', 'integer', 'exists:productos,id']]);
        $query = MovimientoInventario::with(['presentacion.producto', 'presentacion.tamano', 'usuario']);
        $filtro = $datos['filtro'] ?? null;
        if (in_array($filtro, ['entrada', 'salida'], true)) {
            $query->where('tipo', $filtro);
        } elseif ($filtro === 'critico') {
            $query->whereHas('presentacion', fn ($q) => $q->where('activo', true)->where('stock', '<=', 3));
        }
        if ($id = $datos['producto_id'] ?? null) {
            $query->whereHas('presentacion', fn ($q) => $q->where('producto_id', $id));
        }

        return view('admin.inventario.index', ['movimientos' => $query->orderByDesc('fecha')->orderByDesc('id')->paginate(20)->withQueryString(), 'productos' => Producto::orderBy('nombre')->get()]);
    }

    public function create(): View
    {
        return view('admin.inventario.create', ['presentaciones' => ProductoPresentacion::with(['producto', 'tamano'])->whereHas('tamano', fn ($q) => $q->whereIn('nombre', ['5 ml', '10 ml']))->get()->sortBy(fn ($p) => $p->producto->nombre.' '.$p->tamano->nombre)]);
    }

    public function store(Request $request, Inventario $inventario): RedirectResponse
    {
        $datos = $request->validate([
            'presentacion_id' => ['required', 'integer', Rule::exists('producto_presentacion', 'id')->whereIn('tamano_id', Tamano::whereIn('nombre', ['5 ml', '10 ml'])->select('id'))],
            'tipo' => ['required', Rule::in(['entrada', 'salida'])],
            'cantidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'motivo' => ['required', 'string', 'max:255'],
        ], ['required' => 'Completá :attribute.', 'integer' => 'La cantidad debe ser un entero.', 'cantidad.min' => 'La cantidad debe ser mayor que cero.', 'in' => 'Elegí un tipo válido.', 'motivo.required' => 'Escribí el motivo del movimiento.']);
        try {
            $inventario->registrar(ProductoPresentacion::findOrFail($datos['presentacion_id']), $datos['tipo'], (int) $datos['cantidad'], $datos['motivo'], $request->user());
        } catch (StockInsuficiente $e) {
            return to_route('admin.inventario.create')->withInput()->withErrors(['cantidad' => $e->getMessage()]);
        }

        return to_route('admin.inventario.index')->with('success','Movimiento registrado.');
    }
}
