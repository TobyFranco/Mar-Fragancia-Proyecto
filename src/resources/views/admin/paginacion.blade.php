@if($paginator->hasPages())
<nav class="admin-pager" aria-label="Paginación">
    <p class="admin-muted">Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }} resultados</p>
    <div class="admin-page-links">
        @if($paginator->onFirstPage())<span class="admin-page-disabled" aria-disabled="true"><x-phosphor-caret-left-light /><span class="sr-only">Anterior</span></span>
        @else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior"><x-phosphor-caret-left-light /></a>@endif
        @foreach($elements as $element)
            @if(is_string($element))<span>{{ $element }}</span>@else
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())<span aria-current="page" aria-label="Página {{ $page }}">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Ir a página {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente"><x-phosphor-caret-right-light /></a>
        @else<span class="admin-page-disabled" aria-disabled="true"><x-phosphor-caret-right-light /><span class="sr-only">Siguiente</span></span>@endif
    </div>
</nav>
@endif
