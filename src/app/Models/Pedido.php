<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Pedido extends Model
{
    public const ESTADOS = ['pendiente', 'confirmado', 'preparando', 'enviado', 'entregado', 'cancelado'];

    public const TRANSICIONES = [
        'pendiente' => ['confirmado', 'cancelado'],
        'confirmado' => ['preparando', 'cancelado'],
        'preparando' => ['enviado', 'cancelado'],
        'enviado' => ['entregado'],
        'entregado' => [],
        'cancelado' => [],
    ];

    protected $table = 'pedidos';

    protected $fillable = ['cliente_id', 'fecha', 'estado', 'total', 'observacion', 'nombre_contacto', 'telefono', 'cedula', 'email', 'metodo_entrega', 'ciudad', 'barrio', 'direccion', 'referencia', 'subtotal', 'costo_envio'];

    protected static function booted(): void
    {
        static::created(function (Pedido $pedido) {
            $pedido->numero = 'MF-'.str_pad((string) $pedido->id, 4, '0', STR_PAD_LEFT);
            $pedido->saveQuietly();
        });
    }

    public function puedePasarA(string $estado): bool
    {
        return in_array($estado, self::TRANSICIONES[$this->estado] ?? [], true);
    }

    public function estados(): HasMany
    {
        return $this->hasMany(PedidoEstado::class)->orderBy('created_at')->orderBy('id');
    }

    public function fechaConfirmacion(): ?Carbon
    {
        return $this->estados()->where('estado', 'confirmado')->first()?->created_at;
    }

    public function etiquetaEstado(): string
    {
        return ucfirst($this->estado);
    }

    public function claseInsignia(): string
    {
        return match ($this->estado) {
            'pendiente' => 'b-pend',
            'confirmado' => 'b-conf',
            'preparando' => 'b-prep',
            'enviado' => 'b-env',
            'entregado' => 'b-ent',
            'cancelado' => 'b-can',
            default => '',
        };
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
