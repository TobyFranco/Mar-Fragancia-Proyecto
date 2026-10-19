<?php

namespace App\Exceptions;

use App\Models\ProductoPresentacion;
use RuntimeException;

class StockInsuficiente extends RuntimeException
{
    public function __construct(public readonly ProductoPresentacion $presentacion, public readonly int $disponible)
    {
        parent::__construct($presentacion->producto->nombre.' · Decant '.$presentacion->tamano->nombre.' no tiene stock suficiente. Disponible: '.$disponible.'.');
    }
}
