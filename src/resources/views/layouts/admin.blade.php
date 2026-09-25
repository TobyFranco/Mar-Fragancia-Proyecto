<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Admin')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f3f0;
            color: #2b2b2b;
            display: flex;
            min-height: 100vh;
        }
        aside {
            width: 220px;
            background: #1a1a1a;
            color: #fff;
            padding: 1.5rem 1rem;
            flex-shrink: 0;
        }
        aside h2 { font-size: 1.1rem; margin-bottom: 1.5rem; }
        aside nav a {
            display: block;
            color: #ccc;
            text-decoration: none;
            padding: 0.6rem 0.8rem;
            border-radius: 4px;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        aside nav a:hover, aside nav a.active { background: #333; color: #fff; }
        aside form { margin-top: 2rem; }
        aside button {
            width: 100%;
            padding: 0.5rem;
            background: #7a1f1f;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        main { flex: 1; padding: 2rem; }
        .alert-error { color: #b91c1c; background: #fee2e2; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { color: #166534; background: #dcfce7; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; }
        table th, table td { padding: 0.7rem 1rem; text-align: left; border-bottom: 1px solid #eee; }
        table th { background: #efeae4; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #1a1a1a;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        input[type=text], input[type=email], input[type=password] {
            display: block;
            width: 100%;
            padding: 0.6rem;
            margin-bottom: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <aside>
        <h2>Mar Fragancia</h2>
        <nav>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('admin.marcas.index') }}" class="{{ request()->routeIs('admin.marcas.*') ? 'active' : '' }}">Marcas</a>
            <a href="{{ route('admin.categorias.index') }}" class="{{ request()->routeIs('admin.categorias.*') ? 'active' : '' }}">Categorías</a>
            <a href="{{ route('admin.tamanos.index') }}" class="{{ request()->routeIs('admin.tamanos.*') ? 'active' : '' }}">Tamaños</a>
            <a href="{{ route('admin.productos.index') }}" class="{{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">Productos</a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </aside>

    <main>
        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>