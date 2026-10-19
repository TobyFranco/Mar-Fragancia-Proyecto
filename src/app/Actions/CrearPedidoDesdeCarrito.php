<?php

namespace App\Actions;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Services\Carrito;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearPedidoDesdeCarrito
{
    public function handle(Carrito $c, array $datos, ?Cliente $cliente): Pedido
    {
        $pedido = DB::transaction(function () use ($c, $datos, $cliente): Pedido {
            $lineas = $c->lineas();
            if ($lineas->isEmpty() || $lineas->sum('cantidad') !== $c->unidades()) {
                throw ValidationException::withMessages(['carrito' => 'Un decant de tu carrito ya no está disponible. Revisá tu carrito para continuar.']);
            }
            $presentaciones = ProductoPresentacion::with(['producto.marca', 'tamano'])->whereIn('id', $lineas->pluck('presentacion.id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $detalles = [];
            foreach ($lineas as $linea) {
                $p = $presentaciones->get($linea['presentacion']->id);
                if (! $p || ! $p->producto->activo || ! $p->activo || ! in_array($p->tamano->nombre, ['5 ml', '10 ml'], true) || $linea['cantidad'] < 1 || $p->stock < $linea['cantidad']) {
                    $original = $linea['presentacion'];
                    throw ValidationException::withMessages(['carrito' => $original->producto->nombre.' · Decant '.$original->tamano->nombre.' no tiene stock suficiente o ya no está disponible. Revisá tu carrito.']);
                }
                $detalles[] = ['presentacion_id' => $p->id, 'cantidad' => $linea['cantidad'], 'precio_unitario' => (int) $p->precio, 'subtotal' => (int) $p->precio * $linea['cantidad']];
            }
            if ($datos['metodo_entrega'] === 'retiro') {
                $datos = [...$datos, 'ciudad' => null, 'barrio' => null, 'direccion' => null, 'referencia' => null];
            }
            $subtotal = array_sum(array_column($detalles, 'subtotal'));
            $envio = $datos['metodo_entrega'] === 'asuncion' ? (int) config('tienda.envio_asuncion') : 0;
            $pedido = Pedido::create([...$datos, 'cliente_id' => $cliente?->id, 'fecha' => now(), 'estado' => 'pendiente', 'subtotal' => $subtotal, 'costo_envio' => $envio, 'total' => $subtotal + $envio]);
            $pedido->detalles()->createMany($detalles);
            $pedido->estados()->create(['estado' => 'pendiente', 'usuario_id' => $cliente?->usuario_id, 'created_at' => now()]);

            return $pedido;
        });
        $c->vaciar();

        return $pedido;
    }
}
