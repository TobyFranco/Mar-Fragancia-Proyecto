<x-admin.layout title="Dashboard">
    <header class="admin-top"><h1>Hola, {{ auth()->user()->nombre }}</h1><p class="admin-muted">{{ now()->setTimezone('America/Asuncion')->locale('es')->translatedFormat('l j \d\e F') }}</p></header>
    <div class="admin-kpis">
        <section class="admin-kpi"><h2>Pedidos por confirmar</h2><strong>{{ $pendientes }}</strong><p>{{ $pendiente_mas_viejo ? 'El más viejo: '.$pendiente_mas_viejo->locale('es')->diffForHumans() : 'No hay pedidos pendientes' }}</p></section>
        <section class="admin-kpi"><h2>Ventas del mes</h2><strong>{{ \App\Support\Moneda::gs($ventas_mes) }}</strong><p>{{ $pedidos_mes }} pedidos</p></section>
        <section class="admin-kpi"><h2>Decants vendidos</h2><strong>{{ $decants_mes }}</strong><p>5 ml es el {{ $porcentaje_5ml }}%</p></section>
        <section class="admin-kpi admin-warning"><h2>Stock crítico</h2><strong>{{ $criticas->count() }}</strong><p>Presentaciones con stock de 3 o menos</p></section>
    </div>
    <div class="admin-panels">
        <section class="admin-panel"><h2>Más vendidos este mes</h2>
            @forelse($mas_vendidos as $fila)
                <div class="admin-bar-row"><span>{{ $fila['presentacion']->producto->nombre }} · {{ $fila['presentacion']->tamano->nombre }}</span><meter min="0" max="{{ $mas_vendidos->max('unidades') }}" value="{{ $fila['unidades'] }}" aria-label="{{ $fila['unidades'] }} unidades"></meter><span>{{ $fila['unidades'] }}</span></div>
            @empty<p class="admin-muted">Todavía no hay ventas confirmadas este mes.</p>@endforelse
        </section>
        <section class="admin-panel"><h2>Stock crítico</h2><div class="admin-table-wrap"><table><thead><tr><th>Perfume</th><th>Tamaño</th><th>Stock</th></tr></thead><tbody>
            @forelse($criticas as $p)<tr><td>{{ $p->producto->nombre }}</td><td>{{ $p->tamano->nombre }}</td><td class="admin-low">{{ $p->stock }}</td></tr>
            @empty<tr><td colspan="3">No hay presentaciones con stock crítico.</td></tr>@endforelse
        </tbody></table></div></section>
    </div>
</x-admin.layout>
