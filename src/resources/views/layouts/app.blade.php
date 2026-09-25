<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mar Fragancia')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f5f3f0;
            color: #2b2b2b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        header {
            background: #1a1a1a;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header a { color: #fff; text-decoration: none; font-weight: 600; }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 400px;
        }
        .alert-error { color: #b91c1c; background: #fee2e2; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-success { color: #166534; background: #dcfce7; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; font-size: 0.9rem; }
        footer {
            text-align: center;
            padding: 1rem;
            color: #888;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <header>
        <a href="/">Mar Fragancia</a>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        &copy; {{ date('Y') }} Mar Fragancia — Perfumes y decants
    </footer>
</body>
</html>