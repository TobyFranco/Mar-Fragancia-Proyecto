<x-admin.layout :title="'Pedido '.$pedido->numero">
    <a class="text-link" href="{{ route('admin.pedidos.index') }}"><x-phosphor-arrow-left-light />Volver a pedidos</a>
    <header class="admin-top"><div><h1>Pedido {{ $pedido->numero }} <span class="admin-badge {{ $pedido->claseInsignia() }}">{{ $pedido->etiquetaEstado() }}</span></h1><p class="admin-muted">{{ $pedido->fecha->setTimezone('America/Asuncion')->format('d/m/Y · H:i') }}</p></div>
        <div class="admin-actions">
        @foreach(\App\Models\Pedido::TRANSICIONES[$pedido->estado] ?? [] as $estado)
            <form method="POST" action="{{ route('admin.pedidos.transicion', $pedido->id) }}">@csrf<input type="hidden" name="estado" value="{{ $estado }}"><button class="button {{ $estado === 'cancelado' ? 'button-outline' : '' }}">{{ match($estado) {'confirmado' => 'Confirmar pedido', 'preparando' => 'Preparar pedido', 'enviado' => 'Marcar como enviado', 'entregado' => 'Marcar como entregado', 'cancelado' => 'Cancelar pedido'} }}</button></form>
        @endforeach
        </div>
    </header>
    <div class="admin-detail">
        <section class="admin-panel"><h2>Productos</h2><div class="admin-table-wrap"><table><thead><tr><th>Perfume</th><th>Tamaño</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr></thead><tbody>
            @foreach($pedido->detalles as $detalle)<tr><td>{{ $detalle->presentacion->producto->nombre }} · {{ $detalle->presentacion->producto->marca->nombre }}</td><td>Decant {{ $detalle->presentacion->tamano->nombre }}</td><td>{{ $detalle->cantidad }}</td><td class="admin-money">{{ \App\Support\Moneda::gs($detalle->precio_unitario) }}</td><td class="admin-money">{{ \App\Support\Moneda::gs($detalle->subtotal) }}</td></tr>@endforeach
            <tr><th colspan="4">Subtotal</th><td class="admin-money">{{ \App\Support\Moneda::gs($pedido->subtotal) }}</td></tr>
            <tr><th colspan="4">Envío{{ $pedido->metodo_entrega === 'interior' ? ' al interior (a coordinar)' : '' }}</th><td class="admin-money">{{ \App\Support\Moneda::gs($pedido->costo_envio) }}</td></tr>
            <tr><th colspan="4">Total</th><td class="admin-money"><strong>{{ \App\Support\Moneda::gs($pedido->total) }}</strong></td></tr>
        </tbody></table></div></section>
        <div class="admin-detail-side">
            <section class="admin-panel"><h2>Cliente</h2>
                @if($whatsapp)<a class="text-link" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"><x-phosphor-whatsapp-logo />Escribir por WhatsApp</a>@endif
                <p><strong>{{ $pedido->nombre_contacto ?? $pedido->cliente?->usuario?->nombre ?? 'Sin datos de contacto' }}</strong></p>
                <p class="admin-muted">{{ $pedido->telefono ?? $pedido->cliente?->telefono }} {{ $pedido->email }}</p>
                @if($pedido->cedula)<p>Cédula: {{ $pedido->cedula }}</p>@endif
                <p class="admin-address">{{ match($pedido->metodo_entrega) {'retiro' => 'Retiro (a coordinar)', 'asuncion' => 'Envío Asunción y Gran Asunción', 'interior' => 'Envío al interior (a coordinar)', default => 'Sin datos de entrega'} }}</p>
                <p>{{ implode(', ', array_filter([$pedido->direccion, $pedido->barrio, $pedido->ciudad])) }}</p>
                @if($pedido->referencia)<p class="admin-muted">Ref.: {{ $pedido->referencia }}</p>@endif
                @if($pedido->observacion)<p>{{ $pedido->observacion }}</p>@endif
            </section>
            <section class="admin-panel"><h2>Historial</h2><ol class="admin-timeline">
                @forelse($pedido->estados as $cambio)<li><strong>{{ ucfirst($cambio->estado) }}</strong><br><time datetime="{{ $cambio->created_at->toIso8601String() }}">{{ $cambio->created_at->copy()->setTimezone('America/Asuncion')->format('d/m/Y H:i') }}</time><span class="admin-muted"> · {{ $cambio->usuario?->nombre ?? 'Sistema' }}</span></li>
                @empty<li>No hay historial registrado para este pedido.</li>@endforelse
            </ol></section>
        </div>
    </div>
</x-admin.layout>
