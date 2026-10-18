<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Texto
{
    public static function resaltar(string $texto, string $consulta): HtmlString
    {
        $consulta = self::normalizar($consulta);
        preg_match_all('/\X/u', $texto, $partes);
        $normalizado = '';
        $mapa = [];
        foreach ($partes[0] as $indice => $parte) {
            $segmento = preg_match('/^\s+$/u', $parte) ? ' ' : self::normalizar($parte);
            foreach (mb_str_split($segmento) as $letra) {
                if ($letra === ' ' && str_ends_with($normalizado, ' ')) {
                    continue;
                }
                $normalizado .= $letra;
                $mapa[] = $indice;
            }
        }
        $marcas = array_fill(0, count($partes[0]), false);
        $desde = 0;
        while ($consulta !== '' && ($posicion = mb_strpos($normalizado, $consulta, $desde)) !== false) {
            for ($i = $mapa[$posicion]; $i <= $mapa[$posicion + mb_strlen($consulta) - 1]; $i++) {
                $marcas[$i] = true;
            }
            $desde = $posicion + mb_strlen($consulta);
        }
        $html = '';
        $abierto = false;
        foreach ($partes[0] as $i => $parte) {
            if ($marcas[$i] !== $abierto) {
                $html .= $marcas[$i] ? '<mark>' : '</mark>';
                $abierto = $marcas[$i];
            }
            $html .= e($parte);
        }

        return new HtmlString($html.($abierto ? '</mark>' : ''));
    }

    public static function normalizar(string $texto): string
    {
        $texto = preg_replace_callback('/[\p{Latin}\p{M}]+/u', fn (array $parte) => Str::ascii($parte[0]), Str::lower($texto));

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }
}
