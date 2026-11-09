<?php

namespace App\Http\Requests\Api;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['estado' => ['required', 'string', Rule::in(Pedido::ESTADOS)]];
    }
}
