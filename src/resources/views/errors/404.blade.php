<x-tienda.layout title="Página no encontrada" :mostrar-carrito="false">
    <section class="page-width not-found-page">
        <x-phosphor-magnifying-glass-light aria-hidden="true" />
        <p class="eyebrow">404</p><h1>No encontramos esta página</h1><p>Puede que el enlace haya cambiado. Volvé al inicio o encontrá tu próximo decant en el catálogo.</p>
        <div class="hero-actions"><a class="button" href="{{ route('tienda.inicio') }}">Volver al inicio</a><a class="button button-outline" href="{{ route('tienda.catalogo') }}">Ver perfumes</a></div>
    </section>
</x-tienda.layout>
