<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Services\Recomendador;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(Recomendador $recomendador): View
    {
        return view('tienda.inicio', ['productos' => $recomendador->masProbados(4)]);
    }
}
