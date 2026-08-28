<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';
    protected $fillable = ['presentacion_id', 'tipo', 'cantidad', 'fecha', 'motivo'];
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function presentacion()
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }
}