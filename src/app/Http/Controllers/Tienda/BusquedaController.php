<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Support\Texto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusquedaController extends Controller
{
    public function index(Request $request): View
    {
        $consulta = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $normalizada = Texto::normalizar($consulta);
        $productos = Producto::visibles()->with(['marca', 'notas', 'presentaciones.tamano'])->get()
            ->filter(fn (Producto $producto) => $normalizada !== '' && (
                str_contains(Texto::normalizar($producto->nombre), $normalizada)
                || str_contains(Texto::normalizar($producto->marca->nombre), $normalizada)
                || $producto->notas->contains(fn ($nota) => str_contains(Texto::normalizar($nota->nombre), $normalizada))
            ))->sortBy(fn (Producto $producto) => Texto::normalizar($producto->nombre))->values();
        $marcas = $productos->pluck('marca')->unique('id')->values();
        $notas = $productos->flatMap->notas->unique('id')
            ->filter(fn ($nota) => str_contains(Texto::normalizar($nota->nombre), $normalizada))->values();
        $whatsapp = 'https://wa.me/'.config('tienda.whatsapp', '595900000000');

        return view($request->query('sugerencias') === '1' ? 'tienda.resultados-busqueda' : 'tienda.busqueda', compact('consulta', 'productos', 'marcas', 'notas', 'whatsapp'));
    }
}
