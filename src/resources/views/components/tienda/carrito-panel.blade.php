<dialog class="cart-dialog" aria-labelledby="carrito-panel-titulo" x-data="{
    abrir: {{ Js::from((bool) session('carrito_abierto')) }},
    init() { if (this.abrir) this.$nextTick(() => { this.$el.showModal(); document.documentElement.style.overflow = 'hidden' }) },
    atraparFoco(event) {
        const controles = [...this.$el.querySelectorAll('a[href], button:not(:disabled), input:not([type=hidden])')].filter(el => el.getClientRects().length);
        const primero = controles[0], ultimo = controles.at(-1);
        if (event.shiftKey &amp;&amp; document.activeElement === primero) { event.preventDefault(); ultimo.focus() }
        else if (!event.shiftKey &amp;&amp; document.activeElement === ultimo) { event.preventDefault(); primero.focus() }
    },
    cerrar() { this.$el.close(); document.documentElement.style.overflow = ''; document.querySelector('.cart-link')?.focus() }
}" @keydown.tab="atraparFoco($event)" @cancel.prevent="cerrar()" @click="if ($event.target === $el) cerrar()">
    <div class="cart-drawer">
        <div class="section-heading"><h2 id="carrito-panel-titulo">Tu carrito</h2><button class="icon-button" type="button" aria-label="Cerrar carrito" autofocus @click="cerrar()"><x-phosphor-x-light aria-hidden="true" /></button></div>
        <x-tienda.carrito-contenido :lineas="$lineas" :ultimo="$ultimo" :recomendados="$recomendados" :subtotal="$subtotal" />
    </div>
</dialog>
