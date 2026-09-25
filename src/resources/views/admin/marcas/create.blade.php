@extends('layouts.admin')

@section('title', 'Nueva Marca')

@section('content')
    <h1>Nueva marca</h1>

    @if ($errors->any())
        <div class="alert-error">
            <ul style="padding-left:1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.marcas.store') }}" style="max-width:400px">
        @csrf
        <input type="text" name="nombre" placeholder="Nombre de la marca" value="{{ old('nombre') }}" required>
        <button type="submit" class="btn">Guardar</button>
    </form>

    <p style="margin-top:1rem"><a href="{{ route('admin.marcas.index') }}">← Volver</a></p>
@endsection