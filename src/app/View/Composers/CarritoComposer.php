<?php

namespace App\View\Composers;

use App\Services\Carrito;
use App\Services\Recomendador;
use Illuminate\View\View;

class CarritoComposer
{
    public function __construct(private Carrito $carrito, private Recomendador $recomendador) {}

    public function compose(View $view): void
    {
        $lineas = $this->carrito->lineas();
        $ultimo = $this->carrito->ultimoAgregado();
        $view->with([
            'lineas' => $lineas, 'ultimo' => $ultimo,
            'recomendados' => $ultimo && $lineas->isNotEmpty() ? $this->recomendador->parecidos($ultimo, 3) : collect(),
            'subtotal' => (int) $lineas->sum('subtotal'),
        ]);
    }
}
