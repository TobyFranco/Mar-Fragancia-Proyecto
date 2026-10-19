<x-admin.layout title="Pedidos">
    <header class="admin-top"><h1>Pedidos</h1><form method="GET" class="admin-search" action="{{ route('admin.pedidos.index') }}">
        @if(request('estado'))<input type="hidden" name="estado" value="{{ request('estado') }}">@endif
        <label class="sr-only" for="buscar-pedidos">Buscar por número, nombre o teléfono</label><input id="buscar-pedidos" name="q" value="{{ request('q') }}" placeholder="Número, nombre o teléfono"><button class="icon-button" aria-label="Buscar pedidos"><x-phosphor-magnifying-glass-light /></button>
    </form></header>
    <nav class="admin-filters" aria-label="Filtrar pedidos">
        <a href="{{ route('admin.pedidos.index', request()->only('q')) }}" @if(!request('estado')) aria-current="page" @endif>Todos {{ array_sum($contadores) }}</a>
        @foreach($contadores as $estado => $cantidad)<a href="{{ route('admin.pedidos.index', ['estado' => $estado] + request()->only('q')) }}" @if(request('estado') === $estado) aria-current="page" @endif>{{ ucfirst($estado) }} {{ $cantidad }}</a>@endforeach
    </nav>
    <div class="admin-table-wrap"><table class="admin-orders-table"><thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Entrega</th><th>Total</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
        @forelse($pedidos as $pedido)<tr>
            <td><strong>{{ $pedido->numero }}</strong></td><td>{{ $pedido->fecha->setTimezone('America/Asuncion')->format('d/m/Y H:i') }}</td>
            <td>{{ $pedido->nombre_contacto ?? $pedido->cliente?->usuario?->nombre ?? 'Sin datos de contacto' }}<br><span class="admin-muted">{{ $pedido->telefono ?? $pedido->cliente?->telefono }}</span></td>
            <td>{{ match($pedido->metodo_entrega) {'retiro' => 'Retiro', 'asuncion' => 'Asunción y Gran Asunción', 'interior' => 'Interior', default => 'Sin datos de entrega'} }}</td>
            <td class="admin-money">{{ \App\Support\Moneda::gs($pedido->total) }}</td><td><span class="admin-badge {{ $pedido->claseInsignia() }}">{{ $pedido->etiquetaEstado() }}</span></td><td><a class="text-link" href="{{ route('admin.pedidos.show', $pedido->id) }}">Ver<span class="sr-only"> {{ $pedido->numero }}</span></a></td>
        </tr>@empty<tr><td colspan="7">No hay pedidos que coincidan con estos filtros.</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination">{{ $pedidos->links('admin.paginacion') }}</div>
</x-admin.layout>
