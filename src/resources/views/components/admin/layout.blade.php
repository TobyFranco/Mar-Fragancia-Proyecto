@props(['title' => 'Administración'])
<!doctype html>
<html lang="es-PY">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · Mar Fragancias</title>
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-navy antialiased admin-body">
<a href="#contenido" class="skip-link">Saltar al contenido</a>
<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="Administración">
        <a class="admin-logo" href="{{ route('admin.dashboard') }}"><img src="{{ asset('images/logo-mf.png') }}" alt="Mar Fragancias"></a>
        <nav>
            <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><x-phosphor-chart-bar-light />Dashboard</a>
            <h2>VENTAS</h2>
            @if(Route::has('admin.pedidos.index'))
                <a href="{{ route('admin.pedidos.index') }}" @if(request()->routeIs('admin.pedidos.*')) aria-current="page" @endif><x-phosphor-package-light />Pedidos <span class="admin-count" aria-label="{{ $pendientesMenu }} pedidos pendientes">{{ $pendientesMenu }}</span></a>
            @else
                <span class="admin-nav-pending"><x-phosphor-package-light />Pedidos <span class="admin-count" aria-label="{{ $pendientesMenu }} pedidos pendientes">{{ $pendientesMenu }}</span></span>
            @endif
            <h2>CATÁLOGO</h2>
            @foreach (['productos' => ['Productos', 'drop'], 'marcas' => ['Marcas', 'tag'], 'categorias' => ['Categorías', 'squares-four'], 'tamanos' => ['Tamaños', 'ruler']] as $recurso => [$etiqueta, $icono])
                <a href="{{ route('admin.'.$recurso.'.index') }}" @if(request()->routeIs('admin.'.$recurso.'.*')) aria-current="page" @endif><x-dynamic-component :component="'phosphor-'.$icono.'-light'" />{{ $etiqueta }}</a>
            @endforeach
            <h2>STOCK</h2>
            @if(Route::has('admin.inventario.index'))
                <a href="{{ route('admin.inventario.index') }}"><x-phosphor-stack-light />Inventario <span class="admin-count" aria-label="{{ $criticasMenu }} presentaciones críticas">{{ $criticasMenu }}</span></a>
            @else
                <span class="admin-nav-pending"><x-phosphor-stack-light />Inventario <span class="admin-count" aria-label="{{ $criticasMenu }} presentaciones críticas">{{ $criticasMenu }}</span></span>
            @endif
        </nav>
        <a class="admin-store-link" href="{{ route('tienda.inicio') }}"><x-phosphor-arrow-square-out-light />Ver tienda</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="admin-logout" type="submit"><x-phosphor-sign-out-light />Cerrar sesión</button></form>
    </aside>
    <main id="contenido" class="admin-main" tabindex="-1">
        @if(session('success'))<div class="admin-notice" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="admin-errors" role="alert"><p>Revisá los datos para continuar.</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot }}
    </main>
</div>
</body>
</html>
