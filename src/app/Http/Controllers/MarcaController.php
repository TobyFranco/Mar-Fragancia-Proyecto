<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function index()
    {
        $marcas = Marca::orderBy('nombre')->paginate(10);
        return view('admin.marcas.index', compact('marcas'));
    }

    public function create()
    {
        return view('admin.marcas.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:marcas,nombre'],
        ]);

        Marca::create($data);

        return redirect()->route('admin.marcas.index')->with('success', 'Marca creada correctamente.');
    }

    public function edit(Marca $marca)
    {
        return view('admin.marcas.edit', compact('marca'));
    }

    public function update(Request $request, Marca $marca)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:marcas,nombre,' . $marca->id],
        ]);

        $marca->update($data);

        return redirect()->route('admin.marcas.index')->with('success', 'Marca actualizada correctamente.');
    }

    public function destroy(Marca $marca)
    {
        if ($marca->productos()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene productos asociados.');
        }

        $marca->delete();
        return redirect()->route('admin.marcas.index')->with('success', 'Marca eliminada.');
    }
}