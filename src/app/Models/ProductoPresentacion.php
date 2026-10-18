<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoPresentacion extends Model
{
    use HasFactory;

    protected $table = 'producto_presentacion';

    protected $fillable = ['producto_id', 'tamano_id', 'precio', 'stock', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'stock' => 'integer', 'precio' => 'decimal:2'];
    }

    public function disponible(): bool
    {
        return $this->activo && $this->stock > 0;
    }

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
