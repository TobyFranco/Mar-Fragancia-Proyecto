<?php

namespace App\View\Composers;

use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use Illuminate\View\View;

class AdminMenuComposer
{
    public function compose(View $view): void
    {
        $view->with('pendientesMenu', Pedido::where('estado', 'pendiente')->count())
            ->with('criticasMenu', ProductoPresentacion::where('activo', true)->where('stock', '<=', 3)->count());
    }
}
