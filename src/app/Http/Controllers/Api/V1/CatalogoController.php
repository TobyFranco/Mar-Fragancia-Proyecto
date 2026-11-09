<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CatalogoRequest;
use App\Http\Resources\Api\CategoriaResource;
use App\Http\Resources\Api\MarcaResource;
use App\Http\Resources\Api\ProductoResource;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Support\Texto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogoController extends Controller
{
    public function marcas(): AnonymousResourceCollection
    {
        return MarcaResource::collection(Marca::withCount(['productos' => fn ($q) => $q->visibles()])->orderBy('nombre')->paginate(20));
    }

    public function categorias(): AnonymousResourceCollection
    {
        return CategoriaResource::collection(Categoria::orderBy('nombre')->paginate(20));
    }

    private function productos(): Builder
    {
        return Producto::visibles()->with(['marca', 'categoria', 'notas', 'presentaciones' => fn ($q) => $q->where('activo', true)->whereHas('tamano', fn ($t) => $t->whereIn('nombre', ['5 ml', '10 ml']))->with('tamano')->orderBy('id')]);
    }

    public function index(CatalogoRequest $request): AnonymousResourceCollection
    {
        $d = $request->validated();
        $q = $this->productos();
        if (isset($d['marca'])) {
            $q->whereHas('marca', fn ($m) => $m->where('slug', $d['marca']));
        }
        if (isset($d['categoria'])) {
            $q->where('categoria_id', $d['categoria']);
        }
        if (isset($d['genero'])) {
            $q->where('genero', $d['genero']);
        }
        if (isset($d['tamano'])) {
            $q->whereHas('presentaciones', fn ($p) => $p->where('activo', true)->where('stock', '>', 0)->whereHas('tamano', fn ($t) => $t->where('nombre', $d['tamano'].' ml')));
        }
        $buscar = Texto::normalizar($d['q'] ?? '');
        if ($buscar !== '') {
            $ids = (clone $q)->get()->filter(fn ($p) => str_contains(Texto::normalizar($p->nombre.' '.$p->marca->nombre.' '.$p->notas->pluck('nombre')->implode(' ')), $buscar))->modelKeys();
            $q->whereIn('id', $ids);
        }

        return ProductoResource::collection($q->orderBy('nombre')->orderBy('id')->paginate(20)->withQueryString());
    }

    public function show(string $slug): ProductoResource
    {
        return new ProductoResource($this->productos()->where('slug', $slug)->firstOrFail());
    }
}
