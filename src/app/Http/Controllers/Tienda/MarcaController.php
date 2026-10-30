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
        $marcas = Marca::withCount(['productos' => fn (Builder $query) => $query->visibles()])
            ->orderBy('nombre')
            ->get();

        $grupos = $marcas->groupBy(fn (Marca $marca) => strtoupper(substr(Texto::normalizar($marca->nombre), 0, 1)));

        return view('tienda.marcas.index', compact('marcas', 'grupos'));
    }

    public function show(Request $request, Marca $marca): View
    {
        $genero = in_array($request->query('genero'), Producto::GENEROS, true) ? $request->query('genero') : null;

        $productos = $marca->productos()
            ->visibles()
            ->with(['presentaciones.tamano']) // Carga ansiosa total para evitar timeouts en la vista
            ->when($genero, fn (Builder $query) => $query->where('genero', $genero))
            ->orderBy('nombre')
            ->get();

        return view('tienda.marcas.show', compact('marca', 'productos', 'genero'));
    }
}
