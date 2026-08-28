<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';
    protected $fillable = ['pedido_id', 'presentacion_id', 'cantidad', 'precio_unitario', 'subtotal'];
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function presentacion()
    {
        return $this->belongsTo(ProductoPresentacion::class, 'presentacion_id');
    }
}