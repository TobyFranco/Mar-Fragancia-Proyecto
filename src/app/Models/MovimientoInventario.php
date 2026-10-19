<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = ['presentacion_id', 'tipo', 'cantidad', 'fecha', 'motivo', 'usuario_id', 'stock_resultante'];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    protected function casts(): array
    {
        return ['stock_resultante' => 'integer', 'fecha' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function presentacion()
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }
}
