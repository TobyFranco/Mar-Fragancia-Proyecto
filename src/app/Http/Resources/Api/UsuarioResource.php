<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nombre' => $this->nombre, 'email' => $this->email, 'rol' => ['id' => $this->rol->id, 'nombre' => $this->rol->nombre], 'cliente' => $this->cliente ? ['id' => $this->cliente->id, 'telefono' => $this->cliente->telefono, 'direccion' => $this->cliente->direccion] : null];
    }
}
