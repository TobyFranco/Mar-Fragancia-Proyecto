<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class MarcaController extends Controller
{
    public function index(): View
    {
        return view('admin.marcas.index', ['registros' => Marca::withCount('productos')->orderBy('nombre')->paginate(15), 'tipo' => 'marcas', 'titulo' => 'Marcas']);
    }

    public function create(): View
    {
        return view('admin.marcas.create', ['registro' => new Marca, 'tipo' => 'marcas', 'titulo' => 'Nueva marca']);
    }

    public function edit(Marca $marca): View
    {
        return view('admin.marcas.edit', ['registro' => $marca, 'tipo' => 'marcas', 'titulo' => 'Editar marca']);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->guardar($request, new Marca);
    }

    public function update(Request $request, Marca $marca): RedirectResponse
    {
        return $this->guardar($request, $marca);
    }

    private function guardar(Request $request, Marca $marca): RedirectResponse
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100', Rule::unique('marcas', 'nombre')->ignore($marca->id)], 'destacada' => ['sometimes', 'boolean'], 'orden_menu' => ['nullable', 'integer', 'min:0', 'max:2147483647'], 'imagen' => ['nullable', 'image', 'max:2048']], ['nombre.required' => 'Escribí el nombre de la marca.', 'nombre.unique' => 'Ya existe una marca con ese nombre.', 'orden_menu.min' => 'El orden del menú no puede ser negativo.', 'imagen.image' => 'Elegí una imagen válida.']);
        $nueva = $request->file('imagen')?->store('marcas', 'public');
        $anterior = null;
        try {
            DB::transaction(function () use ($marca, $request, $datos, $nueva, &$anterior): void {
                $marcas = Marca::orderBy('id')->lockForUpdate()->get();
                $actual = $marca->exists ? $marcas->firstWhere('id', $marca->id) : $marca;
                if ($request->boolean('destacada') && $marcas->where('destacada', true)->where('id', '!=', $marca->id)->count() >= 8) {
                    throw ValidationException::withMessages(['destacada' => 'Solo puede haber ocho marcas destacadas en el menú.']);
                }
                $anterior = $actual->imagen;
                $actual->fill(['nombre' => $datos['nombre'], 'destacada' => $request->boolean('destacada'), 'orden_menu' => $datos['orden_menu'] ?? null, 'imagen' => $nueva ?? $anterior])->save();
            }, 3);
        } catch (Throwable $e) {
            if ($nueva) {
                Storage::disk('public')->delete($nueva);
            }
            throw $e;
        }
        if ($nueva && $anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return to_route('admin.marcas.index')->with('success', 'Marca guardada.');
    }

    public function destroy(Marca $marca): RedirectResponse
    {
        $eliminada = DB::transaction(function () use ($marca): bool {
            $actual = Marca::whereKey($marca->id)->lockForUpdate()->firstOrFail();
            if ($actual->productos()->exists()) {
                return false;
            }

            return $actual->delete();
        });
        if (! $eliminada) {
            return to_route('admin.marcas.index')->withErrors(['marca' => 'No se puede eliminar la marca: tiene perfumes asociados.']);
        }
        if ($marca->imagen) {
            Storage::disk('public')->delete($marca->imagen);
        }

        return to_route('admin.marcas.index')->with('success','Marca eliminada.');
    }
}
