<?php

namespace App\Http\Requests\Api;

use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['marca' => ['nullable', 'string', 'max:150'], 'categoria' => ['nullable', 'integer'], 'genero' => ['nullable', Rule::in(Producto::GENEROS)], 'q' => ['nullable', 'string', 'max:150'], 'tamano' => ['nullable', Rule::in(['5', '10'])]];
    }
}
