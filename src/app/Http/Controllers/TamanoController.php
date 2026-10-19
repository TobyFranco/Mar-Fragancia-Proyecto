<?php

namespace App\Http\Controllers;

use App\Models\Tamano;
use Illuminate\View\View;

class TamanoController extends Controller
{
    public function index(): View
    {
        return view('admin.tamanos.index', ['registros' => Tamano::whereIn('nombre', ['5 ml', '10 ml'])->withCount('presentaciones as productos_count')->orderBy('id')->paginate(15), 'tipo' => 'tamanos', 'titulo' => 'Tamaños']);
    }
}
