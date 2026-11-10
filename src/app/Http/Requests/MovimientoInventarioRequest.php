<?php

namespace App\Http\Requests;

use App\Models\Tamano;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MovimientoInventarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presentacion_id' => ['required', 'integer', Rule::exists('producto_presentacion', 'id')->whereIn('tamano_id', Tamano::whereIn('nombre', ['5 ml', '10 ml'])->select('id'))],
            'tipo' => ['required', Rule::in(['entrada', 'salida'])],
            'cantidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'motivo' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['required' => 'Completá :attribute.', 'integer' => 'La cantidad debe ser un entero.', 'cantidad.min' => 'La cantidad debe ser mayor que cero.', 'in' => 'Elegí un tipo válido.', 'motivo.required' => 'Escribí el motivo del movimiento.'];
    }
}
