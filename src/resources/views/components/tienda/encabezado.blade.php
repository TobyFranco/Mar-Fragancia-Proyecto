<header x-data="{ abierta: null, movil: false, disparador: null, cerrar() { this.disparador?.focus(); this.abierta = null; this.movil = false } }"
    @keydown.escape.prevent.stop="cerrar()" @click.outside="abierta = null; movil = false" class="site-header">
    <div class="topbar">Envíos a todo Paraguay · Finalizás tu compra por WhatsApp</div>
    <div class="header-row page-width">
        <div class="header-start">
            <button type="button" class="icon-button menu-button" aria-label="Abrir menú de marcas" aria-controls="menu-marcas" :aria-expanded="movil" @click="movil = !movil; abierta = null; disparador = $el">
                <x-phosphor-list-light aria-hidden="true" />
            </button>
            <a href="{{ url('/buscar') }}" class="icon-button" aria-label="Buscar perfumes"><x-phosphor-magnifying-glass-light aria-hidden="true" /></a>
        </div>
        <a href="{{ route('tienda.inicio') }}" class="logo-link" aria-label="Mar Fragancias, inicio"><img src="{{ asset('images/logo-mf.png') }}" alt="Mar Fragancias" width="140" height="56"></a>
        <div class="header-icons">
            <a href="{{ url('/buscar') }}" class="icon-button desktop-search" aria-label="Buscar perfumes"><x-phosphor-magnifying-glass-light aria-hidden="true" /></a>
            <a href="{{ route('login') }}" class="icon-button account-link" aria-label="Mi cuenta"><x-phosphor-user-light aria-hidden="true" /></a>
            <a href="{{ url('/carrito') }}" class="icon-button cart-link" aria-label="Carrito, {{ $unidadesCarrito }} unidades"><x-phosphor-handbag-light aria-hidden="true" /><span class="cart-count">{{ $unidadesCarrito }}</span></a>
        </div>
    </div>
    <nav id="menu-marcas" aria-label="Marcas y tienda" class="brand-nav" :class="{ 'is-open': movil }"
        @mouseleave="if (window.matchMedia('(hover: hover)').matches) abierta = null"
        @focusout="if (!$el.contains($event.relatedTarget)) abierta = null">
        <div class="nav-row page-width">
            @foreach ($marcasMenu as $marca)
                <a href="{{ url('/marcas/'.$marca->slug) }}" aria-controls="panel-{{ $marca->slug }}" :aria-expanded="abierta === {{ $marca->id }}"
                    @mouseenter="if (window.matchMedia('(hover: hover)').matches) abierta = {{ $marca->id }}"
                    @focus="if (!movil) { abierta = {{ $marca->id }}; disparador = $el }"
                    class="brand-link">{{ $marca->nombre }}</a>
            @endforeach
            <a class="all-brands" href="{{ url('/marcas') }}">Todas las marcas</a>
            <div class="info-links"><a href="{{ url('/termometro-olfativo') }}">Termómetro olfativo</a><a href="{{ url('/como-funciona') }}">Cómo funciona</a></div>
        </div>
        @foreach ($marcasMenu as $marca)
            <x-tienda.panel-marca :marca="$marca" />
        @endforeach
    </nav>
</header>
