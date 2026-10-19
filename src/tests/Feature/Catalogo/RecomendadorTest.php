<?php

namespace Tests\Feature\Catalogo;

use App\Models\DetallePedido;
use App\Models\Nota;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Services\Recomendador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecomendadorTest extends TestCase
{
    use RefreshDatabase;

    public function test_parecido_es_jaccard(): void
    {
        $a = $this->perfume('A', ['bergamota', 'pimienta', 'ambroxan', 'lavanda']);
        $b = $this->perfume('B', ['bergamota', 'pimienta', 'ámbar']);

        $this->assertSame(40, app(Recomendador::class)->parecido($a, $b));
        $this->assertSame(0, app(Recomendador::class)->parecido(new Producto, new Producto));
    }

    public function test_parecidos_ordena_por_porcentaje_y_nombre_y_excluye_base_inactivos_y_sin_stock(): void
    {
        $base = $this->perfume('Base', ['bergamota', 'lavanda']);
        $b = $this->perfume('Beta', ['bergamota']);
        $a = $this->perfume('Alfa', ['lavanda']);
        $igual = $this->perfume('Zeta', ['bergamota', 'lavanda']);
        $inactivo = $this->perfume('Inactivo', ['bergamota', 'lavanda']);
        $inactivo->update(['activo' => false]);
        $agotado = $this->perfume('Agotado', ['bergamota', 'lavanda']);
        $agotado->presentaciones()->update(['stock' => 0]);
        $deshabilitado = $this->perfume('Deshabilitado', ['bergamota', 'lavanda']);
        $deshabilitado->presentaciones()->update(['activo' => false]);

        $resultados = app(Recomendador::class)->parecidos($base, 10);

        $this->assertSame([$igual->id, $a->id, $b->id], $resultados->pluck('producto.id')->all());
        $this->assertSame([100, 50, 50], $resultados->pluck('porcentaje')->all());
        $this->assertSame(['bergamota', 'lavanda'], $resultados->first()['compartidas']->sort()->values()->all());
        $this->assertCount(2, app(Recomendador::class)->parecidos($base, 2));
    }

    public function test_parecidos_sin_notas_en_comun_no_aparecen(): void
    {
        $base = $this->perfume('Base', ['bergamota']);
        $this->perfume('Distinto', ['vainilla']);

        $this->assertCount(0, app(Recomendador::class)->parecidos($base, 4));
    }

    public function test_mas_probados_usa_ventas_confirmadas_90_dias_y_completa_con_destacados(): void
    {
        $this->travelTo('2026-10-18 12:00:00');
        $masVendido = $this->perfume('Más vendido', []);
        $otro = $this->perfume('Otra venta', []);
        $destacado = $this->perfume('Zeta destacado', []);
        $destacado->update(['destacado' => true]);
        $alfabetico = $this->perfume('Alfa sin ventas', []);
        $excluido = $this->perfume('Ignorado', []);
        foreach (['confirmado', 'preparando', 'enviado', 'entregado'] as $estado) {
            $this->venta($masVendido, $estado, 10, 2);
        }
        $this->venta($otro, 'confirmado', 90, 3);
        foreach (['pendiente', 'cancelado'] as $estado) {
            $this->venta($excluido, $estado, 1, 100);
        }
        $this->venta($excluido, 'confirmado', 120, 100);
        $agotado = $this->perfume('Agotado', []);
        $this->venta($agotado, 'confirmado', 1, 1000);
        $agotado->presentaciones()->update(['stock' => 0]);

        $resultado = app(Recomendador::class)->masProbados(4);

        $this->assertSame([$masVendido->id, $otro->id, $destacado->id, $alfabetico->id], $resultado->modelKeys());
    }

    private function perfume(string $nombre, array $notas): Producto
    {
        $producto = Producto::factory()->create(['nombre' => $nombre]);
        ProductoPresentacion::factory()->for($producto)->create();
        foreach ($notas as $nombreNota) {
            $producto->notas()->attach(Nota::firstOrCreate(['nombre' => $nombreNota]));
        }

        return $producto;
    }

    private function venta(Producto $producto, string $estado, int $dias, int $cantidad): void
    {
        $pedido = Pedido::create(['cliente_id' => null, 'fecha' => now()->subDays($dias), 'estado' => $estado, 'total' => 30000 * $cantidad]);
        DetallePedido::create(['pedido_id' => $pedido->id, 'presentacion_id' => $producto->presentaciones()->first()->id, 'cantidad' => $cantidad, 'precio_unitario' => 30000, 'subtotal' => 30000 * $cantidad]);
    }
}
