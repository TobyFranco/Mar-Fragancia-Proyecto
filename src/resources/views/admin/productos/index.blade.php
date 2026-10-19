<x-admin.layout title="Productos">
    <header class="admin-top"><h1>Productos</h1><a class="button" href="{{ route('admin.productos.create') }}"><x-phosphor-plus-light class="size-4" />Nuevo perfume</a></header>
    <div class="admin-top"><nav class="admin-filters" aria-label="Filtrar productos">
        @foreach(['' => 'Todos', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir', 'sin-stock' => 'Sin stock', 'inactivos' => 'Inactivos'] as $filtro => $etiqueta)
            <a href="{{ route('admin.productos.index', array_filter(['filtro' => $filtro, 'q' => request('q')])) }}" @if((request('filtro') ?? '') === $filtro) aria-current="page" @endif>{{ $etiqueta }}</a>
        @endforeach
    </nav><form class="admin-search" method="GET" action="{{ route('admin.productos.index') }}">
        @if(request('filtro'))<input type="hidden" name="filtro" value="{{ request('filtro') }}">@endif
        <label for="buscar-productos" class="sr-only">Buscar por nombre</label><input id="buscar-productos" name="q" placeholder="Buscar perfume" value="{{ request('q') }}"><button class="icon-button" aria-label="Buscar perfumes"><x-phosphor-magnifying-glass-light /></button>
    </form></div>
    <div class="admin-table-wrap"><table class="admin-products-table"><thead><tr><th>Perfume</th><th>Marca</th><th>Género</th><th>5 ml</th><th>10 ml</th><th>Visible</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
        @forelse($productos as $producto)<tr><td><strong>{{ $producto->nombre }}</strong></td><td>{{ $producto->marca->nombre }}</td><td>{{ $producto->etiquetaGenero() }}</td>
            @foreach(['5 ml', '10 ml'] as $tamano)@php($p = $producto->presentacionPorTamano($tamano))<td @if($p && $p->stock <= 3) class="admin-low" @endif>{{ $p?->stock ?? 'Sin presentación' }}</td>@endforeach
            <td><span class="admin-toggle {{ $producto->activo ? 'is-on' : '' }}" aria-hidden="true"></span><span class="sr-only">{{ $producto->activo ? 'Sí' : 'No' }}</span></td><td><a class="text-link" href="{{ route('admin.productos.edit', $producto->id) }}">Editar<span class="sr-only"> {{ $producto->nombre }}</span></a></td></tr>
        @empty<tr><td colspan="7">No hay perfumes que coincidan con estos filtros.</td></tr>@endforelse
    </tbody></table></div>
    <div class="admin-pagination">{{ $productos->links('admin.paginacion') }}</div>
</x-admin.layout>
