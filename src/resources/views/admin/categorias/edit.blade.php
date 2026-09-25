@extends('layouts.admin')

@section('title', 'Editar Categoría')

@section('content')
    <h1>Editar categoría</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}" style="max-width:400px">
        @csrf
        @method('PUT')
        <input type="text" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" required>
        <button type="submit" class="btn">Actualizar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.categorias.index') }}">← Volver</a></p>
@endsection