<!DOCTYPE html>
<html>
<head><title>Registro - Mar Fragancia</title></head>
<body>
    <h1>Crear cuenta</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <input type="text" name="nombre" placeholder="Nombre" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Contraseña" required><br>
        <input type="password" name="password_confirmation" placeholder="Confirmar contraseña" required><br>
        <input type="text" name="telefono" placeholder="Teléfono"><br>
        <input type="text" name="direccion" placeholder="Dirección"><br>
        <button type="submit">Registrarme</button>
    </form>
</body>
</html>