<?php

namespace App\Support;

use Illuminate\Support\Str;

class Texto
{
    public static function normalizar(string $texto): string
    {
        $texto = preg_replace_callback('/[\p{Latin}\p{M}]+/u', fn (array $parte) => Str::ascii($parte[0]), Str::lower($texto));

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }
}
