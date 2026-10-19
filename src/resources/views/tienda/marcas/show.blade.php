<x-tienda.layout :title="$marca->nombre">
    <div class="page-width breadcrumbs"><a href="{{ route('tienda.inicio') }}">Inicio</a> / <a href="{{ route('tienda.marcas.index') }}">Marcas</a> / {{ $marca->nombre }}</div>
    <div class="page-width brand-heading">
        <div><p class="eyebrow">Decants de perfumes originales</p><h1>{{ $marca->nombre }}</h1><p class="muted">{{ $productos->count() }} {{ $productos->count() === 1 ? 'perfume' : 'perfumes' }} en decant</p></div>
        <nav class="gender-tabs" aria-label="Filtrar por género">
            @foreach (['' => 'Todos', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir'] as $valor => $etiqueta)
                <a href="{{ route('tienda.marcas.show', ['marca' => $marca, ...($valor ? ['genero' => $valor] : [])]) }}" @class(['selected' => ($genero ?? '') === $valor]) @if (($genero ?? '') === $valor) aria-current="page" @endif>{{ $etiqueta }}</a>
            @endforeach
        </nav>
    </div>
    <section id="resultados-perfumes" class="page-width catalog-results" aria-label="Perfumes de {{ $marca->nombre }}">
        <div class="product-grid">@forelse ($productos as $producto)<x-tienda.tarjeta-perfume :producto="$producto" />@empty<p class="empty-copy">Todavía no hay perfumes en esta selección. <a class="text-link" href="{{ route('tienda.marcas.show', $marca) }}">Ver todos los de {{ $marca->nombre }}</a></p>@endforelse</div>
    </section>
</x-tienda.layout>
