@extends('layouts.admin')

@section('title', 'Nuevo Producto')

@section('content')
    <h1>Nuevo producto</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.productos.store') }}" enctype="multipart/form-data" style="max-width:500px">
        @csrf

        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre') }}" required>

        <label>Marca</label>
        <select name="marca_id" required style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">
            <option value="">-- Selecciona --</option>
            @foreach ($marcas as $marca)
                <option value="{{ $marca->id }}" {{ old('marca_id') == $marca->id ? 'selected' : '' }}>{{ $marca->nombre }}</option>
            @endforeach
        </select>

        <label>Categoría</label>
        <select name="categoria_id" required style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">
            <option value="">-- Selecciona --</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
            @endforeach
        </select>

        <label>Descripción</label>
        <textarea name="descripcion" rows="3" style="display:block;width:100%;padding:0.6rem;margin-bottom:0.8rem;border:1px solid #ccc;border-radius:4px">{{ old('descripcion') }}</textarea>

        <label>Imagen</label>
        <input type="file" name="imagen" accept="image/*" style="margin-bottom:0.8rem">

        <label><input type="checkbox" name="activo" checked> Producto activo</label>

        <h3 style="margin-top:1.5rem">Presentaciones y precios</h3>
        @foreach ($tamanos as $tamano)
            <fieldset style="margin-bottom:1rem;padding:0.8rem;border:1px solid #ddd;border-radius:4px">
                <legend>{{ $tamano->nombre }}</legend>
                <label>Precio</label>
                <input type="number" step="0.01" name="presentaciones[{{ $tamano->id }}][precio]" placeholder="Precio en Gs.">
                <label>Stock</label>
                <input type="number" name="presentaciones[{{ $tamano->id }}][stock]" placeholder="Cantidad disponible">
            </fieldset>
        @endforeach

        <button type="submit" class="btn">Guardar producto</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.productos.index') }}">← Volver</a></p>
@endsection