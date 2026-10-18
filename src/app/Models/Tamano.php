<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tamano extends Model
{
    use HasFactory;

    protected $table = 'tamanos';

    protected $fillable = ['nombre', 'descripcion'];

    public function presentaciones()
    {
        return $this->hasMany(ProductoPresentacion::class, 'tamano_id');
    }
}
