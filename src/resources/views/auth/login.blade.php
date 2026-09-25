@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="card">
        <h1>Iniciar sesión</h1>

        @if ($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn">Entrar</button>
        </form>

        <p style="margin-top:1rem">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
    </div>
@endsection