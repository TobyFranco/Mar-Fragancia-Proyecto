<?php

namespace Tests\Feature\Admin;

use App\Exceptions\StockInsuficiente;
use App\Exceptions\TransicionInvalida;
use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CicloPedido;
use App\Services\Inventario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CicloPedidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmar_descuenta_stock_y_registra_salidas(): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $admin = $this->admin();

        $resultado = app(CicloPedido::class)->pasarA($pedido, 'confirmado', $admin);

        $this->assertSame($pedido->id, $resultado->id);
        $this->assertSame('confirmado', $resultado->estado);
        $this->assertSame('confirmado', $pedido->fresh()->estado);
        $this->assertSame(8, $a->fresh()->stock);
        $this->assertSame(7, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $a->id, 'tipo' => 'salida', 'cantidad' => 2, 'stock_resultante' => 8, 'motivo' => 'Venta '.$pedido->numero, 'usuario_id' => $admin->id]);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $b->id, 'tipo' => 'salida', 'cantidad' => 3, 'stock_resultante' => 7, 'motivo' => 'Venta '.$pedido->numero, 'usuario_id' => $admin->id]);
        $this->assertDatabaseHas('pedido_estados', ['pedido_id' => $pedido->id, 'estado' => 'confirmado', 'usuario_id' => $admin->id]);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'total' => 150000]);
    }

    public function test_confirmar_sin_stock_suficiente_falla_sin_cambios(): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $b->update(['stock' => 2]);
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);

        try {
            $servicio->pasarA($pedido, 'confirmado', $admin);
            $this->fail('Debe rechazar el pedido completo.');
        } catch (StockInsuficiente $e) {
            $this->assertTrue($e->presentacion->is($b));
            $this->assertSame(2, $e->disponible);
            $this->assertStringContainsString($b->producto->nombre, $e->getMessage());
            $this->assertStringContainsString($b->tamano->nombre, $e->getMessage());
        }

        $this->assertSame('pendiente', $pedido->fresh()->estado);
        $this->assertSame(10, $a->fresh()->stock);
        $this->assertSame(2, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(['pendiente'], $pedido->estados()->pluck('estado')->all());
    }

    public function test_confirmar_dos_veces_descuenta_una_sola_vez(): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $obsoleto = $pedido->fresh();
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);
        $servicio->pasarA($pedido, 'confirmado', $admin);

        try {
            $servicio->pasarA($obsoleto, 'confirmado', $admin);
            $this->fail('La segunda confirmación debe rechazarse.');
        } catch (TransicionInvalida $e) {
            $this->assertStringContainsString('confirmado', $e->getMessage());
        }

        $this->assertSame('confirmado', $pedido->fresh()->estado);
        $this->assertSame(8, $a->fresh()->stock);
        $this->assertSame(7, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertSame(['pendiente', 'confirmado'], $pedido->estados()->pluck('estado')->all());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_cancelar_confirmado_devuelve_stock(bool $preparando): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);
        $servicio->pasarA($pedido, 'confirmado', $admin);
        if ($preparando) {
            $servicio->pasarA($pedido, 'preparando', $admin);
        }
        app(Inventario::class)->registrar($a, 'entrada', 4, 'Reposición', $admin);

        $servicio->pasarA($pedido, 'cancelado', $admin);

        $this->assertSame('cancelado', $pedido->fresh()->estado);
        $this->assertSame(14, $a->fresh()->stock);
        $this->assertSame(10, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 5);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $a->id, 'tipo' => 'entrada', 'cantidad' => 2, 'stock_resultante' => 14, 'motivo' => 'Cancelación '.$pedido->numero, 'usuario_id' => $admin->id]);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $b->id, 'tipo' => 'entrada', 'cantidad' => 3, 'stock_resultante' => 10, 'motivo' => 'Cancelación '.$pedido->numero, 'usuario_id' => $admin->id]);
    }

    public function test_cancelar_pendiente_no_toca_stock(): void
    {
        [$pedido, $a, $b] = $this->pedido();

        app(CicloPedido::class)->pasarA($pedido, 'cancelado', $this->admin());

        $this->assertSame('cancelado', $pedido->fresh()->estado);
        $this->assertSame(10, $a->fresh()->stock);
        $this->assertSame(10, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(['pendiente', 'cancelado'], $pedido->estados()->pluck('estado')->all());
    }

    #[TestWith(['pendiente', 'enviado'])]
    #[TestWith(['enviado', 'cancelado'])]
    #[TestWith(['entregado', 'pendiente'])]
    #[TestWith(['entregado', 'cancelado'])]
    #[TestWith(['confirmado', 'pendiente'])]
    #[TestWith(['cancelado', 'confirmado'])]
    #[TestWith(['pendiente', 'desconocido'])]
    public function test_transiciones_invalidas_se_rechazan(string $origen, string $destino): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $pedido->update(['estado' => $origen]);
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);

        try {
            $servicio->pasarA($pedido, $destino, $admin);
            $this->fail('Debe rechazar la transición.');
        } catch (TransicionInvalida $e) {
            $this->assertStringContainsString($origen, $e->getMessage());
            $this->assertStringContainsString($destino, $e->getMessage());
        }

        $this->assertSame($origen, $pedido->fresh()->estado);
        $this->assertSame(10, $a->fresh()->stock);
        $this->assertSame(10, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('pedido_estados', 1);
    }

    public function test_cada_cambio_queda_en_el_historial_con_usuario(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 31)->setTime(23, 50));
        [$pedido, $a, $b] = $this->pedido();
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);

        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(0, 10));
        foreach (['confirmado', 'preparando', 'enviado', 'entregado'] as $estado) {
            $servicio->pasarA($pedido, $estado, $admin);
            $this->assertDatabaseHas('pedido_estados', ['pedido_id' => $pedido->id, 'estado' => $estado, 'usuario_id' => $admin->id, 'created_at' => now()->utc()->format('Y-m-d H:i:s')]);
            $this->travel(1)->minute();
        }

        $this->assertSame('entregado', $pedido->fresh()->estado);
        $this->assertSame(['pendiente', 'confirmado', 'preparando', 'enviado', 'entregado'], $pedido->estados()->pluck('estado')->all());
        $this->assertSame('2026-11-01 03:10:00', $pedido->fechaConfirmacion()->format('Y-m-d H:i:s'));
        $this->assertSame(8, $a->fresh()->stock);
        $this->assertSame(7, $b->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    public function test_otro_pedido_consume_stock_antes_de_confirmar(): void
    {
        [$primero, $a] = $this->pedido();
        $segundo = Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 270000]);
        $segundo->detalles()->create(['presentacion_id' => $a->id, 'cantidad' => 9, 'precio_unitario' => 30000, 'subtotal' => 270000]);
        $admin = $this->admin();
        $servicio = app(CicloPedido::class);
        $servicio->pasarA($primero, 'confirmado', $admin);

        try {
            $servicio->pasarA($segundo, 'confirmado', $admin);
            $this->fail('No debe vender el mismo stock dos veces.');
        } catch (StockInsuficiente $e) {
            $this->assertSame(8, $e->disponible);
        }
        $this->assertSame('pendiente', $segundo->fresh()->estado);
        $this->assertSame(8, $a->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
        $this->assertSame(0, $segundo->estados()->count());
    }

    public function test_fallo_al_guardar_historial_revierte_estado(): void
    {
        [$pedido, $a, $b] = $this->pedido();
        $pedido->update(['estado' => 'confirmado']);
        $adminInexistente = new Usuario;
        $adminInexistente->id = 999;
        $servicio = app(CicloPedido::class);

        try {
            $servicio->pasarA($pedido, 'preparando', $adminInexistente);
            $this->fail('Debe fallar la FK de usuario.');
        } catch (QueryException) {
            $this->assertSame('confirmado', $pedido->fresh()->estado);
            $this->assertSame(10, $a->fresh()->stock);
            $this->assertSame(10, $b->fresh()->stock);
            $this->assertDatabaseCount('movimientos_inventario', 0);
            $this->assertDatabaseCount('pedido_estados', 1);
        }
    }

    private function pedido(): array
    {
        $pedido = Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 150000]);
        $a = ProductoPresentacion::factory()->create(['stock' => 10]);
        $b = ProductoPresentacion::factory()->create(['stock' => 10]);
        $pedido->detalles()->createMany([
            ['presentacion_id' => $a->id, 'cantidad' => 2, 'precio_unitario' => 30000, 'subtotal' => 60000],
            ['presentacion_id' => $b->id, 'cantidad' => 3, 'precio_unitario' => 30000, 'subtotal' => 90000],
        ]);
        $pedido->estados()->create(['estado' => 'pendiente', 'created_at' => now()]);

        return [$pedido, $a, $b];
    }

    private function admin(): Usuario
    {
        return Usuario::create(['rol_id' => Rol::create(['nombre' => 'administrador'])->id, 'nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => 'no-se-usa-en-este-test']);
    }
}
