<x-admin.layout :title="$titulo">
    <header class="admin-top"><h1>{{ $titulo }}</h1>@if($tipo!=='tamanos')<a class="button" href="{{ route('admin.'.$tipo.'.create') }}"><x-phosphor-plus-light class="size-4" />{{ $tipo==='marcas'?'Nueva marca':'Nueva categoría' }}</a>@endif</header>
    @if($tipo==='tamanos')<p class="admin-muted mb-4">Solo decants de 5 ml y 10 ml. Esta lista es de solo lectura.</p>@endif
    <div class="admin-table-wrap"><table><thead><tr><th>{{ $tipo==='marcas'?'Marca':($tipo==='categorias'?'Categoría':'Tamaño') }}</th><th>Perfumes</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
        @forelse($registros as $registro)<tr><td><strong>{{ $registro->nombre }}</strong>@if($tipo==='marcas'&&$registro->destacada)<span class="admin-muted"> · Destacada</span>@endif</td><td>{{ $registro->productos_count }}</td><td>
            @if($tipo!=='tamanos')<div class="admin-actions"><a class="text-link" href="{{ route('admin.'.$tipo.'.edit',$registro->id) }}">Editar<span class="sr-only"> {{ $registro->nombre }}</span></a>@if($registro->productos_count===0)<form method="POST" action="{{ route('admin.'.$tipo.'.destroy',$registro->id) }}">@csrf @method('DELETE')<button type="submit" class="text-link">Eliminar<span class="sr-only"> {{ $registro->nombre }}</span></button></form>@endif</div>@endif
        </td></tr>@empty<tr><td colspan="3">Todavía no hay registros.</td></tr>@endforelse
    </tbody></table></div><div class="admin-pagination">{{ $registros->links('admin.paginacion') }}</div>
</x-admin.layout>
