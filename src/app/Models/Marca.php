<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Marca extends Model
{
    use HasFactory;

    protected $table = 'marcas';

    protected $fillable = ['nombre', 'slug', 'destacada', 'orden_menu', 'imagen'];

    protected static function booted(): void
    {
        static::creating(function (Marca $marca) {
            if (empty($marca->slug)) {
                $base = Str::slug($marca->nombre);
                $marca->slug = $base;
                $sufijo = 2;
                while (static::where('slug', $marca->slug)->exists()) {
                    $marca->slug = $base.'-'.$sufijo++;
                }
            }
        });
    }

    protected function casts(): array
    {
        return ['destacada' => 'boolean', 'orden_menu' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeDelMenu(Builder $query): Builder
    {
        return $query->where('destacada', true)->orderBy('orden_menu')->limit(8);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
