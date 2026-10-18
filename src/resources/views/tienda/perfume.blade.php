<x-tienda.layout :title="$producto->nombre">
    <div class="page-width perfume-page" x-data="{
        opciones: {{ Js::from($opciones) }}, elegida: {{ Js::from($elegida['id'] ?? null) }}, cantidad: 1,
        get opcion() { return this.opciones.find(o => o.id === Number(this.elegida)) },
        get precio() { return '₲ ' + (this.opcion?.precio ?? 0).toLocaleString('es-PY') },
        ajustar() { this.cantidad = Math.max(1, Math.min(Number(this.cantidad) || 1, this.opcion?.stock || 1)) }
    }">
        <nav class="breadcrumbs" aria-label="Ubicación"><a href="{{ route('tienda.inicio') }}">Inicio</a><span>/</span><a href="{{ route('tienda.marcas.show', $producto->marca) }}">{{ $producto->marca->nombre }}</a><span>/</span><span>{{ $producto->nombre }}</span></nav>
        <div class="perfume-detail">
            <div class="perfume-gallery">
                @if ($producto->imagen)
                    <img src="{{ asset('storage/'.$producto->imagen) }}" alt="{{ $producto->nombre }}" width="600" height="640">
                @else
                    <span class="bottle" aria-hidden="true"></span>
                @endif
            </div>
            <div class="perfume-info">
                <p class="eyebrow">{{ $producto->marca->nombre }} · {{ $producto->etiquetaGenero() }} · {{ $producto->categoria->nombre }}</p>
                <h1>{{ $producto->nombre }}</h1>
                <p class="muted">{{ $producto->descripcion }}</p>
                <ul class="note-chips" aria-label="Notas olfativas">@foreach ($producto->notas as $nota)<li>{{ $nota->nombre }}</li>@endforeach</ul>
                @if ($errors->any())<div class="form-errors" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <form id="agregar-perfume" method="POST" action="{{ route('tienda.carrito.agregar') }}">
                    @csrf
                    <fieldset>
                        <legend>Elegí tu decant <span class="muted">del frasco original</span></legend>
                        <div class="decant-options">
                            @foreach ($opciones as $opcion)
                                <label @class(['decant-option', 'unavailable' => !$opcion['stock']])>
                                    <input type="radio" name="presentacion_id" data-tamano="{{ $opcion['tamano'] }}" value="{{ $opcion['id'] }}" x-model.number="elegida" @change="ajustar()" @checked($opcion['id'] && $opcion['id'] === ($elegida['id'] ?? null)) @disabled(!$opcion['stock']) required>
                                    <span class="mini-vial" aria-hidden="true"></span>
                                    <span><strong>{{ $opcion['tamano'] }}</strong><small>{{ $opcion['id'] ? \App\Support\Moneda::gs($opcion['precio']) : 'No disponible' }}</small>@if (!$opcion['stock'])<small>Sin stock</small>@endif</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="selected-price" aria-live="polite" x-text="elegida ? precio : 'Sin stock'">{{ $elegida ? \App\Support\Moneda::gs($elegida['precio']) : 'Sin stock' }}</p>
                    <div class="quantity-row">
                        <div class="quantity-control">
                            <button type="button" class="icon-button" aria-label="Restar una unidad" @click="cantidad--; ajustar()" :disabled="cantidad <= 1 || !elegida"><x-phosphor-minus-light aria-hidden="true" /></button>
                            <label class="sr-only" for="cantidad-perfume">Cantidad</label>
                            <input id="cantidad-perfume" name="cantidad" type="number" min="1" :max="opcion?.stock || 1" max="{{ $elegida['stock'] ?? 1 }}" value="1" x-model.number="cantidad" @change="ajustar()" required>
                            <button type="button" class="icon-button" aria-label="Sumar una unidad" @click="cantidad++; ajustar()" :disabled="!elegida || cantidad >= opcion.stock"><x-phosphor-plus-light aria-hidden="true" /></button>
                        </div>
                        <button class="button add-perfume" type="submit" @disabled(!$elegida)>Agregar al carrito</button>
                    </div>
                </form>
                <div class="decant-hint"><strong>¿Por qué un decant?</strong> Un perfume cambia en tu piel. Con un decant lo usás varios días y conocés más fragancias por el precio de una.</div>
            </div>
        </div>
        <section class="featured" aria-labelledby="parecidos-titulo">
            <div class="section-heading"><h2 id="parecidos-titulo">También te puede gustar</h2><a class="text-link" href="{{ route('tienda.marcas.show', $producto->marca) }}">Ver más de {{ $producto->marca->nombre }}<x-phosphor-arrow-right-light aria-hidden="true" /></a></div>
            <div class="product-grid">@foreach ($parecidos as $parecido)<x-tienda.tarjeta-perfume :producto="$parecido['producto']" />@endforeach</div>
        </section>
        <div class="perfume-bottom-bar"><div><span class="muted" x-text="opcion ? 'Decant ' + opcion.tamano : 'Sin stock'">Decant {{ $elegida['tamano'] ?? '' }}</span><strong x-text="elegida ? precio : 'Sin stock'">{{ $elegida ? \App\Support\Moneda::gs($elegida['precio']) : 'Sin stock' }}</strong></div><button class="button" type="submit" form="agregar-perfume" @disabled(!$elegida)>Agregar</button></div>
    </div>
</x-tienda.layout>
