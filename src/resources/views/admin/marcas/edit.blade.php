@extends('layouts.admin')

@section('title', 'Editar Marca')

@section('content')
    <h1>Editar marca</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.marcas.update', $marca) }}" style="max-width:400px">
        @csrf
        @method('PUT')
        <input type="text" name="nombre" value="{{ old('nombre', $marca->nombre) }}" required>
        <button type="submit" class="btn">Actualizar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.marcas.index') }}">← Volver</a></p>
@endsection