<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Marca;
use App\Models\Categoria;
use App\Models\Tamano;
use App\Models\ProductoPresentacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('marca', 'categoria')->orderBy('nombre')->paginate(10);
        return view('admin.productos.index', compact('productos'));
    }

    public function create()
    {
        $marcas = Marca::orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();
        $tamanos = Tamano::orderBy('nombre')->get();

        return view('admin.productos.create', compact('marcas', 'categorias', 'tamanos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'marca_id' => ['required', 'exists:marcas,id'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activo' => ['nullable'],
            'presentaciones' => ['required', 'array'],
            'presentaciones.*.precio' => ['nullable', 'numeric', 'min:0'],
            'presentaciones.*.stock' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        }

        $data['activo'] = $request->has('activo');

        $producto = Producto::create([
            'marca_id' => $data['marca_id'],
            'categoria_id' => $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'] ?? null,
            'activo' => $data['activo'],
        ]);

        foreach ($data['presentaciones'] as $tamanoId => $pres) {
            if (!empty($pres['precio'])) {
                ProductoPresentacion::create([
                    'producto_id' => $producto->id,
                    'tamano_id' => $tamanoId,
                    'precio' => $pres['precio'],
                    'stock' => $pres['stock'] ?? 0,
                    'activo' => true,
                ]);
            }
        }

        return redirect()->route('admin.productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $marcas = Marca::orderBy('nombre')->get();
        $categorias = Categoria::orderBy('nombre')->get();
        $tamanos = Tamano::orderBy('nombre')->get();
        $producto->load('presentaciones');

        return view('admin.productos.edit', compact('producto', 'marcas', 'categorias', 'tamanos'));
    }

    public function update(Request $request, Producto $producto)
    {
        $data = $request->validate([
            'marca_id' => ['required', 'exists:marcas,id'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activo' => ['nullable'],
            'presentaciones' => ['required', 'array'],
            'presentaciones.*.precio' => ['nullable', 'numeric', 'min:0'],
            'presentaciones.*.stock' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('productos', 'public');
        } else {
            $data['imagen'] = $producto->imagen;
        }

        $data['activo'] = $request->has('activo');

        $producto->update([
            'marca_id' => $data['marca_id'],
            'categoria_id' => $data['categoria_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'],
            'activo' => $data['activo'],
        ]);

        foreach ($data['presentaciones'] as $tamanoId => $pres) {
            if (!empty($pres['precio'])) {
                ProductoPresentacion::updateOrCreate(
                    ['producto_id' => $producto->id, 'tamano_id' => $tamanoId],
                    ['precio' => $pres['precio'], 'stock' => $pres['stock'] ?? 0, 'activo' => true]
                );
            }
        }

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado.');
    }
}