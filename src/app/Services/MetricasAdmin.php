<?php

namespace App\Services;

use App\Models\DetallePedido;
use App\Models\Pedido;
use Illuminate\Support\Carbon;

class MetricasAdmin
{
    public function __construct(private Inventario $inventario) {}

    public function resumen(?Carbon $mes = null): array
    {
        $inicio = ($mes ?? now())->copy()->setTimezone('America/Asuncion')->startOfMonth();
        $fin = $inicio->copy()->addMonth()->utc();
        $inicio->utc();
        $pedidos = Pedido::whereIn('estado', ['confirmado', 'preparando', 'enviado', 'entregado'])
            ->whereHas('estados', fn ($q) => $q->where('estado', 'confirmado')->where('created_at', '>=', $inicio)->where('created_at', '<', $fin));
        $ids = (clone $pedidos)->select('id');
        $detalles = DetallePedido::whereIn('pedido_id', $ids);
        $unidades = (int) (clone $detalles)->sum('cantidad');
        $cinco = (int) (clone $detalles)->whereHas('presentacion.tamano', fn ($q) => $q->where('nombre', '5 ml'))->sum('cantidad');
        $top = (clone $detalles)->selectRaw('presentacion_id, SUM(cantidad) as unidades')->groupBy('presentacion_id')
            ->with(['presentacion.producto', 'presentacion.tamano'])->orderByDesc('unidades')->orderBy('presentacion_id')->limit(5)->get()
            ->map(fn ($d) => ['presentacion' => $d->presentacion, 'unidades' => (int) $d->unidades]);
        $fecha = Pedido::where('estado', 'pendiente')->min('fecha');

        return [
            'pendientes' => Pedido::where('estado', 'pendiente')->count(),
            'pendiente_mas_viejo' => $fecha ? Carbon::parse($fecha, 'UTC') : null,
            'ventas_mes' => (int) (clone $pedidos)->sum('total'),
            'pedidos_mes' => (clone $pedidos)->count(),
            'decants_mes' => $unidades,
            'porcentaje_5ml' => $unidades ? (int) round($cinco * 100 / $unidades) : 0,
            'mas_vendidos' => $top,
            'criticas' => $this->inventario->criticas(),
        ];
    }
}
