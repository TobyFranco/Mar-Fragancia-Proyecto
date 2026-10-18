<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    public const GENEROS = ['mujer', 'hombre', 'unisex'];

    protected $fillable = ['marca_id', 'categoria_id', 'nombre', 'descripcion', 'imagen', 'activo', 'genero', 'slug', 'destacado'];

    protected $attributes = ['genero' => 'unisex'];

    protected static function booted(): void
    {
        static::saving(function (Producto $producto) {
            if (! in_array($producto->genero, self::GENEROS, true)) {
                throw ValidationException::withMessages(['genero' => 'Elegí Mujer, Hombre o Para compartir.']);
            }
        });

        static::creating(function (Producto $producto) {
            if (empty($producto->slug)) {
                $base = Str::slug($producto->nombre);
                $producto->slug = $base;
                $sufijo = 2;
                while (static::where('slug', $producto->slug)->exists()) {
                    $producto->slug = $base.'-'.$sufijo++;
                }
            }
        });
    }

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'destacado' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function etiquetaGenero(): string
    {
        return match ($this->genero) {
            'mujer' => 'Mujer',
            'hombre' => 'Hombre',
            'unisex' => 'Para compartir',
        };
    }

    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('activo', true)->whereHas('presentaciones', function (Builder $presentaciones) {
            $presentaciones->where('activo', true)->whereHas('tamano', function (Builder $tamanos) {
                $tamanos->whereIn('nombre', ['5 ml', '10 ml']);
            });
        });
    }

    public function notas(): BelongsToMany
    {
        return $this->belongsToMany(Nota::class, 'nota_producto');
    }

    public function presentacionPorTamano(string $nombre): ?ProductoPresentacion
    {
        return $this->presentaciones->first(fn (ProductoPresentacion $presentacion) => $presentacion->tamano->nombre === $nombre);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function presentaciones()
    {
        return $this->hasMany(ProductoPresentacion::class);
    }
}
