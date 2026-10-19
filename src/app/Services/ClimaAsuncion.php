<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClimaAsuncion
{
    public function actual(): array
    {
        return Cache::remember('clima_asuncion', 1800, function (): array {
            try {
                $respuesta = Http::connectTimeout(3)->timeout(3)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => -25.28, 'longitude' => -57.63, 'current' => 'temperature_2m,relative_humidity_2m',
                ]);
                $temperatura = $respuesta->json('current.temperature_2m');
                $humedad = $respuesta->json('current.relative_humidity_2m');
                if ($respuesta->successful() && is_numeric($temperatura) && is_numeric($humedad) && $humedad >= 0 && $humedad <= 100) {
                    return ['temperatura' => (int) round((float) $temperatura), 'humedo' => $humedad >= 60, 'fuente' => 'open-meteo'];
                }
            } catch (ConnectionException) {
            }

            return ['temperatura' => TermometroOlfativo::PROMEDIOS_MES[(int) now('America/Asuncion')->format('n')], 'humedo' => false, 'fuente' => 'promedio'];
        });
    }
}
