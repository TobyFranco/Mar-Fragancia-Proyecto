<?php

namespace App\Http\Requests\Api;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListaPedidosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['estado' => ['nullable', Rule::in(Pedido::ESTADOS)]];
    }
}
