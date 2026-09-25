@extends('layouts.admin')

@section('title', 'Editar Producto')

@section('content')
    <h1>Editar producto</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.productos.update', $producto) }}" enctype="multipart/form-data" style="max-width:500px">
        @csrf
        @method('PUT')

        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required>

        <label>Marca</label>
        <select name="marca_id" required style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">
            @foreach ($marcas as $marca)
                <option value="{{ $marca->id }}" {{ old('marca_id', $producto->marca_id) == $marca->id ? 'selected' : '' }}>{{ $marca->nombre }}</option>
            @endforeach
        </select>

        <label>Categoría</label>
        <select name="categoria_id" required style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" {{ old('categoria_id', $producto->categoria_id) == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
            @endforeach
        </select>

        <label>Descripción</label>
        <textarea name="descripcion" rows="3" style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">{{ old('descripcion', $producto->descripcion) }}</textarea>

        @if ($producto->imagen)
            <p><img src="{{ asset('storage/' . $producto->imagen) }}" width="100"></p>
        @endif
        <label>Cambiar imagen (opcional)</label>
        <input type="file" name="imagen" accept="image/*" style="margin-bottom:0.8rem">

        <label><input type="checkbox" name="activo" {{ old('activo', $producto->activo) ? 'checked' : '' }}> Producto activo</label>

        <h3 style="margin-top:1.5rem">Presentaciones y precios</h3>
        @foreach ($tamanos as $tamano)
            @php
                $pres = $producto->presentaciones->firstWhere('tamano_id', $tamano->id);
            @endphp
            <fieldset style="margin-bottom:1rem;padding:0.8rem;border:1px solid #ddd;border-radius:4px">
                <legend>{{ $tamano->nombre }}</legend>
                <label>Precio</label>
                <input type="number" step="0.01" name="presentaciones[{{ $tamano->id }}][precio]" value="{{ $pres->precio ?? '' }}" placeholder="Precio en Gs.">
                <label>Stock</label>
                <input type="number" name="presentaciones[{{ $tamano->id }}][stock]" value="{{ $pres->stock ?? '' }}" placeholder="Cantidad disponible">
            </fieldset>
        @endforeach

        <button type="submit" class="btn">Actualizar producto</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.productos.index') }}">← Volver</a></p>
@endsection