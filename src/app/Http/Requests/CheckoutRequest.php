<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $telefono = $this->input('telefono');
        if (is_string($telefono)) {
            $telefono = preg_replace('/[\s\p{Z}]+/u', '', $telefono);
            if (str_starts_with($telefono, '+595')) {
                $telefono = '0'.substr($telefono, 4);
            }
            $this->merge(['telefono' => $telefono]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre_contacto' => ['required', 'string', 'max:255'],
            'telefono' => ['bail', 'required', 'string', 'regex:/\A09[0-9]{8}\z/'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'metodo_entrega' => ['required', Rule::in(['retiro', 'asuncion', 'interior'])],
            'ciudad' => ['required_if:metodo_entrega,asuncion,interior', 'nullable', 'string', 'max:255'],
            'barrio' => ['nullable', 'string', 'max:255'],
            'direccion' => ['required_if:metodo_entrega,asuncion,interior', 'nullable', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.*' => 'Revisá el número: deben ser 10 dígitos, ej. 0981 234 567.',
            'nombre_contacto.required' => 'Completá tu nombre y apellido.',
            'ciudad.required_if' => 'Completá la ciudad para el envío.',
            'direccion.required_if' => 'Completá la dirección para el envío.',
            'email.email' => 'Revisá el email, ej. nombre@correo.com.',
            'metodo_entrega.*' => 'Elegí cómo querés recibir tu pedido.',
            '*.string' => 'Completá este campo con texto.',
            '*.max' => 'Usá hasta 255 caracteres.',
        ];
    }
}
