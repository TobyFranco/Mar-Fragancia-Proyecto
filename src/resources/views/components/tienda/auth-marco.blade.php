@props(['modo'])
<x-tienda.layout :title="$modo === 'ingresar' ? 'Ingresar' : 'Crear cuenta'">
    <div class="page-width auth-page">
        <section class="auth-intro" aria-labelledby="auth-presentacion"><a href="{{ route('tienda.inicio') }}"><img src="{{ asset('images/logo-mf.png') }}" alt="Mar Fragancias" width="160" height="64"></a><h2 id="auth-presentacion">Tus datos, listos para tu próximo decant.</h2><p>Guardá tus datos para tu próximo pedido. Para comprar no hace falta cuenta.</p></section>
        <section class="auth-form-area" aria-labelledby="auth-titulo">
            <nav class="auth-tabs" aria-label="Ingresar o crear cuenta"><a href="{{ route('login') }}" @if ($modo === 'ingresar') aria-current="page" @endif>Ingresar</a><a href="{{ route('register') }}" @if ($modo === 'crear') aria-current="page" @endif>Crear cuenta</a></nav>
            <h1 id="auth-titulo" class="sr-only">{{ $modo === 'ingresar' ? 'Ingresar' : 'Crear cuenta' }}</h1>
            {{ $slot }}
            <p class="auth-guest">@if ($modo === 'ingresar')¿Primera vez? <a href="{{ route('register') }}">Creá tu cuenta</a> o @endif<a href="{{ route('tienda.inicio') }}">seguí sin cuenta</a></p>
        </section>
    </div>
</x-tienda.layout>
