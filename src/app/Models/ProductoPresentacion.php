<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPresentacion extends Model
{
    protected $table = 'producto_presentacion';
    protected $fillable = ['producto_id', 'tamano_id', 'precio', 'stock', 'activo'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tamano()
    {
        return $this->belongsTo(Tamano::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class, 'presentacion_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class, 'presentacion_id');
    }
}