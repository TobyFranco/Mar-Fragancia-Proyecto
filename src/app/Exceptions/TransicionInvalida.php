<?php

namespace App\Exceptions;

use App\Models\Pedido;
use RuntimeException;

class TransicionInvalida extends RuntimeException
{
    public function __construct(public readonly Pedido $pedido, public readonly string $destino)
    {
        parent::__construct('El pedido '.$pedido->numero.' no puede pasar de '.$pedido->estado.' a '.$destino.'.');
    }
}
