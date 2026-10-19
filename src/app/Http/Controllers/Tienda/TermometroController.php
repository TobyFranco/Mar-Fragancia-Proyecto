<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Services\ClimaAsuncion;
use App\Services\TermometroOlfativo;
use Illuminate\View\View;

class TermometroController extends Controller
{
    public function __invoke(ClimaAsuncion $climaAsuncion, TermometroOlfativo $termometro): View
    {
        $clima = $climaAsuncion->actual();
        $temperaturaInicial = max(10, min(40, $clima['temperatura']));
        $zonas = $termometro->zonas();
        $recomendaciones = [];
        foreach (['frio' => 18, 'templado' => 26, 'calor' => 32, 'calor_intenso' => 40] as $clave => $temperatura) {
            $recomendaciones[$clave] = $termometro->recomendaciones($temperatura);
            foreach (['dia_seco' => [false, false], 'dia_humedo' => [false, true], 'noche_seco' => [true, false], 'noche_humedo' => [true, true]] as $estado => [$noche, $humedo]) {
                $texto = $zonas[$clave]['texto'];
                if ($noche) {
                    $texto .= ' De noche podés subir un poco la intensidad: hay menos calor y más cercanía.';
                }
                if ($humedo) {
                    $texto .= ' La humedad hace que el aroma "flote" y se sienta más: mejor una aplicación menos.';
                }
                $zonas[$clave]['estados'][$estado] = ['dosis' => $termometro->dosis($temperatura, $noche, $humedo), 'texto' => $texto];
            }
        }
        $porTemperatura = $ciencia = [];
        foreach (range(10, 40) as $temperatura) {
            $porTemperatura[$temperatura] = $termometro->zona($temperatura)['clave'];
            $ciencia[$temperatura] = 'El perfume es una mezcla de aromas que se evaporan a distinta velocidad. A '.$temperatura.'° '.($temperatura > 28 ? 'las notas de salida se van rápido y las pesadas se intensifican' : 'las notas se liberan despacio y las cálidas se sienten suaves').'.';
        }
        $meses = array_combine(['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], TermometroOlfativo::PROMEDIOS_MES);
        $inicial = $zonas[$porTemperatura[$temperaturaInicial]];

        return view('tienda.termometro', compact('clima', 'temperaturaInicial', 'zonas', 'recomendaciones', 'porTemperatura', 'ciencia', 'meses', 'inicial'));
    }
}
