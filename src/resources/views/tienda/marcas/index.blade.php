<x-tienda.layout title="Marcas">
    <div class="page-width page-intro">
        <h1>Marcas</h1>
        <p>{{ $marcas->count() }} casas de fragancias · seleccioná una para explorar su catálogo</p>
    </div>

    <nav class="alphabet page-width" aria-label="Índice alfabético de marcas" style="display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; padding: 15px; background: var(--color-sage-bg); border-radius: 12px; border: 1px solid var(--color-line);">
        @foreach (range('A', 'Z') as $letra)
            @if ($grupos->has($letra))
                <a href="#letra-{{ $letra }}" style="color: var(--color-navy); font-weight: bold; text-decoration: none; width: 30px; height: 30px; display: grid; place-items: center; border-radius: 50%; background: white; border: 1px solid var(--color-line); transition: all 0.2s ease;">{{ $letra }}</a>
            @else
                <span aria-disabled="true" style="color: var(--color-ink-soft); opacity: 0.4; width: 30px; height: 30px; display: grid; place-items: center;">{{ $letra }}</span>
            @endif
        @endforeach
    </nav>

    <div class="brand-groups page-width" style="display: grid; gap: 40px;">
        @foreach ($grupos as $letra => $grupo)
            <section id="letra-{{ $letra }}" aria-label="Marcas con {{ $letra }}" style="scroll-margin-top: 20px;">
                <h2 style="color: var(--color-navy); border-bottom: 2px solid var(--color-sage); display: inline-block; margin-bottom: 20px; padding-bottom: 4px;">{{ $letra }}</h2>
                <ul style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px; list-style: none; padding: 0;">
                    @foreach ($grupo as $marca)
                        <li style="margin: 0;">
                            <a href="{{ route('tienda.marcas.show', $marca) }}" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: white; border: 1px solid var(--color-line); border-radius: 8px; text-decoration: none; color: var(--color-navy); transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);" onmouseover="this.style.borderColor='var(--color-sage)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.05)';" onmouseout="this.style.borderColor='var(--color-line)'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.02)';">
                                <span style="font-weight: 500;">{{ $marca->nombre }}</span>
                                <span class="brand-total" style="font-size: 12px; background: var(--color-sage-soft); color: var(--color-navy); padding: 2px 8px; border-radius: 12px; font-weight: bold;">{{ $marca->productos_count }} {{ $marca->productos_count === 1 ? 'perfume' : 'perfumes' }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-tienda.layout>
