<x-tienda.layout title="Marcas">
    <div class="page-width page-intro"><h1>Marcas</h1><p>{{ $marcas->count() }} marcas · tocá una para ver sus perfumes</p></div>
    <nav class="alphabet page-width" aria-label="Índice alfabético de marcas">
        @foreach (range('A', 'Z') as $letra)
            @if ($grupos->has($letra))<a href="#letra-{{ $letra }}">{{ $letra }}</a>@else<span aria-disabled="true">{{ $letra }}</span>@endif
        @endforeach
    </nav>
    <div class="brand-groups page-width">
        @foreach ($grupos as $letra => $grupo)
            <section id="letra-{{ $letra }}" aria-label="Marcas con {{ $letra }}"><h2>{{ $letra }}</h2>
                <ul>@foreach ($grupo as $marca)<li><a href="{{ route('tienda.marcas.show', $marca) }}"><span>{{ $marca->nombre }}</span><span class="brand-total">{{ $marca->productos_count }} {{ $marca->productos_count === 1 ? 'perfume' : 'perfumes' }}</span></a></li>@endforeach</ul>
            </section>
        @endforeach
    </div>
</x-tienda.layout>
