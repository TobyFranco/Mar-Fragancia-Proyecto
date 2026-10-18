<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Services\Recomendador;
use App\Support\Texto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request, Recomendador $recomendador): View
    {
        $genero = in_array($request->query('genero'), Producto::GENEROS, true) ? $request->query('genero') : null;
        $marcasSeleccionadas = array_values(array_filter((array) $request->query('marca', []), is_string(...)));
        $familiasSeleccionadas = array_values(array_filter((array) $request->query('familia', []), fn (mixed $id) => is_scalar($id) && ctype_digit((string) $id)));
        $tamano = in_array($request->query('tamano'), ['5', '10'], true) ? $request->query('tamano') : null;
        $orden = in_array($request->query('orden'), ['probados', 'precio_asc', 'precio_desc', 'nombre'], true) ? $request->query('orden') : 'probados';

        $productos = Producto::visibles()->with(['marca', 'presentaciones.tamano'])
            ->when($genero, fn (Builder $query) => $query->where('genero', $genero))
            ->when($marcasSeleccionadas, fn (Builder $query) => $query->whereHas('marca', fn (Builder $marcas) => $marcas->whereIn('slug', $marcasSeleccionadas)))
            ->when($familiasSeleccionadas, fn (Builder $query) => $query->whereIn('categoria_id', $familiasSeleccionadas))
            ->when($tamano, fn (Builder $query) => $query->whereHas('presentaciones', fn (Builder $presentaciones) => $presentaciones->where('activo', true)->where('stock', '>', 0)->whereHas('tamano', fn (Builder $tamanos) => $tamanos->where('nombre', $tamano.' ml'))))
            ->get();
        $precio = fn (Producto $producto) => (float) $producto->presentaciones
            ->filter(fn (ProductoPresentacion $presentacion) => $presentacion->activo && in_array($presentacion->tamano->nombre, ['5 ml', '10 ml'], true))->min('precio');
        $nombre = fn (Producto $producto) => Texto::normalizar($producto->nombre);
        $ranking = $orden === 'probados' ? array_flip($recomendador->masProbados(Producto::count())->modelKeys()) : [];
        $productos = match ($orden) {
            'precio_asc' => $productos->sortBy(fn (Producto $producto) => [$precio($producto), $nombre($producto)]),
            'precio_desc' => $productos->sortBy(fn (Producto $producto) => [-$precio($producto), $nombre($producto)]),
            'nombre' => $productos->sortBy($nombre),
            default => $productos->sortBy(fn (Producto $producto) => [$ranking[$producto->id] ?? PHP_INT_MAX, $nombre($producto)]),
        };
        $productos = $productos->values();
        $marcas = Marca::withCount(['productos' => fn (Builder $query) => $query->visibles()])->orderBy('nombre')->get();
        $familias = Categoria::orderBy('nombre')->get();

        return view('tienda.catalogo', compact('productos', 'genero', 'marcas', 'familias', 'marcasSeleccionadas', 'familiasSeleccionadas', 'tamano', 'orden'));
    }
}
