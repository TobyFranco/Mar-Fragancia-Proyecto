<x-tienda.layout title="Mis datos">
    <section class="page-width account-page">
        <a class="text-link" href="{{ route('cliente.home') }}">Volver a mi cuenta</a>
        <h1>Mis datos</h1>
        <p class="muted">Actualizá tus datos de contacto y entrega.</p>
        @if (session('estado'))<p role="status">{{ session('estado') }}</p>@endif
        <form class="account-form" method="POST" action="{{ route('cliente.datos.actualizar') }}">
            @csrf @method('PUT')
            @foreach (['nombre' => ['Nombre y apellido', $usuario->nombre, 100, 'name'], 'telefono' => ['Teléfono', $usuario->cliente?->telefono, 30, 'tel'], 'direccion' => ['Dirección', $usuario->cliente?->direccion, 255, 'street-address']] as $campo => [$etiqueta, $valor, $maximo, $autocomplete])
                @php($valorCampo = old($campo, $valor))
                <div class="form-field">
                    <label for="{{ $campo }}">{{ $etiqueta }}</label>
                    <input id="{{ $campo }}" name="{{ $campo }}" value="{{ is_scalar($valorCampo) ? $valorCampo : '' }}" maxlength="{{ $maximo }}" autocomplete="{{ $autocomplete }}" @required($campo === 'nombre') @if ($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif>
                    @error($campo)<p id="error-{{ $campo }}" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <button class="button" type="submit">Guardar cambios</button>
        </form>
    </section>
</x-tienda.layout>
