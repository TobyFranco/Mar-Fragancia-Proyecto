<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PresentacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'tamano' => $this->tamano->nombre, 'precio' => $this->precio, 'stock' => $this->stock, 'disponible' => $this->disponible()];
    }
}
