        @if ($consulta !== '' && $productos->isEmpty())
            <div class="search-empty"><x-phosphor-magnifying-glass-light aria-hidden="true" /><h2>No encontramos "{{ $consulta }}"</h2><p>Probá con otra palabra o escribinos y te ayudamos a elegir.</p><a href="{{ $whatsapp }}" class="button button-outline"><x-phosphor-whatsapp-logo aria-hidden="true" />Consultar por WhatsApp</a></div>
        @else
            <div class="search-results">
                <aside><h2>Marcas y aromas</h2>
                    @foreach ($marcas as $marca)<a href="{{ route('tienda.marcas.show', $marca) }}">{{ \App\Support\Texto::resaltar($marca->nombre, $consulta) }}</a>@endforeach
                    @foreach ($notas as $nota)<a href="{{ route('tienda.buscar', ['q' => $nota->nombre]) }}">Notas: {{ \App\Support\Texto::resaltar($nota->nombre, $consulta) }}</a>@endforeach
                    <h2>Búsquedas populares</h2><a href="{{ route('tienda.buscar', ['q' => 'Dior']) }}">Dior</a><a href="{{ route('tienda.buscar', ['q' => 'vainilla']) }}">Vainilla</a>
                </aside>
                <section id="resultados-perfumes" aria-label="Resultados de búsqueda"><h2>Perfumes ({{ $productos->count() }})</h2>
                    @forelse ($productos as $producto)
                        <a class="search-result" href="{{ url('/perfumes/'.$producto->slug) }}">
                            <div class="search-thumbnail">@if ($producto->imagen)<img src="{{ asset('storage/'.$producto->imagen) }}" alt="" loading="lazy">@else<span class="bottle" aria-hidden="true"></span>@endif</div>
                            <div><h3>{{ \App\Support\Texto::resaltar($producto->nombre, $consulta) }}</h3><p>{{ \App\Support\Texto::resaltar($producto->marca->nombre, $consulta) }} · {{ $producto->etiquetaGenero() }}</p></div>
                            <span>desde {{ \App\Support\Moneda::gs((float) $producto->presentaciones->where('activo', true)->filter(fn ($p) => in_array($p->tamano->nombre, ['5 ml', '10 ml'], true))->min('precio')) }}</span>
                        </a>
                    @empty<p class="muted">Buscá por perfume, marca o nota.</p>@endforelse
                </section>
            </div>
        @endif
