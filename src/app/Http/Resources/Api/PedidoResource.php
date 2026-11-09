<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'numero' => $this->numero, 'estado' => $this->estado, 'fecha' => $this->fecha?->toIso8601String(), 'total' => (string) $this->total, 'subtotal' => (string) $this->subtotal, 'costo_envio' => (string) $this->costo_envio,
            'contacto' => $this->whenLoaded('cliente', fn () => ['nombre' => $this->nombre_contacto ?? $this->cliente?->usuario?->nombre, 'telefono' => $this->telefono ?? $this->cliente?->telefono, 'email' => $this->email ?? $this->cliente?->usuario?->email, 'cedula' => $this->cedula]),
            'entrega' => $this->whenLoaded('cliente', fn () => ['metodo' => $this->metodo_entrega, 'ciudad' => $this->ciudad, 'barrio' => $this->barrio, 'direccion' => $this->direccion ?? $this->cliente?->direccion, 'referencia' => $this->referencia, 'observacion' => $this->observacion]),
            'items' => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => ['id' => $d->id, 'presentacion_id' => $d->presentacion_id, 'perfume' => $d->presentacion->producto->nombre, 'tamano' => $d->presentacion->tamano->nombre, 'cantidad' => $d->cantidad, 'precio_unitario' => (string) $d->precio_unitario, 'subtotal' => (string) $d->subtotal])),
            'historial' => $this->whenLoaded('estados', fn () => $this->estados->map(fn ($e) => ['id' => $e->id, 'estado' => $e->estado, 'fecha' => $e->created_at?->toIso8601String()]))];
    }
}
