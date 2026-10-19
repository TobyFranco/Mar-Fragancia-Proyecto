<x-tienda.layout title="Tu carrito">
    <div class="page-width cart-page">
        <h1>Tu carrito</h1>
        <x-tienda.carrito-contenido :lineas="$lineas" :ultimo="$ultimo" :recomendados="$recomendados" :subtotal="$subtotal" />
    </div>
</x-tienda.layout>
