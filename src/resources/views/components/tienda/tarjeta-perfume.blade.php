@props(['producto'])
@php
    $presentaciones = $producto->presentaciones->filter(fn ($p) => in_array($p->tamano->nombre, ['5 ml', '10 ml'], true));
    $precio = $presentaciones->where('activo', true)->min('precio');
@endphp
<article {{ $attributes->merge(['class' => 'perfume-card']) }}>
    <a href="{{ url('/perfumes/'.$producto->slug) }}" class="card-link">
        <div class="card-image" style="position: relative; overflow: hidden; border: 2px solid var(--color-navy); border-radius: 8px; background: var(--color-sage-bg); aspect-ratio: 1/1;">
            <span class="gender-tag">{{ $producto->etiquetaGenero() }}</span>
            @if ($producto->imagen)
                <img src="{{ filter_var($producto->imagen, FILTER_VALIDATE_URL) ? $producto->imagen : asset('storage/'.$producto->imagen) }}"
                     alt="{{ $producto->nombre }}"
                     loading="lazy"
                     style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
            @else
                <span class="bottle" aria-hidden="true"></span>
            @endif
        </div>
        <p class="card-brand">{{ $producto->marca->nombre }}</p>
        <h3 style="color: var(--color-navy);">{{ $producto->nombre }}</h3>
        <div class="size-chips">
            @foreach (['5 ml', '10 ml'] as $nombre)
                @php($presentacion = $presentaciones->first(fn ($p) => $p->tamano->nombre === $nombre))
                @if ($presentacion)
                    <span @class(['line-through' => !$presentacion->disponible()]) aria-label="{{ str_replace(' ', '', $nombre) }}{{ $presentacion->disponible() ? '' : ' sin stock' }}">{{ str_replace(' ', '', $nombre) }}</span>
                @endif
            @endforeach
        </div>
        <p class="card-price">Decant desde <strong style="color: var(--color-sage)">{{ \App\Support\Moneda::gs((float) $precio) }}</strong></p>
    </a>
</article>
