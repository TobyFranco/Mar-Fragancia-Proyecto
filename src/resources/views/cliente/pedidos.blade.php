<x-tienda.layout title="Mis pedidos">
    <section class="page-width account-page">
        <a class="text-link" href="{{ route('cliente.home') }}">Volver a mi cuenta</a>
        <h1>Mis pedidos</h1>
        @forelse ($pedidos as $pedido)
            <a class="account-order" href="{{ route('tienda.pedido', $pedido->numero) }}">
                <span><strong>{{ $pedido->numero }}</strong><br><span class="muted">{{ $pedido->fecha->format('d/m/Y') }}</span></span>
                <span>{{ $pedido->etiquetaEstado() }}</span>
                <strong>{{ \App\Support\Moneda::gs((float) $pedido->total) }}</strong>
                <span class="text-link">Ver pedido</span>
            </a>
        @empty
            <p>Todavía no tenés pedidos.</p>
            <a class="button button-outline" href="{{ route('tienda.catalogo') }}">Ver catálogo</a>
        @endforelse
        @if ($pedidos->hasPages())<div class="account-pagination">{{ $pedidos->links() }}</div>@endif
    </section>
</x-tienda.layout>
