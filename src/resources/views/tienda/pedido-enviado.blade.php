<x-tienda.layout :title="'Pedido '.$pedido->numero">
    <section class="page-width order-sent">
        <div class="order-check"><x-phosphor-check-light aria-hidden="true" /></div>
        <h1>¡Listo! Tu pedido {{ $pedido->numero }} está en WhatsApp</h1>
        <p>Enviá el mensaje en el chat que se abrió. Te respondemos para confirmar el pago y el envío.</p>
        <div class="order-actions"><a class="button button-whatsapp" href="{{ $whatsapp }}"><x-phosphor-whatsapp-logo aria-hidden="true" />Abrir WhatsApp de nuevo</a><a class="button button-outline" href="{{ route('tienda.catalogo') }}">Seguir viendo perfumes</a></div>
        <p class="muted">Total {{ \App\Support\Moneda::gs((float) $pedido->total) }} · {{ $pedido->metodo_entrega === 'retiro' ? 'Retiro a coordinar' : 'Envío a '.$pedido->ciudad }} · {{ $pedido->detalles->sum('cantidad') }} {{ $pedido->detalles->sum('cantidad') === 1 ? 'unidad' : 'unidades' }}</p>
    </section>
</x-tienda.layout>
