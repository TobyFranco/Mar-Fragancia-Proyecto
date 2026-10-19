<x-tienda.layout title="Buscar perfumes">
    <div class="page-width search-page" x-data="busquedaEnVivo">
        <h1 class="sr-only">Buscar perfumes</h1>
        <form class="search-form" x-ref="formulario" method="GET" action="{{ route('tienda.buscar') }}" @submit="cancelar()">
            <label for="buscar-perfume" class="sr-only">Buscá por perfume, marca o nota</label>
            <input id="buscar-perfume" type="search" name="q" value="{{ $consulta }}" placeholder="Buscá por perfume, marca o nota" autocomplete="off" enterkeyhint="search" aria-controls="sugerencias-busqueda" @input="buscar($event.target.value)">
            <button type="submit" class="icon-button" aria-label="Buscar"><x-phosphor-magnifying-glass-light aria-hidden="true" /></button>
            <p class="search-hint">Enter para ver todos</p>
        </form>
        <p class="search-status" role="status" x-text="cargando ? 'Buscando…' : estado"></p>
        <div id="sugerencias-busqueda" :aria-busy="cargando" x-ref="resultados">@include('tienda.resultados-busqueda')</div>
    </div>
</x-tienda.layout>
