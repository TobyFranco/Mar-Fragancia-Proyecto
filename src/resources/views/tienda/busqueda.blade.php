<x-tienda.layout title="Buscar perfumes">
    <div class="page-width search-page">
        <h1 class="sr-only">Buscar perfumes</h1>
        <form class="search-form" method="GET" action="{{ route('tienda.buscar') }}">
            <label for="buscar-perfume" class="sr-only">Buscá por perfume, marca o nota</label>
            <input id="buscar-perfume" type="search" name="q" value="{{ $consulta }}" placeholder="Buscá por perfume, marca o nota" autocomplete="off" enterkeyhint="search">
            <button type="submit" class="icon-button" aria-label="Buscar"><x-phosphor-magnifying-glass-light aria-hidden="true" /></button>
            <p class="search-hint">Enter para ver todos</p>
        </form>
        @if ($consulta !== '' && $productos->isEmpty())
            <div class="search-empty"><x-phosphor-magnifying-glass-light aria-hidden="true" /><h2>No encontramos "{{ $consulta }}"</h2><p>Probá con otra palabra o escribinos y te ayudamos a elegir.</p><a href="{{ $whatsapp }}" class="button button-outline"><x-phosphor-whatsapp-logo aria-hidden="true" />Consultar por WhatsApp</a></div>
        @else
            <div class="search-results">
                <aside><h2>Marcas y aromas</h2>
                    @foreach ($marcas as $marca)<a href="{{ route('tienda.marcas.show', $marca) }}">{{ $marca->nombre }}</a>@endforeach
                    @foreach ($notas as $nota)<a href="{{ route('tienda.buscar', ['q' => $nota->nombre]) }}">Notas: {{ $nota->nombre }}</a>@endforeach
                    <h2>Búsquedas populares</h2><a href="{{ route('tienda.buscar', ['q' => 'Dior']) }}">Dior</a><a href="{{ route('tienda.buscar', ['q' => 'vainilla']) }}">Vainilla</a>
                </aside>
                <section id="resultados-perfumes" aria-label="Resultados de búsqueda"><h2>Perfumes ({{ $productos->count() }})</h2>
                    @forelse ($productos as $producto)
                        <a class="search-result" href="{{ url('/perfumes/'.$producto->slug) }}">
                            <div class="search-thumbnail">@if ($producto->imagen)<img src="{{ asset('storage/'.$producto->imagen) }}" alt="" loading="lazy">@else<span class="bottle" aria-hidden="true"></span>@endif</div>
                            <div><h3>{{ $producto->nombre }}</h3><p>{{ $producto->marca->nombre }} · {{ $producto->etiquetaGenero() }}</p></div>
                            <span>desde {{ \App\Support\Moneda::gs((float) $producto->presentaciones->where('activo', true)->filter(fn ($p) => in_array($p->tamano->nombre, ['5 ml', '10 ml'], true))->min('precio')) }}</span>
                        </a>
                    @empty<p class="muted">Buscá por perfume, marca o nota.</p>@endforelse
                </section>
            </div>
        @endif
    </div>
</x-tienda.layout>
