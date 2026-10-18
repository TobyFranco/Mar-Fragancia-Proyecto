<?php

namespace App\Support;

class Moneda
{
    public static function gs(int|float $monto): string
    {
        return '₲ '.number_format($monto, 0, ',', '.');
    }
}
