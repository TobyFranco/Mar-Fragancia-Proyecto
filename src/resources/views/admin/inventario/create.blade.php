<x-admin.layout title="Registrar movimiento">
    <a class="text-link" href="{{ route('admin.inventario.index') }}"><x-phosphor-arrow-left-light />Volver a inventario</a>
    <header class="admin-top"><h1>Registrar movimiento</h1></header>
    @php($valor=fn($campo,$defecto='')=>is_scalar(old($campo,$defecto))?old($campo,$defecto):'')
    <form method="POST" action="{{ route('admin.inventario.store') }}" class="admin-panel admin-form-grid">@csrf
        <div class="admin-field admin-full"><label for="presentacion_id">Perfume y tamaño</label><select id="presentacion_id" name="presentacion_id" required><option value="">Elegí una presentación</option>@foreach($presentaciones as $p)<option value="{{ $p->id }}" @selected($valor('presentacion_id')==$p->id)>{{ $p->producto->nombre }} · {{ $p->tamano->nombre }} · Stock {{ $p->stock }}</option>@endforeach</select></div>
        <div class="admin-field"><label for="tipo">Tipo</label><select id="tipo" name="tipo"><option value="entrada" @selected($valor('tipo')==='entrada')>Entrada</option><option value="salida" @selected($valor('tipo')==='salida')>Salida</option></select></div>
        <div class="admin-field"><label for="cantidad">Cantidad</label><input id="cantidad" type="number" name="cantidad" min="1" step="1" value="{{ $valor('cantidad',1) }}" required></div>
        <div class="admin-field admin-full"><label for="motivo">Motivo</label><input id="motivo" name="motivo" maxlength="255" value="{{ $valor('motivo') }}" required></div>
        <div><button type="submit" class="button">Registrar movimiento</button></div>
    </form>
</x-admin.layout>
