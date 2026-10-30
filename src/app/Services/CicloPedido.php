<?php

namespace App\Services;

use App\Exceptions\TransicionInvalida;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CicloPedido
{
    public function __construct(private Inventario $inventario) {}

    public function pasarA(Pedido $pedido, string $estado, Usuario $admin): Pedido
    {
        return DB::transaction(function () use ($pedido, $estado, $admin): Pedido {
            $actual = Pedido::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if (! $actual->puedePasarA($estado)) {
                throw new TransicionInvalida($actual, $estado);
            }

            $confirmando = $estado === 'confirmado';
            $devolviendo = $estado === 'cancelado' && in_array($actual->estado, ['confirmado', 'preparando'], true);
            if ($confirmando || $devolviendo) {
                $detalles = $actual->detalles()->with('presentacion')->orderBy('presentacion_id')->orderBy('id')->get();
                foreach ($detalles as $detalle) {
                    $this->inventario->registrar(
                        $detalle->presentacion,
                        $confirmando ? 'salida' : 'entrada',
                        $detalle->cantidad,
                        ($confirmando ? 'Venta ' : 'Cancelación ').$actual->numero,
                        $admin,
                    );
                }
            }

            $actual->update(['estado' => $estado]);
            $actual->estados()->create(['estado' => $estado, 'usuario_id' => $admin->id, 'created_at' => now()]);

            return $actual;
        });
    }
}
