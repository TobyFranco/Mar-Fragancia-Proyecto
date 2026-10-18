@props(['title' => 'Perfumes de lujo en decant'])
<!doctype html>
<html lang="es-PY">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#192438">
    <title>{{ $title }} · Mar Fragancias</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-navy antialiased">
    <a href="#contenido" class="skip-link">Saltar al contenido</a>
    <x-tienda.encabezado />
    <main id="contenido" tabindex="-1">{{ $slot }}</main>
    <x-tienda.pie />
    @if (!request()->routeIs('tienda.carrito'))<x-tienda.carrito-panel />@endif
</body>
</html>
