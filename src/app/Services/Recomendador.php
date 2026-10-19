<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class Recomendador
{
    public function parecido(Producto $a, Producto $b): int
    {
        $notasA = $a->notas->pluck('id');
        $notasB = $b->notas->pluck('id');
        $union = $notasA->merge($notasB)->unique()->count();

        return $union === 0 ? 0 : (int) round($notasA->intersect($notasB)->count() / $union * 100);
    }

    public function parecidos(Producto $base, int $limite): SupportCollection
    {
        return $this->candidatos()->whereKeyNot($base->id)->get()
            ->map(fn (Producto $producto) => [
                'producto' => $producto,
                'porcentaje' => $this->parecido($base, $producto),
                'compartidas' => $base->notas->whereIn('id', $producto->notas->modelKeys())->pluck('nombre'),
            ])
            ->filter(fn (array $fila) => $fila['porcentaje'] > 0)
            ->sort(fn (array $a, array $b) => ($b['porcentaje'] <=> $a['porcentaje']) ?: strcmp($a['producto']->nombre, $b['producto']->nombre))
            ->take($limite)->values();
    }

    public function masProbados(int $limite): Collection
    {
        $ventas = DB::table('detalle_pedidos')
            ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.pedido_id')
            ->join('producto_presentacion', 'producto_presentacion.id', '=', 'detalle_pedidos.presentacion_id')
            ->whereIn('pedidos.estado', ['confirmado', 'preparando', 'enviado', 'entregado'])
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('pedido_estados')->whereColumn('pedido_estados.pedido_id', 'pedidos.id')->where('pedido_estados.estado', 'confirmado')->whereBetween('pedido_estados.created_at', [now()->subDays(90)->utc(), now()->utc()]))
            ->selectRaw('producto_presentacion.producto_id, SUM(detalle_pedidos.cantidad) as unidades')
            ->groupBy('producto_presentacion.producto_id');

        return $this->candidatos()
            ->leftJoinSub($ventas, 'ventas', 'ventas.producto_id', '=', 'productos.id')
            ->select('productos.*')
            ->orderByRaw('COALESCE(ventas.unidades, 0) DESC')
            ->orderByRaw('CASE WHEN COALESCE(ventas.unidades, 0) = 0 THEN productos.destacado ELSE 0 END DESC')
            ->orderBy('productos.nombre')
            ->limit($limite)->get();
    }

    private function candidatos(): Builder
    {
        return Producto::visibles()->with(['notas', 'marca', 'presentaciones.tamano'])
            ->whereHas('presentaciones', function (Builder $query) {
                $query->where('activo', true)->where('stock', '>', 0)
                    ->whereHas('tamano', fn (Builder $tamanos) => $tamanos->whereIn('nombre', ['5 ml', '10 ml']));
            });
    }
}
