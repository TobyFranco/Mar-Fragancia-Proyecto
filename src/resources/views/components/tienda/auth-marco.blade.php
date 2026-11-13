@props(['modo'])
<x-tienda.layout :title="match ($modo) { 'admin' => 'Panel de administración', 'ingresar' => 'Ingresar', default => 'Crear cuenta' }">
    <div class="page-width auth-page">
        <section class="auth-intro" aria-labelledby="auth-presentacion"><a href="{{ route('tienda.inicio') }}"><img src="{{ asset('images/logo-mf.png') }}" alt="Mar Fragancias" width="160" height="64"></a>@if ($modo === 'admin')<h2 id="auth-presentacion">Panel de administración</h2><p>Gestioná catálogo, pedidos e inventario de la tienda.</p>@else<h2 id="auth-presentacion">Tus datos, listos para tu próximo decant.</h2><p>Guardá tus datos para tu próximo pedido. Para comprar no hace falta cuenta.</p>@endif</section>
        <section class="auth-form-area" aria-labelledby="auth-titulo">
            @if ($modo === 'admin')
                <nav class="auth-tabs" aria-label="Panel de administración"><a href="{{ route('admin.login') }}" aria-current="page">Ingresar</a></nav>
            @else
                <nav class="auth-tabs" aria-label="Ingresar o crear cuenta"><a href="{{ route('login') }}" @if ($modo === 'ingresar') aria-current="page" @endif>Ingresar</a><a href="{{ route('register') }}" @if ($modo === 'crear') aria-current="page" @endif>Crear cuenta</a></nav>
            @endif
            <h1 id="auth-titulo" class="sr-only">{{ match ($modo) { 'admin' => 'Ingresar al panel de administración', 'ingresar' => 'Ingresar', default => 'Crear cuenta' } }}</h1>
            {{ $slot }}
            @unless ($modo === 'admin')
                <p class="auth-guest">@if ($modo === 'ingresar')¿Primera vez? <a href="{{ route('register') }}">Creá tu cuenta</a> o @endif<a href="{{ route('tienda.inicio') }}">seguí sin cuenta</a></p>
            @endunless
        </section>
    </div>
</x-tienda.layout>
