<x-tienda.layout :title="$marca->nombre">
    <div class="page-width breadcrumbs" style="margin-bottom: 20px; font-size: 13px; color: var(--color-ink-soft);">
        <a href="{{ route('tienda.inicio') }}" style="text-decoration: none; color: inherit;">Inicio</a>
        <span style="margin: 0 8px; opacity: 0.5;">/</span>
        <a href="{{ route('tienda.marcas.index') }}" style="text-decoration: none; color: inherit;">Marcas</a>
        <span style="margin: 0 8px; opacity: 0.5;">/</span>
        <span style="font-weight: bold; color: var(--color-navy);">{{ $marca->nombre }}</span>
    </div>

    <div class="page-width brand-heading" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--color-line);">
        <div>
            <p class="eyebrow" style="color: var(--color-sage); font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">Decants de perfumes originales</p>
            <h1 style="font-size: 2.5rem; margin: 0; color: var(--color-navy);">{{ $marca->nombre }}</h1>
            <p class="muted" style="margin-top: 8px; color: var(--color-ink-soft);">{{ $productos->count() }} {{ $productos->count() === 1 ? 'perfume' : 'perfumes' }} disponibles en decant</p>
        </div>

        <nav class="gender-tabs" aria-label="Filtrar por género" style="display: flex; gap: 8px; background: var(--color-sage-bg); padding: 6px; border-radius: 50px; border: 1px solid var(--color-line);">
            @foreach (['' => 'Todos', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir'] as $valor => $etiqueta)
                <a href="{{ route('tienda.marcas.show', ['marca' => $marca, ...($valor ? ['genero' => $valor] : [])]) }}"
                   @class(['selected' => ($genero ?? '') === $valor])
                   style="padding: 8px 16px; border-radius: 50px; font-size: 14px; text-decoration: none; transition: all 0.2s ease;
                          {{ ($genero ?? '') === $valor ? 'background: var(--color-navy); color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);' : 'color: var(--color-navy);' }}"
                   @if (($genero ?? '') === $valor) aria-current="page" @endif>
                    {{ $etiqueta }}
                </a>
            @endforeach
        </nav>
    </div>

    <section id="resultados-perfumes" class="page-width catalog-results" aria-label="Perfumes de {{ $marca->nombre }}" style="min-height: 60vh;">
        <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px;">
            @forelse ($productos as $producto)
                <x-tienda.tarjeta-perfume :producto="$producto" />
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--color-sage-bg); border-radius: 16px; border: 2px dashed var(--color-line);">
                    <p class="empty-copy" style="font-size: 18px; color: var(--color-ink-soft);">Todavía no hay perfumes en esta selección.</p>
                    <a class="text-link" href="{{ route('tienda.marcas.show', $marca) }}" style="display: inline-block; margin-top: 10px; color: var(--color-sage); font-weight: bold; text-decoration: none; border-bottom: 2px solid var(--color-sage);">Ver todos los de {{ $marca->nombre }}</a>
                </div>
            @endforelse
        </div>
    </section>
</x-tienda.layout>
