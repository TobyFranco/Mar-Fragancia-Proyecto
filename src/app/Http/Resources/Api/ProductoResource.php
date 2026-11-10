<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'slug' => $this->slug, 'nombre' => $this->nombre, 'descripcion' => $this->descripcion, 'genero' => $this->genero, 'marca' => new MarcaResource($this->marca), 'categoria' => new CategoriaResource($this->categoria), 'notas' => $this->notas->map(fn ($n) => ['id' => $n->id, 'nombre' => $n->nombre]), 'imagen_url' => $this->imagen ? url(Storage::url($this->imagen)) : null, 'presentaciones' => PresentacionResource::collection($this->presentaciones)];
    }
}
