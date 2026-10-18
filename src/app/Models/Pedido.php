<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = ['cliente_id', 'fecha', 'estado', 'total', 'observacion', 'nombre_contacto', 'telefono', 'cedula', 'email', 'metodo_entrega', 'ciudad', 'barrio', 'direccion', 'referencia', 'subtotal', 'costo_envio'];

    protected static function booted(): void
    {
        static::created(function (Pedido $pedido) {
            $pedido->numero = 'MF-'.str_pad((string) $pedido->id, 4, '0', STR_PAD_LEFT);
            $pedido->saveQuietly();
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }
}
