<x-tienda.layout title="Mi cuenta">
    <section class="page-width account-page">
        <h1>Hola, {{ $usuario->nombre }}</h1>
        <p class="muted">Desde acá podés consultar tus datos y pedidos.</p>
        <div class="account-content">
            <div>
                <h2>Mi cuenta</h2>
                <dl class="account-details">
                    <div><dt>Nombre</dt><dd>{{ $usuario->nombre }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $usuario->email }}</dd></div>
                    <div><dt>Teléfono</dt><dd>{{ $usuario->cliente?->telefono ?: 'Sin completar' }}</dd></div>
                    <div><dt>Dirección</dt><dd>{{ $usuario->cliente?->direccion ?: 'Sin completar' }}</dd></div>
                </dl>
            </div>
            <nav class="account-nav" aria-label="Mi cuenta">
                <a class="button button-outline" href="{{ route('cliente.datos') }}">Mis datos</a>
                <a class="button button-outline" href="{{ route('cliente.pedidos') }}">Mis pedidos</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-link" type="submit">Salir</button></form>
            </nav>
        </div>
    </section>
</x-tienda.layout>
