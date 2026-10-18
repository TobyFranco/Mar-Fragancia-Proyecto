<x-tienda.layout title="Perfumes">
    <div class="page-width breadcrumbs"><a href="{{ route('tienda.inicio') }}">Inicio</a> / Perfumes</div>
    <div class="page-width catalog-heading">
        <div><h1>{{ match ($genero) { 'mujer' => 'Perfumes para mujer', 'hombre' => 'Perfumes para hombre', 'unisex' => 'Perfumes para compartir', default => 'Perfumes' } }}</h1><p class="muted">{{ $productos->count() }} {{ $productos->count() === 1 ? 'perfume' : 'perfumes' }} · todos disponibles en decant</p></div>
        <label class="sort-control">Ordenar
            <select name="orden" form="filtros-catalogo" onchange="this.form.requestSubmit()">
                @foreach (['probados' => 'Más probados', 'precio_asc' => 'Menor precio', 'precio_desc' => 'Mayor precio', 'nombre' => 'Nombre'] as $valor => $etiqueta)<option value="{{ $valor }}" @selected($orden === $valor)>{{ $etiqueta }}</option>@endforeach
            </select>
        </label>
    </div>
    <div class="page-width catalog-body">
        <details class="catalog-filters" x-data="{ abierto: window.matchMedia('(min-width: 768px)').matches }" :open="abierto" @toggle="abierto = $el.open">
            <summary>Filtrar perfumes <x-phosphor-caret-down-light aria-hidden="true" /></summary>
            <form id="filtros-catalogo" method="GET" action="{{ route('tienda.catalogo') }}">
                <fieldset><legend>Género</legend><div class="filter-genders">
                    @foreach (['' => 'Todos', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir'] as $valor => $etiqueta)<label><input type="radio" name="genero" value="{{ $valor }}" @checked(($genero ?? '') === $valor)>{{ $etiqueta }}</label>@endforeach
                </div></fieldset>
                <fieldset><legend>Marca</legend>
                    @foreach ($marcas as $marca)<label class="filter-option"><input type="checkbox" name="marca[]" value="{{ $marca->slug }}" @checked(in_array($marca->slug, $marcasSeleccionadas, true))><span>{{ $marca->nombre }}</span><span class="filter-count">{{ $marca->productos_count }}</span></label>@endforeach
                </fieldset>
                <fieldset><legend>Familia de aroma</legend>
                    @foreach ($familias as $familia)<label class="filter-option"><input type="checkbox" name="familia[]" value="{{ $familia->id }}" @checked(in_array((string) $familia->id, array_map('strval', $familiasSeleccionadas), true))>{{ $familia->nombre }}</label>@endforeach
                </fieldset>
                <fieldset><legend>Tamaño</legend>
                    <label class="filter-option"><input type="radio" name="tamano" value="" @checked(!$tamano)>Todos</label>
                    @foreach (['5', '10'] as $valor)<label class="filter-option"><input type="radio" name="tamano" value="{{ $valor }}" @checked($tamano === $valor)>Decant {{ $valor }} ml</label>@endforeach
                </fieldset>
                <button type="submit" class="button">Aplicar filtros</button>
                <a class="text-link" href="{{ route('tienda.catalogo') }}">Limpiar filtros</a>
            </form>
        </details>
        <section id="resultados-perfumes" aria-label="Resultados del catálogo">
            @if ($marcasSeleccionadas || $familiasSeleccionadas || $genero || $tamano)
                <div class="active-filters">
                    @foreach ($marcas->whereIn('slug', $marcasSeleccionadas) as $marca)<span>{{ $marca->nombre }}</span>@endforeach
                    @foreach ($familias->whereIn('id', $familiasSeleccionadas) as $familia)<span>{{ $familia->nombre }}</span>@endforeach
                    @if ($tamano)<span>Decant {{ $tamano }} ml</span>@endif
                    <a href="{{ route('tienda.catalogo') }}" class="text-link">Limpiar filtros <x-phosphor-x-light aria-hidden="true" /></a>
                </div>
            @endif
            <div class="product-grid catalog-grid">@forelse ($productos as $producto)<x-tienda.tarjeta-perfume :producto="$producto" />@empty<div class="empty-copy"><h2>No hay perfumes con estos filtros</h2><a class="text-link" href="{{ route('tienda.catalogo') }}">Ver todos los perfumes</a></div>@endforelse</div>
        </section>
    </div>
</x-tienda.layout>
