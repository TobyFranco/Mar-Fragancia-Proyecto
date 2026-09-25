@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
    <div class="card">
        <h1>Crear cuenta</h1>

        @if ($errors->any())
            <div class="alert-error">
                <ul style="padding-left:1rem">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="password" name="password_confirmation" placeholder="Confirmar contraseña" required>
            <input type="text" name="telefono" placeholder="Teléfono">
            <input type="text" name="direccion" placeholder="Dirección">
            <button type="submit" class="btn">Registrarme</button>
        </form>
    </div>
@endsection