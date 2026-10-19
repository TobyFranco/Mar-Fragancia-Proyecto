@props(['nombre', 'etiqueta', 'tipo' => 'text', 'autocomplete' => 'off', 'requerido' => true, 'maximo' => null])
@php($valor = old($nombre, ''))
<div class="form-field">
    <label for="auth-{{ $nombre }}">{{ $etiqueta }}</label>
    <input id="auth-{{ $nombre }}" name="{{ $nombre }}" type="{{ $tipo }}" autocomplete="{{ $autocomplete }}" @required($requerido) @if ($maximo) maxlength="{{ $maximo }}" @endif @if ($tipo !== 'password') value="{{ is_scalar($valor) ? $valor : '' }}" @endif @if ($errors->has($nombre)) aria-invalid="true" aria-describedby="auth-error-{{ $nombre }}" @endif>
    @error($nombre)<p id="auth-error-{{ $nombre }}" class="field-error" role="alert">{{ $message }}</p>@enderror
    @if ($slot->isNotEmpty())<p class="muted">{{ $slot }}</p>@endif
</div>
