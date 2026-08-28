<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $fillable = ['rol_id', 'nombre', 'email', 'password'];
    protected $hidden = ['password'];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class);
    }
}