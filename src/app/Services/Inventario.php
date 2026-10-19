<?php

namespace App\Services;

use App\Exceptions\StockInsuficiente;
use App\Models\MovimientoInventario;
use App\Models\ProductoPresentacion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Inventario
{
    public function registrar(ProductoPresentacion $p, string $tipo, int $cantidad, string $motivo, ?Usuario $usuario = null): MovimientoInventario
    {
        if (! in_array($tipo, ['entrada', 'salida'], true)) {
            throw new InvalidArgumentException('El tipo debe ser entrada o salida.');
        }
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser un entero mayor que cero.');
        }
        if (trim($motivo) === '') {
            throw new InvalidArgumentException('El motivo es requerido.');
        }

        return DB::transaction(function () use ($p, $tipo, $cantidad, $motivo, $usuario): MovimientoInventario {
            $presentacion = ProductoPresentacion::whereKey($p->id)->lockForUpdate()->firstOrFail();
            if ($tipo === 'salida' && $presentacion->stock < $cantidad) {
                throw new StockInsuficiente($presentacion, $presentacion->stock);
            }
            $presentacion->stock += $tipo === 'entrada' ? $cantidad : -$cantidad;
            $presentacion->save();

            return $presentacion->movimientos()->create([
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'stock_resultante' => $presentacion->stock,
                'usuario_id' => $usuario?->id,
                'fecha' => now(),
            ]);
        });
    }

    /** @return Collection<int, ProductoPresentacion> */
    public function criticas(): Collection
    {
        return ProductoPresentacion::with(['producto', 'tamano'])->where('activo', true)->where('stock', '<=', 3)->orderBy('stock')->orderBy('id')->get();
    }
}
