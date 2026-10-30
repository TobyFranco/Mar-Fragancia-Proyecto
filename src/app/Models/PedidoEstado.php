<?php

namespace App\Models;

use App\Casts\FechaUtc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoEstado extends Model
{
    protected $table = 'pedido_estados';

    protected $fillable = ['pedido_id', 'estado', 'usuario_id', 'created_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['created_at' => FechaUtc::class];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
