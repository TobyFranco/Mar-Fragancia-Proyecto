<?php

namespace App\Services;

use App\Models\Pedido;
use App\Support\Moneda;

class MensajeWhatsapp
{
    public function texto(Pedido $p): string
    {
        $p->loadMissing(['detalles.presentacion.producto.marca', 'detalles.presentacion.tamano']);
        $lineas = ['¡Hola Mar Fragancias! Quiero hacer este pedido (#'.$p->numero.'):', ''];
        foreach ($p->detalles as $detalle) {
            $presentacion = $detalle->presentacion;
            $producto = $presentacion->producto;
            $lineas[] = '• '.$producto->nombre.' ('.$producto->marca->nombre.') · Decant '.$presentacion->tamano->nombre.' × '.$detalle->cantidad.' · '.Moneda::gs((float) $detalle->subtotal);
        }
        $envio = match ($p->metodo_entrega) {
            'asuncion' => 'Envío Asunción: '.Moneda::gs((float) $p->costo_envio),
            'interior' => 'Envío al interior: a coordinar',
            default => 'Envío: '.Moneda::gs(0),
        };
        $entrega = $p->metodo_entrega === 'retiro' ? 'Retiro (a coordinar)' : implode(', ', array_filter([$p->direccion, $p->barrio, $p->ciudad], fn ($valor) => $valor !== null && $valor !== ''));
        array_push($lineas, '', 'Subtotal: '.Moneda::gs((float) $p->subtotal), $envio, 'Total: '.Moneda::gs((float) $p->total), '', 'Nombre: '.$p->nombre_contacto, 'Entrega: '.$entrega);
        if ($p->referencia && $p->metodo_entrega !== 'retiro') {
            $lineas[] = 'Ref.: '.$p->referencia;
        }

        return implode("\n", $lineas);
    }

    public function url(Pedido $p): string
    {
        return 'https://wa.me/'.config('tienda.whatsapp').'?text='.rawurlencode($this->texto($p));
    }
}
