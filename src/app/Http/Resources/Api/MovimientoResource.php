<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'presentacion_id' => $this->presentacion_id,
            'tipo' => $this->tipo,
            'cantidad' => (int) $this->cantidad,
            'motivo' => $this->motivo,
            'stock_resultante' => $this->stock_resultante,
            'usuario_id' => $this->usuario_id,
            'fecha' => $this->fecha?->toIso8601String(),
        ];
    }
}
