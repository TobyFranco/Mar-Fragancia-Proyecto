<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductoRequest;
use App\Models\Categoria;
use App\Models\DetallePedido;
use App\Models\Marca;
use App\Models\MovimientoInventario;
use App\Models\Nota;
use App\Models\Producto;
use App\Models\Tamano;
use App\Services\Inventario;
use App\Support\Texto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['filtro' => ['nullable', Rule::in([...Producto::GENEROS, 'sin-stock', 'inactivos'])], 'q' => ['nullable', 'string', 'max:150']]);
        $query = Producto::with(['marca', 'presentaciones.tamano']);
        $filtro = $datos['filtro'] ?? null;
        if (in_array($filtro, Producto::GENEROS, true)) {
            $query->where('genero', $filtro);
        } elseif ($filtro === 'inactivos') {
            $query->where('activo', false);
        } elseif ($filtro === 'sin-stock') {
            $query->whereDoesntHave('presentaciones', fn ($q) => $q->where('activo', true)->where('stock', '>', 0)->whereHas('tamano', fn ($t) => $t->whereIn('nombre', ['5 ml', '10 ml'])));
        }
        if ($q = Texto::normalizar($datos['q'] ?? '')) {
            $ids = Producto::select(['id', 'nombre'])->get()->filter(fn ($p) => str_contains(Texto::normalizar($p->nombre), $q))->modelKeys();
            $query->whereIn('id', $ids);
        }

        return view('admin.productos.index', ['productos' => $query->orderBy('nombre')->orderBy('id')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.productos.create', $this->formulario(new Producto));
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.edit', $this->formulario($producto));
    }

    private function formulario(Producto $producto): array
    {
        $producto->load(['presentaciones.tamano', 'notas']);

        return ['producto' => $producto, 'marcas' => Marca::orderBy('nombre')->get(), 'categorias' => Categoria::orderBy('nombre')->get(), 'tamanos' => Tamano::whereIn('nombre', ['5 ml', '10 ml'])->orderBy('id')->get(), 'notas' => Nota::orderBy('nombre')->get()];
    }

    public function store(ProductoRequest $request, Inventario $inventario): RedirectResponse
    {
        return $this->guardar($request, new Producto, $inventario);
    }

    public function update(ProductoRequest $request, Producto $producto, Inventario $inventario): RedirectResponse
    {
        return $this->guardar($request, $producto, $inventario);
    }

    private function guardar(ProductoRequest $request, Producto $producto, Inventario $inventario): RedirectResponse
    {
        $datos = $request->validated();
        $creando = ! $producto->exists;
        $imagenNueva = $request->file('imagen')?->store('productos', 'public');
        $imagenAnterior = null;
        try {
            DB::transaction(function () use ($request, $datos, $producto, $creando, $imagenNueva, &$imagenAnterior, $inventario): void {
                if (! $creando) {
                    $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
                }
                $imagenAnterior = $producto->imagen;
                $producto->fill(collect($datos)->only(['nombre', 'marca_id', 'categoria_id', 'genero', 'descripcion'])->all());
                $producto->activo = $request->boolean('activo');
                $producto->destacado = $request->boolean('destacado');
                $producto->imagen = $imagenNueva ?? $imagenAnterior;
                $producto->save();
                $existentes = Nota::all()->keyBy(fn ($nota) => Texto::normalizar($nota->nombre));
                $nombres = [...($datos['notas'] ?? []), ...preg_split('/[,\r\n]+/u', $datos['notas_nuevas'] ?? '')];
                $ids = [];
                foreach ($nombres as $nombre) {
                    $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));
                    if ($nombre === '') {
                        continue;
                    }
                    $clave = Texto::normalizar($nombre);
                    $nota = $existentes->get($clave) ?? Nota::firstOrCreate(['nombre' => $nombre]);
                    $existentes->put($clave, $nota);
                    $ids[] = $nota->id;
                }
                $producto->notas()->sync(array_unique($ids));
                foreach ($datos['presentaciones'] as $tamanoId => $fila) {
                    $pres = $producto->presentaciones()->where('tamano_id', $tamanoId)->first();
                    if (! isset($fila['precio']) || $fila['precio'] === '') {
                        $pres?->update(['activo' => false]);

                        continue;
                    }
                    $atributos = ['precio' => $fila['precio'], 'activo' => (bool) ($fila['activo'] ?? false)];
                    if ($pres) {
                        $pres->update($atributos);
                    } else {
                        $pres = $producto->presentaciones()->create(['tamano_id' => $tamanoId, ...$atributos]);
                    }
                    if ($creando && ($cantidad = (int) ($fila['stock_inicial'] ?? 0)) > 0) {
                        $inventario->registrar($pres, 'entrada', $cantidad, 'Stock inicial', $request->user());
                    }
                }
            });
        } catch (Throwable $e) {
            if ($imagenNueva) {
                Storage::disk('public')->delete($imagenNueva);
            }
            throw $e;
        }
        if ($imagenNueva && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return to_route('admin.productos.index')->with('success', $creando ? 'Perfume creado.' : 'Perfume actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        try {
            DB::transaction(function () use ($producto): void {
                $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
                $presentaciones = $producto->presentaciones()->orderBy('id')->lockForUpdate()->get();
                if (DetallePedido::whereIn('presentacion_id', $presentaciones->modelKeys())->exists()) {
                    throw ValidationException::withMessages(['producto' => 'Este perfume tiene pedidos y no se puede eliminar. Desmarcá Visible en la tienda para ocultarlo.']);
                }
                if (MovimientoInventario::whereIn('presentacion_id', $presentaciones->modelKeys())->exists()) {
                    throw ValidationException::withMessages(['producto' => 'Este perfume tiene movimientos de inventario y no se puede eliminar. Desmarcá Visible en la tienda para ocultarlo.']);
                }
                $producto->notas()->detach();
                $producto->presentaciones()->delete();
                $producto->delete();
            });
        } catch (ValidationException $e) {
            return to_route('admin.productos.edit', $producto->id)->withErrors($e->errors());
        }
        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        return to_route('admin.productos.index')->with('success', 'Perfume eliminado.');
    }
}
