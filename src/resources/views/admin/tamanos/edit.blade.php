@extends('layouts.admin')

@section('title', 'Editar Tamaño')

@section('content')
    <h1>Editar tamaño</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tamanos.update', $tamano) }}" style="max-width:400px">
        @csrf
        @method('PUT')
        <input type="text" name="nombre" value="{{ old('nombre', $tamano->nombre) }}" required>
        <input type="text" name="descripcion" value="{{ old('descripcion', $tamano->descripcion) }}">
        <button type="submit" class="btn">Actualizar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.tamanos.index') }}">← Volver</a></p>
@endsection