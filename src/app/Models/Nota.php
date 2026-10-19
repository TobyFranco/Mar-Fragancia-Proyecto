<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Nota extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'nota_producto');
    }
}
