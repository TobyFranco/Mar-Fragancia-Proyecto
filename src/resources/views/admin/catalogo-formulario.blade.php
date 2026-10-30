<x-admin.layout :title="$titulo">
    <a class="text-link" href="{{ route('admin.'.$tipo.'.index') }}"><x-phosphor-arrow-left-light />Volver a {{ $tipo==='marcas'?'marcas':'categorías' }}</a>
    <header class="admin-top"><h1>{{ $titulo }}</h1></header>
    @php($valor=fn($campo,$defecto='')=>is_scalar(old($campo,$defecto))?old($campo,$defecto):'')
    <form method="POST" enctype="multipart/form-data" action="{{ $registro->exists?route('admin.'.$tipo.'.update',$registro->id):route('admin.'.$tipo.'.store') }}" class="admin-panel admin-form-grid">@csrf @if($registro->exists)@method('PUT')@endif
        <div class="admin-field admin-full"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="100" value="{{ $valor('nombre',$registro->nombre) }}" required></div>
        @if($tipo==='marcas')
            <label class="admin-check"><input type="hidden" name="destacada" value="0"><input type="checkbox" name="destacada" value="1" @checked($valor('destacada',$registro->destacada))>Destacada en el menú (máximo ocho)</label>
            <div class="admin-field"><label for="orden_menu">Orden del menú</label><input id="orden_menu" name="orden_menu" type="number" min="0" value="{{ $valor('orden_menu',$registro->orden_menu) }}"></div>
            <div class="admin-field admin-full"><label for="imagen">Imagen del panel de marca</label>@if($registro->imagen)<img class="admin-brand-preview" src="{{ Storage::disk('public')->url($registro->imagen) }}" alt="Panel de {{ $registro->nombre }}">@endif<input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp"><p class="admin-muted">Máximo 2 MB. Si no elegís otra imagen se conserva la actual.</p></div>
        @endif
        <div><button class="button" type="submit">Guardar cambios</button></div>
    </form>
</x-admin.layout>
