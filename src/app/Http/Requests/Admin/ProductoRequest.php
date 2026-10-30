<?php

namespace App\Http\Requests\Admin;

use App\Models\Producto;
use App\Models\Tamano;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'marca_id' => ['required', 'integer', 'exists:marcas,id'],
            'categoria_id' => ['required', 'integer', 'exists:categorias,id'],
            'genero' => ['required', Rule::in(Producto::GENEROS)],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activo' => ['sometimes', 'boolean'],
            'destacado' => ['sometimes', 'boolean'],
            'notas' => ['sometimes', 'array'],
            'notas.*' => ['required', 'string', 'max:100'],
            'notas_nuevas' => ['nullable', 'string', 'max:1000'],
            'presentaciones' => ['required', 'array'],
            'presentaciones.*' => ['array'],
            'presentaciones.*.precio' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'presentaciones.*.activo' => ['sometimes', 'boolean'],
            'presentaciones.*.stock_inicial' => $this->isMethod('POST') ? ['nullable', 'integer', 'min:0', 'max:2147483647'] : ['exclude'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $permitidos = Tamano::whereIn('nombre', ['5 ml', '10 ml'])->pluck('id')->map(fn ($id) => (string) $id)->all();
            $filas = $this->input('presentaciones', []);
            if (array_diff(array_map('strval', array_keys($filas)), $permitidos)) {
                $validator->errors()->add('presentaciones', 'Solo se permiten decants de 5 ml y 10 ml.');
            }
            if (! collect($filas)->contains(fn ($fila) => isset($fila['precio']) && $fila['precio'] !== '')) {
                $validator->errors()->add('presentaciones', 'Cargá un precio para al menos un tamaño.');
            }
            foreach ($filas as $id => $fila) {
                if ($this->isMethod('POST') && ($fila['stock_inicial'] ?? 0) > 0 && (! isset($fila['precio']) || $fila['precio'] === '')) {
                    $validator->errors()->add('presentaciones.'.$id.'.precio', 'Cargá el precio de este tamaño para registrar su stock inicial.');
                }
            }
            foreach (preg_split('/[,\r\n]+/u', $this->input('notas_nuevas') ?? '') as $nombre) {
                if (mb_strlen(trim($nombre)) > 100) {
                    $validator->errors()->add('notas_nuevas', 'Cada nota admite hasta 100 caracteres.');
                }
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completá :attribute.',
            'string' => 'El campo :attribute debe ser un texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'array' => 'Revisá el formato de :attribute.',
            'boolean' => 'Elegí una opción válida en :attribute.',
            'exists' => 'La opción elegida para :attribute no está disponible.',
            'image' => 'Elegí una imagen válida.',
            'numeric' => 'El precio debe ser un número.',
            'decimal' => 'El precio admite hasta dos decimales.',
            'max' => 'El campo :attribute supera el máximo permitido (:max).',
            'min' => 'El campo :attribute no puede ser menor que :min.',
            'nombre.required' => 'Escribí el nombre del perfume.',
            'marca_id.required' => 'Elegí una marca.',
            'categoria_id.required' => 'Elegí una familia de aroma.',
            'genero.required' => 'Elegí un género.',
            'genero.in' => 'Elegí Mujer, Hombre o Para compartir.',
            'presentaciones.required' => 'Cargá un precio para al menos un tamaño.',
            'presentaciones.*.precio.min' => 'El precio no puede ser negativo.',
            'presentaciones.*.stock_inicial.min' => 'El stock inicial no puede ser negativo.',
        ];
    }
}
