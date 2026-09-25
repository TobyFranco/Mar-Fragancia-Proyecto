@extends('layouts.admin')

@section('title', 'Nueva Categoría')

@section('content')
    <h1>Nueva categoría</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categorias.store') }}" style="max-width:400px">
        @csrf
        <input type="text" name="nombre" placeholder="Nombre de la categoría" value="{{ old('nombre') }}" required>
        <button type="submit" class="btn">Guardar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.categorias.index') }}">← Volver</a></p>
@endsection