<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $fillable = ['usuario_id', 'telefono', 'direccion'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}