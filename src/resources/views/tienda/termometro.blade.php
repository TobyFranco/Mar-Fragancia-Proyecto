<x-tienda.layout title="Termómetro olfativo">
    <section class="page-width thermometer-page" x-data="{
        temperatura: {{ $temperaturaInicial }}, noche: false, humedo: {{ Js::from($clima['humedo']) }}, mes: '',
        zonas: {{ Js::from($zonas) }}, porTemperatura: {{ Js::from($porTemperatura) }}, ciencia: {{ Js::from($ciencia) }},
        get clave() { return this.porTemperatura[this.temperatura] },
        get zona() { return this.zonas[this.clave] },
        get estado() { return this.zona.estados[(this.noche ? 'noche' : 'dia') + '_' + (this.humedo ? 'humedo' : 'seco')] }
    }" :data-zona="clave" data-zona="{{ $inicial['clave'] }}">
        <div class="thermometer-heading"><div><p class="eyebrow">Termómetro olfativo</p><h1>Cada clima tiene su perfume.</h1></div>
            <div class="weather-now"><p><x-phosphor-map-pin-light aria-hidden="true" />{{ $clima['fuente'] === 'promedio' ? 'Promedio del mes en Asunción' : 'Hoy en Asunción' }}: <strong>{{ $clima['temperatura'] }}°</strong> · {{ $clima['humedo'] ? 'húmedo' : 'seco' }}</p><button type="button" class="text-link" @click="temperatura = {{ $temperaturaInicial }}; noche = false; humedo = {{ Js::from($clima['humedo']) }}; mes = ''">Usar el clima de hoy</button></div>
        </div>
        <div class="thermometer-body">
            <div class="temperature-dial">
                <p class="temperature-number"><span x-text="temperatura">{{ $temperaturaInicial }}</span><small>°C</small></p><p class="temperature-zone" x-text="zona.nombre">{{ $inicial['nombre'] }}</p>
                <label class="sr-only" for="temperatura">Temperatura</label><input id="temperatura" type="range" min="10" max="40" step="1" value="{{ $temperaturaInicial }}" x-model.number="temperatura" @input="mes = ''" :aria-valuetext="temperatura + ' grados Celsius, ' + zona.nombre">
                <div class="temperature-ticks" aria-hidden="true"><span>10°</span><span>18°</span><span>26°</span><span>32°</span><span>40°</span></div>
                <div class="weather-toggles">
                    <fieldset><legend>Momento</legend><div><button id="momento-dia" type="button" :aria-pressed="!noche" @click="noche = false"><x-phosphor-sun-light aria-hidden="true" />Día</button><button id="momento-noche" type="button" :aria-pressed="noche" @click="noche = true"><x-phosphor-moon-light aria-hidden="true" />Noche</button></div></fieldset>
                    <fieldset><legend>Humedad</legend><div><button id="humedad-seco" type="button" :aria-pressed="!humedo" @click="humedo = false"><x-phosphor-wind-light aria-hidden="true" />Seco</button><button id="humedad-humedo" type="button" :aria-pressed="humedo" @click="humedo = true"><x-phosphor-drop-light aria-hidden="true" />Húmedo</button></div></fieldset>
                </div>
                <p class="temperature-science"><strong>¿Por qué?</strong> <span x-text="ciencia[temperatura]">{{ $ciencia[$temperaturaInicial] }}</span></p>
            </div>
            <div class="temperature-advice" aria-live="polite">
                <p class="eyebrow">Qué usar</p><h2 x-text="zona.titulo">{{ $inicial['titulo'] }}</h2><p class="advice-copy" x-text="estado.texto">{{ $inicial['estados'][$clima['humedo'] ? 'dia_humedo' : 'dia_seco']['texto'] }}</p>
                <div class="climate-notes">
                    @foreach ($zonas as $clave => $zona)
                        <div x-show="clave === '{{ $clave }}'" @if ($clave !== $inicial['clave']) x-cloak @endif class="climate-note-columns"><div><h3><x-phosphor-check-circle-light aria-hidden="true" />Notas que funcionan</h3><ul class="note-chips">@foreach ($zona['notas_si'] as $nota)<li>{{ $nota }}</li>@endforeach</ul></div><div><h3><x-phosphor-prohibit-light aria-hidden="true" />Mejor dejar para otro día</h3><ul class="note-chips avoid-notes">@foreach ($zona['notas_no'] as $nota)<li>{{ $nota }}</li>@endforeach</ul></div></div>
                    @endforeach
                </div>
                <p class="application-dose"><x-phosphor-spray-bottle-light aria-hidden="true" /><strong>Cuánto aplicar:</strong><span id="dosis-texto" x-text="estado.dosis === 1 ? '1 aplicación' : estado.dosis + ' aplicaciones'">{{ $inicial['estados'][$clima['humedo'] ? 'dia_humedo' : 'dia_seco']['dosis'] }} aplicación{{ $inicial['estados'][$clima['humedo'] ? 'dia_humedo' : 'dia_seco']['dosis'] === 1 ? '' : 'es' }}</span><span class="dose-marks" aria-hidden="true">@foreach (range(1, 4) as $n)<i :class="{ 'on': estado.dosis >= {{ $n }} }"></i>@endforeach</span></p>
                @foreach ($zonas as $clave => $zona)
                    <div class="climate-picks" data-recomendaciones="{{ $clave }}" x-show="clave === '{{ $clave }}'" @if ($clave !== $inicial['clave']) x-cloak @endif>
                        @forelse ($recomendaciones[$clave] as $producto)
                            @php($decant = $producto->presentaciones->first(fn ($p) => $p->disponible() && in_array($p->tamano->nombre, ['5 ml', '10 ml'], true)))
                            <a class="climate-pick" href="{{ route('tienda.perfume', $producto) }}"><span class="search-thumbnail" aria-hidden="true">@if ($producto->imagen)<img src="{{ asset('storage/'.$producto->imagen) }}" alt="" width="44" height="60">@else<span class="bottle"></span>@endif</span><span><span class="card-brand">{{ $producto->marca->nombre }}</span><strong>{{ $producto->nombre }}</strong><small>{{ $decant->tamano->nombre }} {{ \App\Support\Moneda::gs((float) $decant->precio) }}</small></span></a>
                        @empty
                            <p class="muted">Por ahora no tenemos decants disponibles para este clima. <a class="text-link" href="{{ route('tienda.catalogo') }}">Ver perfumes</a></p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
        <div class="climate-year"><div><h2>El año en Paraguay · tocá un mes</h2><span class="muted" x-text="mes ? mes + ': promedio ' + temperatura + '° de máxima' : ''"></span></div><div class="climate-months">@foreach ($meses as $nombre => $promedio)<button type="button" data-mes="{{ $nombre }}" data-clima="{{ $porTemperatura[$promedio] }}" :aria-pressed="mes === '{{ $nombre }}'" @click="temperatura = {{ $promedio }}; mes = '{{ $nombre }}'">{{ $nombre }}<small>{{ $promedio }}°</small></button>@endforeach</div></div>
    </section>
</x-tienda.layout>
