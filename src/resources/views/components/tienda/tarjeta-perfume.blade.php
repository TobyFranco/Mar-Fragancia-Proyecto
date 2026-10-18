@props(['producto'])
@php
    $presentaciones = $producto->presentaciones->filter(fn ($p) => in_array($p->tamano->nombre, ['5 ml', '10 ml'], true));
    $precio = $presentaciones->where('activo', true)->min('precio');
@endphp
<article {{ $attributes->merge(['class' => 'perfume-card']) }}>
    <a href="{{ url('/perfumes/'.$producto->slug) }}" class="card-link">
        <div class="card-image">
            <span class="gender-tag">{{ $producto->etiquetaGenero() }}</span>
            @if ($producto->imagen)
                <img src="{{ asset('storage/'.$producto->imagen) }}" alt="{{ $producto->nombre }}" loading="lazy" width="320" height="400">
            @else
                <span class="bottle" aria-hidden="true"></span>
            @endif
        </div>
        <p class="card-brand">{{ $producto->marca->nombre }}</p>
        <h3>{{ $producto->nombre }}</h3>
        <div class="size-chips">
            @foreach (['5 ml', '10 ml'] as $nombre)
                @php($presentacion = $presentaciones->first(fn ($p) => $p->tamano->nombre === $nombre))
                @if ($presentacion)
                    <span @class(['line-through' => !$presentacion->disponible()]) aria-label="{{ str_replace(' ', '', $nombre) }}{{ $presentacion->disponible() ? '' : ' sin stock' }}">{{ str_replace(' ', '', $nombre) }}</span>
                @endif
            @endforeach
        </div>
        <p class="card-price">Decant desde <strong>{{ \App\Support\Moneda::gs((float) $precio) }}</strong></p>
    </a>
</article>
