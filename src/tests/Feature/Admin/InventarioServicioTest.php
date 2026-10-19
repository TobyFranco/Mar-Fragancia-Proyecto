<?php

namespace Tests\Feature\Admin;

use App\Exceptions\StockInsuficiente;
use App\Models\MovimientoInventario;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Inventario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use TypeError;

class InventarioServicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_entrada_suma_y_registra_stock_resultante(): void
    {
        $this->freezeTime();
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $usuario = Usuario::create(['rol_id' => Rol::create(['nombre' => 'administrador'])->id, 'nombre' => 'Paz', 'email' => 'paz@example.com', 'password' => 'no-se-usa']);

        $movimiento = app(Inventario::class)->registrar($presentacion, 'entrada', 4, 'Fraccionado', $usuario);

        $this->assertInstanceOf(MovimientoInventario::class, $movimiento);
        $this->assertSame(6, $presentacion->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['id' => $movimiento->id, 'presentacion_id' => $presentacion->id, 'tipo' => 'entrada', 'cantidad' => 4, 'stock_resultante' => 6, 'motivo' => 'Fraccionado', 'usuario_id' => $usuario->id, 'fecha' => now()->format('Y-m-d H:i:s')]);
        $this->assertTrue($movimiento->fresh()->fecha->equalTo(now()->startOfSecond()));
    }

    public function test_salida_resta(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);

        $movimiento = app(Inventario::class)->registrar($presentacion, 'salida', 2, 'Venta MF-0042');

        $this->assertSame(0, $presentacion->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['id' => $movimiento->id, 'tipo' => 'salida', 'cantidad' => 2, 'stock_resultante' => 0, 'usuario_id' => null]);
    }

    public function test_salida_no_deja_stock_negativo(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'salida', 3, 'Ajuste');
            $this->fail('Debe rechazar la salida sin stock suficiente.');
        } catch (StockInsuficiente $e) {
            $this->assertTrue($e->presentacion->is($presentacion));
            $this->assertSame(2, $e->disponible);
            $this->assertStringContainsString($presentacion->producto->nombre, $e->getMessage());
            $this->assertStringContainsString($presentacion->tamano->nombre, $e->getMessage());
        }

        $this->assertSame(2, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith([0])]
    #[TestWith([-1])]
    public function test_cantidad_invalida_se_rechaza(int $cantidad): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'entrada', $cantidad, 'Ajuste');
            $this->fail('Debe rechazar la cantidad inválida.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->assertSame(2, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_cantidad_texto_se_rechaza_sin_cambios(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'salida', 'texto', 'Ajuste');
            $this->fail('Debe rechazar texto como cantidad.');
        } catch (TypeError) {
            $this->assertSame(2, $presentacion->fresh()->stock);
            $this->assertDatabaseCount('movimientos_inventario', 0);
        }
    }

    public function test_tipo_invalido_se_rechaza(): void
    {
        $presentacion = ProductoPresentacion::factory()->create();
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'ajuste', 1, 'Recuento');
            $this->fail('Debe rechazar un tipo desconocido.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->assertSame(10, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith([''])]
    #[TestWith(['   '])]
    public function test_motivo_vacio_se_rechaza(string $motivo): void
    {
        $presentacion = ProductoPresentacion::factory()->create();
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'entrada', 1, $motivo);
            $this->fail('El movimiento requiere motivo.');
        } catch (InvalidArgumentException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->assertSame(10, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_criticas_lista_stock_menor_o_igual_a_tres_solo_activas(): void
    {
        $cero = ProductoPresentacion::factory()->create(['stock' => 0]);
        $tres = ProductoPresentacion::factory()->create(['stock' => 3]);
        ProductoPresentacion::factory()->create(['stock' => 4]);
        ProductoPresentacion::factory()->create(['stock' => 2, 'activo' => false]);

        $criticas = app(Inventario::class)->criticas();

        $this->assertEqualsCanonicalizing([$cero->id, $tres->id], $criticas->modelKeys());
        foreach ($criticas as $presentacion) {
            $this->assertTrue($presentacion->relationLoaded('producto'));
            $this->assertTrue($presentacion->relationLoaded('tamano'));
        }
    }

    public function test_relee_stock_persistido_antes_de_registrar(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 10]);
        $presentacion->fresh()->update(['stock' => 2]);

        app(Inventario::class)->registrar($presentacion, 'entrada', 1, 'Reposición');

        $this->assertSame(3, $presentacion->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['stock_resultante' => 3]);
    }

    public function test_error_al_guardar_movimiento_revierte_el_stock(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $usuarioInexistente = new Usuario;
        $usuarioInexistente->id = 999;
        $servicio = app(Inventario::class);

        try {
            $servicio->registrar($presentacion, 'entrada', 1, 'Reposición', $usuarioInexistente);
            $this->fail('Debe fallar la FK de usuario.');
        } catch (QueryException) {
            $this->assertSame(2, $presentacion->fresh()->stock);
            $this->assertDatabaseCount('movimientos_inventario', 0);
        }
    }
}
