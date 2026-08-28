<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';
    protected $fillable = ['marca_id', 'categoria_id', 'nombre', 'descripcion', 'imagen', 'activo'];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function presentaciones()
    {
        return $this->hasMany(ProductoPresentacion::class);
    }
}