<?php

namespace App\Exceptions;

use App\Models\ProductoPresentacion;
use RuntimeException;

class StockFueraDeRango extends RuntimeException
{
    public function __construct(public readonly ProductoPresentacion $presentacion, public readonly int $maximo)
    {
        parent::__construct('El stock resultante supera el máximo permitido ('.$maximo.').');
    }
}
