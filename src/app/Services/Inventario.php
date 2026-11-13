<?php

namespace App\Services;

use App\Exceptions\StockFueraDeRango;
use App\Exceptions\StockInsuficiente;
use App\Models\MovimientoInventario;
use App\Models\ProductoPresentacion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class Inventario
{
    /** Máximo de la columna producto_presentacion.stock (INT con signo). */
    public const STOCK_MAXIMO = 2147483647;

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
            if ($tipo === 'entrada' && $cantidad > self::STOCK_MAXIMO - $presentacion->stock) {
                throw new StockFueraDeRango($presentacion, self::STOCK_MAXIMO);
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

    public function ajustar(ProductoPresentacion $p, int $stockNuevo, ?string $motivo, ?Usuario $usuario): MovimientoInventario
    {
        if ($stockNuevo < 0 || $stockNuevo > self::STOCK_MAXIMO) {
            throw new InvalidArgumentException('El stock nuevo está fuera de rango.');
        }

        return DB::transaction(function () use ($p, $stockNuevo, $motivo, $usuario): MovimientoInventario {
            $presentacion = ProductoPresentacion::whereKey($p->id)->lockForUpdate()->firstOrFail();
            $diferencia = $stockNuevo - $presentacion->stock;
            if ($diferencia === 0) {
                throw ValidationException::withMessages(['stock_nuevo' => 'El stock ya es '.$presentacion->stock.'.']);
            }

            return $this->registrar(
                $presentacion,
                $diferencia > 0 ? 'entrada' : 'salida',
                abs($diferencia),
                trim($motivo ?? '') !== '' ? $motivo : 'Ajuste manual de stock',
                $usuario,
            );
        });
    }

    /** @return Collection<int, ProductoPresentacion> */
    public function criticas(): Collection
    {
        return ProductoPresentacion::with(['producto', 'tamano'])->where('activo', true)->where('stock', '<=', 3)->orderBy('stock')->orderBy('id')->get();
    }
}
