<?php

namespace App\View\Composers;

use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class EncabezadoComposer
{
    public function compose(View $view): void
    {
        $marcas = Marca::delMenu()->with([
            'productos' => fn (HasMany $query) => $query->visibles()->orderBy('nombre'),
        ])->get();
        $marca = request()->route('marca');
        $producto = request()->route('producto');
        $seleccion = request()->query('marca');
        if (is_array($seleccion)) {
            $seleccion = count($seleccion) === 1 ? reset($seleccion) : null;
        }
        $activa = $marca instanceof Marca ? $marca->id : ($producto instanceof Producto ? $producto->marca_id : (is_string($seleccion) ? $marcas->firstWhere('slug', $seleccion)?->id : null));
        $view->with(['marcasMenu' => $marcas, 'marcaActiva' => $activa]);
        $view->with('unidadesCarrito', array_sum(session('carrito', [])));
    }
}
