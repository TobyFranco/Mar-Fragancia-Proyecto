@props(['marca'])
<section id="panel-{{ $marca->slug }}" aria-label="Perfumes de {{ $marca->nombre }}" class="brand-panel" x-cloak x-show="abierta === {{ $marca->id }}" x-transition.opacity.duration.150ms>
    <a class="climate-strip" href="{{ url('/termometro-olfativo') }}"><x-phosphor-thermometer-light aria-hidden="true" /><span>Termómetro olfativo: encontrá el {{ $marca->nombre }} ideal para el clima de hoy</span></a>
    <button type="button" class="icon-button panel-close" aria-label="Cerrar panel de {{ $marca->nombre }}" @click="cerrar()"><x-phosphor-x-light aria-hidden="true" /></button>
    <div class="panel-columns page-width">
        <div>
            @if ($marca->imagen)
                <img class="brand-photo" src="{{ asset('storage/'.$marca->imagen) }}" alt="Perfumes de {{ $marca->nombre }}" loading="lazy">
            @else
                <div class="brand-photo perfume-placeholder" aria-hidden="true"><span class="bottle"></span></div>
            @endif
            <a class="text-link" href="{{ url('/marcas/'.$marca->slug) }}">Todo {{ $marca->nombre }} <x-phosphor-caret-right-light aria-hidden="true" /></a>
        </div>
        @foreach (['portada' => 'En portada', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Para compartir'] as $genero => $etiqueta)
            <div>
                <h2>{{ $etiqueta }}</h2>
                <ul>
                    @foreach (($genero === 'portada' ? $marca->productos->where('destacado', true) : $marca->productos->where('genero', $genero))->take(6) as $perfume)
                        <li><a href="{{ url('/perfumes/'.$perfume->slug) }}">{{ $perfume->nombre }}</a></li>
                    @endforeach
                </ul>
                <a class="text-link" href="{{ url('/marcas/'.$marca->slug).($genero === 'portada' ? '' : '?genero='.$genero) }}">Ver todo <x-phosphor-caret-right-light aria-hidden="true" /></a>
            </div>
        @endforeach
    </div>
</section>
