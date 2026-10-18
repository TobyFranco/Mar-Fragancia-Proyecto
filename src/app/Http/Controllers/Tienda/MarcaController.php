<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use App\Models\Producto;
use App\Support\Texto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarcaController extends Controller
{
    public function index(): View
    {
        $marcas = Marca::withCount(['productos' => fn (Builder $query) => $query->visibles()])->get()
            ->sortBy(fn (Marca $marca) => Texto::normalizar($marca->nombre));
        $grupos = $marcas->groupBy(fn (Marca $marca) => strtoupper(substr(Texto::normalizar($marca->nombre), 0, 1)));

        return view('tienda.marcas.index', compact('marcas', 'grupos'));
    }

    public function show(Request $request, Marca $marca): View
    {
        $genero = in_array($request->query('genero'), Producto::GENEROS, true) ? $request->query('genero') : null;
        $productos = $marca->productos()->visibles()->with(['marca', 'presentaciones.tamano'])
            ->when($genero, fn (Builder $query) => $query->where('genero', $genero))->get()
            ->sortBy(fn (Producto $producto) => Texto::normalizar($producto->nombre))->values();

        return view('tienda.marcas.show', compact('marca', 'productos', 'genero'));
    }
}
