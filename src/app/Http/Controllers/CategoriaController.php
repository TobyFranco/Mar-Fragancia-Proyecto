<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias.index', ['registros' => Categoria::withCount('productos')->orderBy('nombre')->paginate(15), 'tipo' => 'categorias', 'titulo' => 'Categorías']);
    }

    public function create(): View
    {
        return view('admin.categorias.create', ['registro' => new Categoria, 'tipo' => 'categorias', 'titulo' => 'Nueva categoría']);
    }

    public function edit(Categoria $categoria): View
    {
        return view('admin.categorias.edit', ['registro' => $categoria, 'tipo' => 'categorias', 'titulo' => 'Editar categoría']);
    }

    public function store(Request $request): RedirectResponse
    {
        Categoria::create($this->datos($request));

        return to_route('admin.categorias.index')->with('success', 'Categoría guardada.');
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->datos($request, $categoria));

        return to_route('admin.categorias.index')->with('success', 'Categoría guardada.');
    }

    private function datos(Request $request, ?Categoria $categoria = null): array
    {
        return $request->validate(['nombre' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($categoria?->id)]], ['nombre.required' => 'Escribí el nombre de la categoría.', 'nombre.unique' => 'Ya existe una categoría con ese nombre.']);
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $eliminada = DB::transaction(function () use ($categoria): bool {
            $actual = Categoria::whereKey($categoria->id)->lockForUpdate()->firstOrFail();
            if ($actual->productos()->exists()) {
                return false;
            }

            return $actual->delete();
        });

        return $eliminada ? to_route('admin.categorias.index')->with('success', 'Categoría eliminada.') : to_route('admin.categorias.index')->withErrors(['categoria' => 'No se puede eliminar la categoría: tiene perfumes asociados.']);
    }
}
