<?php

namespace App\Http\Controllers;

use App\Models\Tamano;
use Illuminate\Http\Request;

class TamanoController extends Controller
{
    public function index()
    {
        $tamanos = Tamano::orderBy('nombre')->paginate(10);
        return view('admin.tamanos.index', compact('tamanos'));
    }

    public function create()
    {
        return view('admin.tamanos.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:tamanos,nombre'],
            'descripcion' => ['nullable', 'string', 'max:100'],
        ]);

        Tamano::create($data);

        return redirect()->route('admin.tamanos.index')->with('success', 'Tamaño creado correctamente.');
    }

    public function edit(Tamano $tamano)
    {
        return view('admin.tamanos.edit', compact('tamano'));
    }

    public function update(Request $request, Tamano $tamano)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:tamanos,nombre,' . $tamano->id],
            'descripcion' => ['nullable', 'string', 'max:100'],
        ]);

        $tamano->update($data);

        return redirect()->route('admin.tamanos.index')->with('success', 'Tamaño actualizado correctamente.');
    }

    public function destroy(Tamano $tamano)
    {
        if ($tamano->presentaciones()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene presentaciones asociadas.');
        }

        $tamano->delete();
        return redirect()->route('admin.tamanos.index')->with('success', 'Tamaño eliminado.');
    }
}