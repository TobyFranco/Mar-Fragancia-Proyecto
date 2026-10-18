<x-tienda.auth-marco modo="crear">
    <form class="auth-form" method="POST" action="{{ route('register') }}">
        @csrf
        <x-tienda.auth-campo nombre="nombre" etiqueta="Nombre y apellido" autocomplete="name" maximo="100" />
        <x-tienda.auth-campo nombre="email" etiqueta="Email" tipo="email" autocomplete="email" maximo="150" />
        <x-tienda.auth-campo nombre="password" etiqueta="Contraseña" tipo="password" autocomplete="new-password">Usá al menos 6 caracteres.</x-tienda.auth-campo>
        <x-tienda.auth-campo nombre="password_confirmation" etiqueta="Confirmá la contraseña" tipo="password" autocomplete="new-password" />
        <x-tienda.auth-campo nombre="telefono" etiqueta="Celular (opcional)" tipo="tel" autocomplete="tel-national" maximo="30" :requerido="false" />
        <x-tienda.auth-campo nombre="direccion" etiqueta="Dirección (opcional)" autocomplete="street-address" maximo="255" :requerido="false" />
        <button class="button" type="submit">Crear cuenta</button>
    </form>
</x-tienda.auth-marco>
