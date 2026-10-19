<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\Recomendador;
use Illuminate\View\View;

class PerfumeController extends Controller
{
    public function show(Producto $producto, Recomendador $recomendador): View
    {
        abort_unless($producto->activo, 404);
        $producto->load(['marca', 'categoria', 'notas', 'presentaciones.tamano']);
        $opciones = collect(['5 ml', '10 ml'])->map(function (string $tamano) use ($producto): array {
            $p = $producto->presentacionPorTamano($tamano);

            return ['id' => $p?->id, 'tamano' => $tamano, 'precio' => (int) ($p?->precio ?? 0), 'stock' => $p?->disponible() ? $p->stock : 0];
        });
        $elegida = $opciones->first(fn (array $opcion) => $opcion['stock'] > 0);
        $parecidos = $recomendador->parecidos($producto, 4);

        return view('tienda.perfume', compact('producto', 'opciones', 'elegida', 'parecidos'));
    }
}
