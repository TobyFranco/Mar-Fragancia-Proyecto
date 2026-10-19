<x-tienda.layout title="Tus datos y envío">
    @php($metodo = in_array(old('metodo_entrega'), ['retiro', 'asuncion', 'interior'], true) ? old('metodo_entrega') : 'asuncion')
    <form class="page-width checkout-page" method="POST" action="{{ route('tienda.checkout.enviar') }}" x-data="{
        metodo: {{ Js::from($metodo) }}, subtotal: {{ $subtotal }}, envioAsuncion: {{ (int) config('tienda.envio_asuncion') }},
        get envio() { return this.metodo === 'asuncion' ? this.envioAsuncion : 0 },
        gs(valor) { return '₲ ' + valor.toLocaleString('es-PY') }
    }">
        @csrf
        <div class="checkout-fields">
            <ol class="checkout-steps" aria-label="Pasos del pedido"><li class="done"><x-phosphor-check-light aria-hidden="true" /><a href="{{ route('tienda.carrito') }}">Carrito</a></li><li aria-current="step"><span>2</span>Tus datos</li><li><span>3</span>WhatsApp</li></ol>
            <h1>Tus datos y envío</h1><p class="muted">Para comprar no hace falta cuenta. Confirmamos el pedido por WhatsApp.</p>
            @error('carrito')<div class="form-errors" role="alert">{{ $message }} <a class="text-link" href="{{ route('tienda.carrito') }}">Revisar mi carrito</a></div>@enderror
            <fieldset><legend>Datos personales</legend><div class="checkout-form-grid">
                @foreach (['nombre_contacto' => ['Nombre y apellido', 'text', 'name'], 'telefono' => ['Celular (WhatsApp)', 'tel', 'tel-national'], 'cedula' => ['Cédula (opcional, para la factura)', 'text', 'off'], 'email' => ['Email (opcional)', 'email', 'email']] as $campo => [$etiqueta, $tipo, $autocomplete])
                    @php($valorCampo = old($campo, $precarga[$campo] ?? ''))
                    <div class="form-field"><label for="{{ $campo }}">{{ $etiqueta }}</label><input id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" autocomplete="{{ $autocomplete }}" value="{{ is_scalar($valorCampo) ? $valorCampo : '' }}" maxlength="{{ $campo === 'telefono' ? 30 : 255 }}" @required(in_array($campo, ['nombre_contacto', 'telefono'])) @if ($campo === 'telefono') placeholder="0981 234 567" @endif @if ($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif>
                        @error($campo)<p id="error-{{ $campo }}" class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div></fieldset>
            <fieldset><legend>Entrega</legend>
                <div class="delivery-options">
                    @foreach (['retiro' => ['Retiro', 'Gratis · coordinamos punto'], 'asuncion' => ['Envío Asunción y Gran Asunción', \App\Support\Moneda::gs((int) config('tienda.envio_asuncion'))], 'interior' => ['Envío al interior', 'Por encomienda · a coordinar']] as $valor => [$nombre, $detalle])
                        <label><input name="metodo_entrega" type="radio" value="{{ $valor }}" x-model="metodo" @checked($metodo === $valor) required><span><strong>{{ $nombre }}</strong><small>{{ $detalle }}</small></span></label>
                    @endforeach
                </div>
                @error('metodo_entrega')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                <div class="checkout-form-grid delivery-fields" x-show="metodo !== 'retiro'">
                    @foreach (['ciudad' => ['Ciudad', 'address-level2'], 'barrio' => ['Barrio (opcional)', 'address-level3'], 'direccion' => ['Dirección', 'street-address'], 'referencia' => ['Referencia (opcional)', 'off']] as $campo => [$etiqueta, $autocomplete])
                        @php($valorCampo = old($campo, $precarga[$campo] ?? ''))
                        <div @class(['form-field', 'wide-field' => in_array($campo, ['direccion', 'referencia'])])><label for="{{ $campo }}">{{ $etiqueta }}</label><input id="{{ $campo }}" name="{{ $campo }}" autocomplete="{{ $autocomplete }}" value="{{ is_scalar($valorCampo) ? $valorCampo : '' }}" maxlength="255" :disabled="metodo === 'retiro'" @if (in_array($campo, ['ciudad', 'direccion'])) :required="metodo !== 'retiro'" required @endif @if ($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif>
                            @error($campo)<p id="error-{{ $campo }}" class="field-error" role="alert">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
        </div>
        <aside class="checkout-summary" aria-labelledby="resumen-titulo"><h2 id="resumen-titulo">Tu pedido</h2>
            @foreach ($lineas as $linea)<div class="summary-row"><span>{{ $linea['presentacion']->producto->nombre }} · Decant {{ $linea['presentacion']->tamano->nombre }} × {{ $linea['cantidad'] }}</span><strong>{{ \App\Support\Moneda::gs($linea['subtotal']) }}</strong></div>@endforeach
            <hr><div class="summary-row"><span>Subtotal</span><strong>{{ \App\Support\Moneda::gs($subtotal) }}</strong></div>
            <div class="summary-row"><span>Envío</span><strong id="checkout-envio" x-text="metodo === 'interior' ? 'A coordinar' : gs(envio)">{{ \App\Support\Moneda::gs((int) config('tienda.envio_asuncion')) }}</strong></div>
            <div class="summary-row summary-total"><span>Total</span><strong id="checkout-total" aria-live="polite" x-text="gs(subtotal + envio)">{{ \App\Support\Moneda::gs($subtotal + (int) config('tienda.envio_asuncion')) }}</strong></div>
            <button class="button button-whatsapp" type="submit"><x-phosphor-whatsapp-logo aria-hidden="true" />Enviar pedido por WhatsApp</button><p class="muted">Se abre WhatsApp con tu pedido listo para enviar. El pedido queda guardado para que puedas volver a abrirlo.</p>
        </aside>
    </form>
</x-tienda.layout>
