<x-tienda.auth-marco modo="ingresar">
    <form class="auth-form" method="POST" action="{{ route('login') }}">
        @csrf
        <x-tienda.auth-campo nombre="email" etiqueta="Email" tipo="email" autocomplete="email" maximo="150" />
        <x-tienda.auth-campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="current-password" />
        <button class="button" type="submit">Ingresar</button>
    </form>
</x-tienda.auth-marco>
