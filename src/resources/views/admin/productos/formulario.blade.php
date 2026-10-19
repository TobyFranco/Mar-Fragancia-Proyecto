@php
    $editando = $producto->exists;
    $valor = fn ($campo, $defecto = '') => is_scalar(old($campo, $defecto)) ? old($campo, $defecto) : '';
    $seleccionadas = old('notas', $producto->notas->pluck('nombre')->all());
    $seleccionadas = is_array($seleccionadas) ? array_filter($seleccionadas, 'is_string') : [];
@endphp
<a class="text-link" href="{{ route('admin.productos.index') }}"><x-phosphor-arrow-left-light />Volver a productos</a>
<form method="POST" enctype="multipart/form-data" action="{{ $editando ? route('admin.productos.update', $producto->id) : route('admin.productos.store') }}" class="admin-product-form">
    @csrf
    @if($editando)@method('PUT')@endif
    <header class="admin-top"><h1>{{ $editando ? $producto->nombre : 'Nuevo perfume' }}</h1><div class="admin-actions">
        @if($editando && $producto->activo)<a class="button button-outline" href="{{ route('tienda.perfume', $producto->slug) }}">Ver en la tienda</a>@endif
        <button class="button" type="submit">Guardar cambios</button>
    </div></header>
    <div class="admin-product-fields">
        <div class="admin-form-grid">
            <div class="admin-field"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ $valor('nombre', $producto->nombre) }}" required maxlength="150"></div>
            <div class="admin-field"><label for="marca_id">Marca</label><select id="marca_id" name="marca_id" required><option value="">Elegí una marca</option>@foreach($marcas as $marca)<option value="{{ $marca->id }}" @selected((string)$valor('marca_id', $producto->marca_id) === (string)$marca->id)>{{ $marca->nombre }}</option>@endforeach</select></div>
            <div class="admin-field"><label for="categoria_id">Familia de aroma (categoría)</label><select id="categoria_id" name="categoria_id" required><option value="">Elegí una familia</option>@foreach($categorias as $categoria)<option value="{{ $categoria->id }}" @selected((string)$valor('categoria_id', $producto->categoria_id) === (string)$categoria->id)>{{ $categoria->nombre }}</option>@endforeach</select></div>
            <div class="admin-field"><label for="genero">Género</label><select id="genero" name="genero" required>@foreach(['mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir'] as $genero => $etiqueta)<option value="{{ $genero }}" @selected($valor('genero', $producto->genero) === $genero)>{{ $etiqueta }}</option>@endforeach</select></div>
            <div class="admin-field admin-full"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" rows="3">{{ $valor('descripcion', $producto->descripcion) }}</textarea></div>
            <div class="admin-field"><label for="notas">Notas (podés elegir varias)</label><select id="notas" name="notas[]" multiple size="5">@foreach($notas as $nota)<option value="{{ $nota->nombre }}" @selected(in_array($nota->nombre, $seleccionadas, true))>{{ $nota->nombre }}</option>@endforeach</select></div>
            <div class="admin-field"><label for="notas_nuevas">Crear notas nuevas</label><textarea id="notas_nuevas" name="notas_nuevas" rows="3" aria-describedby="ayuda-notas">{{ $valor('notas_nuevas') }}</textarea><p class="admin-muted" id="ayuda-notas">Separá las notas con comas o saltos de línea. Las existentes se reutilizan.</p></div>
            <label class="admin-check"><input type="hidden" name="activo" value="0"><input type="checkbox" name="activo" value="1" @checked($valor('activo', $editando ? $producto->activo : true))>Visible en la tienda</label>
            <label class="admin-check"><input type="hidden" name="destacado" value="0"><input type="checkbox" name="destacado" value="1" @checked($valor('destacado', $producto->destacado))>Destacado</label>
        </div>
        <div class="admin-photo">
            @if($producto->imagen)<img src="{{ Storage::disk('public')->url($producto->imagen) }}" alt="Foto de {{ $producto->nombre }}">@else<x-phosphor-image-light class="size-10" />@endif
            <label for="imagen">Foto del perfume</label><input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"><p class="admin-muted">JPG, PNG o WebP. Máximo 2 MB.</p>
        </div>
    </div>
    <section class="admin-panel"><h2>Presentaciones</h2><div class="admin-table-wrap"><table class="admin-presentations-table"><thead><tr><th>Tamaño</th><th>Tipo</th><th>Precio (₲)</th><th>{{ $editando ? 'Stock' : 'Stock inicial' }}</th><th>Activo</th></tr></thead><tbody>
        @foreach($tamanos as $tamano)
            @php($presentacion = $producto->presentaciones->firstWhere('tamano_id', $tamano->id))
            <tr><td>{{ $tamano->nombre }}</td><td>Decant</td><td><label class="sr-only" for="precio-{{ $tamano->id }}">Precio {{ $tamano->nombre }}</label><input id="precio-{{ $tamano->id }}" type="number" min="0" max="99999999.99" step="0.01" name="presentaciones[{{ $tamano->id }}][precio]" value="{{ $valor('presentaciones.'.$tamano->id.'.precio', $presentacion?->precio) }}"></td>
            <td>@if($editando){{ $presentacion?->stock ?? 0 }}@else<label class="sr-only" for="stock-{{ $tamano->id }}">Stock inicial {{ $tamano->nombre }}</label><input id="stock-{{ $tamano->id }}" type="number" min="0" step="1" name="presentaciones[{{ $tamano->id }}][stock_inicial]" value="{{ $valor('presentaciones.'.$tamano->id.'.stock_inicial', 0) }}">@endif</td>
            <td><input type="hidden" name="presentaciones[{{ $tamano->id }}][activo]" value="0"><label class="admin-check"><input type="checkbox" name="presentaciones[{{ $tamano->id }}][activo]" value="1" @checked($valor('presentaciones.'.$tamano->id.'.activo', $presentacion?->activo ?? true))><span class="sr-only">Activo {{ $tamano->nombre }}</span></label></td></tr>
        @endforeach
    </tbody></table></div><p class="admin-muted admin-stock-help">{{ $editando ? 'El stock se cambia desde Inventario para que cada entrada y salida quede registrada.' : 'El stock inicial se registra como una entrada en Inventario.' }}</p></section>
</form>
@if($editando)<form method="POST" action="{{ route('admin.productos.destroy', $producto->id) }}" class="admin-delete">@csrf @method('DELETE')<button class="button button-outline" type="submit">Eliminar perfume</button><p class="admin-muted">Si tiene pedidos o movimientos de inventario, ocultalo desmarcando Visible en la tienda.</p></form>@endif
