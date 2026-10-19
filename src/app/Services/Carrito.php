<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoPresentacion;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Carrito
{
    public function __construct(private Session $session) {}

    public function agregar(ProductoPresentacion $p, int $cantidad): void
    {
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'Elegí una cantidad entera mayor a cero.']);
        }
        $this->validarDisponible($p);
        $actual = (int) $this->session->get('carrito.'.$p->id, 0);
        $this->guardar($p, min($actual, $p->stock) + min($cantidad, $p->stock + 1));
        $this->session->put('carrito_ultimo', $p->producto_id);
        $this->session->flash('carrito_confirmacion', 'Agregaste '.$p->producto->nombre.' · Decant '.$p->tamano->nombre);
    }

    public function actualizar(int $presentacionId, int $cantidad): void
    {
        if ($cantidad < 0) {
            throw ValidationException::withMessages(['cantidad' => 'Elegí una cantidad entera igual o mayor a cero.']);
        }
        $carrito = $this->session->get('carrito', []);
        if ($cantidad === 0) {
            unset($carrito[$presentacionId]);
            $this->session->put('carrito', $carrito);

            return;
        }
        if (! array_key_exists($presentacionId, $carrito)) {
            throw ValidationException::withMessages(['carrito' => 'Ese decant ya no está en tu carrito.']);
        }
        $p = ProductoPresentacion::with(['producto', 'tamano'])->findOrFail($presentacionId);
        $this->validarDisponible($p);
        $this->guardar($p, $cantidad);
    }

    /** @return Collection<int, array{presentacion: ProductoPresentacion, cantidad: int, subtotal: int}> */
    public function lineas(): Collection
    {
        $carrito = $this->session->get('carrito', []);
        $presentaciones = ProductoPresentacion::with(['producto.marca', 'tamano'])->whereIn('id', array_keys($carrito))->get()->keyBy('id');

        return collect($carrito)->map(function (int $cantidad, int $id) use ($presentaciones): ?array {
            $p = $presentaciones->get($id);

            return $p ? ['presentacion' => $p, 'cantidad' => $cantidad, 'subtotal' => (int) $p->precio * $cantidad] : null;
        })->filter()->values();
    }

    public function subtotal(): int
    {
        return (int) $this->lineas()->sum('subtotal');
    }

    public function unidades(): int
    {
        return (int) array_sum($this->session->get('carrito', []));
    }

    public function vaciar(): void
    {
        $this->session->forget(['carrito', 'carrito_ultimo']);
    }

    public function ultimoAgregado(): ?Producto
    {
        return Producto::with('notas')->find($this->session->get('carrito_ultimo'));
    }

    private function validarDisponible(ProductoPresentacion $p): void
    {
        if (! $p->disponible() || ! $p->producto->activo || ! in_array($p->tamano->nombre, ['5 ml', '10 ml'], true)) {
            throw ValidationException::withMessages(['carrito' => $p->producto->nombre.' · Decant '.$p->tamano->nombre.' no está disponible.']);
        }
    }

    private function guardar(ProductoPresentacion $p, int $cantidad): void
    {
        if ($cantidad > $p->stock) {
            $this->session->flash('aviso_carrito', 'Solo quedan '.$p->stock.' de '.$p->producto->nombre.' · Decant '.$p->tamano->nombre.'.');
        }
        $carrito = $this->session->get('carrito', []);
        $carrito[$p->id] = min($cantidad, $p->stock);
        $this->session->put('carrito', $carrito);
    }
}
