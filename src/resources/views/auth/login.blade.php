<!DOCTYPE html>
<html>
<head><title>Login - Mar Fragancia</title></head>
<body>
    <h1>Iniciar sesión</h1>

    @if ($errors->any())
        <div style="color:red">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required><br>
        <input type="password" name="password" placeholder="Contraseña" required><br>
        <button type="submit">Entrar</button>
    </form>

    <p>¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
</body>
</html>