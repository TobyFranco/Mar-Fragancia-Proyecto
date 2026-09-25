@extends('layouts.admin')

@section('title', 'Nuevo Tamaño')

@section('content')
    <h1>Nuevo tamaño</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.tamanos.store') }}" style="max-width:400px">
        @csrf
        <input type="text" name="nombre" placeholder="Ej: 5 ml" value="{{ old('nombre') }}" required>
        <input type="text" name="descripcion" placeholder="Descripción (opcional)" value="{{ old('descripcion') }}">
        <button type="submit" class="btn">Guardar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.tamanos.index') }}">← Volver</a></p>
@endsection