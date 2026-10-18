@props(['lineas', 'ultimo', 'recomendados', 'subtotal'])
@if (session('carrito_confirmacion'))<p class="cart-added" role="status"><x-phosphor-check-circle-light aria-hidden="true" /><span>{{ session('carrito_confirmacion') }}</span></p>@endif
@if (session('aviso_carrito'))<p class="form-errors" role="status">{{ session('aviso_carrito') }}</p>@endif
@if ($errors->has('carrito') || $errors->has('cantidad'))<div class="form-errors" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if ($lineas->isEmpty())
    <div class="search-empty"><x-phosphor-handbag-light aria-hidden="true" /><h2>Tu carrito está vacío</h2><p>Empezá por un decant de 5 ml: la forma más fácil de conocer una fragancia nueva.</p><a href="{{ route('tienda.inicio') }}#mas-probados" class="button">Ver los más probados</a></div>
@else
    <div class="cart-lines">
        @foreach ($lineas as $linea)
            @php($p = $linea['presentacion'])
            <article class="cart-line">
                <a class="search-thumbnail" href="{{ route('tienda.perfume', $p->producto) }}" aria-label="Ver {{ $p->producto->nombre }}">@if ($p->producto->imagen)<img src="{{ asset('storage/'.$p->producto->imagen) }}" alt="" width="44" height="60">@else<span class="bottle" aria-hidden="true"></span>@endif</a>
                <div><h3>{{ $p->producto->nombre }} · {{ $p->producto->marca->nombre }}</h3><p class="muted">Decant {{ $p->tamano->nombre }}</p>
                    @if (!$p->disponible() || !$p->producto->activo)<p class="muted">Ya no está disponible. Quitá este decant para continuar.</p>@endif
                    <form method="POST" action="{{ route('tienda.carrito.actualizar', $p->id) }}" class="cart-quantity" x-data="{ cantidad: {{ $linea['cantidad'] }} }">
                        @csrf @method('PATCH')
                        <div class="quantity-control"><button type="submit" class="icon-button" aria-label="Restar una unidad de {{ $p->producto->nombre }}" @click.prevent="cantidad = Math.max(0, cantidad - 1); $nextTick(() => $el.form.requestSubmit())"><x-phosphor-minus-light aria-hidden="true" /></button><label class="sr-only" for="cantidad-carrito-{{ $p->id }}">Cantidad de {{ $p->producto->nombre }}</label><input id="cantidad-carrito-{{ $p->id }}" name="cantidad" type="number" min="0" max="{{ $p->stock }}" value="{{ $linea['cantidad'] }}" x-model.number="cantidad" required><button type="submit" class="icon-button" aria-label="Sumar una unidad de {{ $p->producto->nombre }}" @click.prevent="cantidad = Math.min({{ $p->stock }}, cantidad + 1); $nextTick(() => $el.form.requestSubmit())" @disabled(!$p->disponible())><x-phosphor-plus-light aria-hidden="true" /></button></div>
                        <button class="text-link" type="submit">Actualizar</button>
                    </form>
                </div>
                <div class="cart-line-price"><strong>{{ \App\Support\Moneda::gs($linea['subtotal']) }}</strong><form method="POST" action="{{ route('tienda.carrito.quitar', $p->id) }}">@csrf @method('DELETE')<button class="text-link" type="submit">Quitar</button></form></div>
            </article>
        @endforeach
    </div>
    @if ($ultimo && $recomendados->isNotEmpty())
        <section class="cart-recommendations" aria-label="Recomendaciones por notas"><h3>Con notas parecidas a {{ $ultimo->nombre }}</h3><p class="muted">Si te gusta {{ $ultimo->nombre }}, estos comparten su base:</p>
            @foreach ($recomendados as $fila)
                @php($cinco = $fila['producto']->presentacionPorTamano('5 ml'))
                <article class="cart-recommendation"><div><p class="match-percent">{{ $fila['porcentaje'] }}% parecido</p><a href="{{ route('tienda.perfume', $fila['producto']) }}"><h4>{{ $fila['producto']->nombre }}</h4></a><p class="muted">{{ $fila['producto']->marca->nombre }}@if ($cinco) · 5 ml {{ \App\Support\Moneda::gs((float) $cinco->precio) }}@endif</p></div>
                    @if ($cinco && $cinco->disponible())<form method="POST" action="{{ route('tienda.carrito.agregar') }}">@csrf<input type="hidden" name="presentacion_id" value="{{ $cinco->id }}"><input type="hidden" name="cantidad" value="1"><button class="button button-outline" type="submit" aria-label="Agregar 5 ml de {{ $fila['producto']->nombre }}"><x-phosphor-plus-light aria-hidden="true" />5 ml</button></form>@endif
                    <ul class="note-chips" aria-label="Notas compartidas">@foreach ($fila['compartidas'] as $nota)<li>{{ $nota }}</li>@endforeach</ul>
                </article>
            @endforeach
        </section>
    @endif
    <div class="cart-totals"><div><span>Subtotal ({{ $lineas->sum('cantidad') }} {{ $lineas->sum('cantidad') === 1 ? 'unidad' : 'unidades' }})</span><strong>{{ \App\Support\Moneda::gs($subtotal) }}</strong></div><p class="muted">El envío se calcula en el siguiente paso.</p><div class="cart-total"><span>Total</span><strong>{{ \App\Support\Moneda::gs($subtotal) }}</strong></div><a class="button" href="{{ url('/checkout') }}">Continuar con mis datos</a><a class="text-link" href="{{ route('tienda.catalogo') }}">Seguir comprando</a></div>
@endif
