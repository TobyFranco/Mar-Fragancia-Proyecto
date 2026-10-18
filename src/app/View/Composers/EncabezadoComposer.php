<?php

namespace App\View\Composers;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class EncabezadoComposer
{
    public function compose(View $view): void
    {
        $view->with('marcasMenu', Marca::delMenu()->with([
            'productos' => fn (HasMany $query) => $query->visibles()->orderBy('nombre'),
        ])->get());
        $view->with('unidadesCarrito', array_sum(session('carrito', [])));
    }
}
