<?php

namespace Tests\Feature\Admin;

use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ModeloPedidosTest extends TestCase
{
    use RefreshDatabase;

    public function test_transiciones_permitidas_segun_tabla(): void
    {
        $tabla = [
            'pendiente' => ['confirmado', 'cancelado'],
            'confirmado' => ['preparando', 'cancelado'],
            'preparando' => ['enviado', 'cancelado'],
            'enviado' => ['entregado'],
            'entregado' => [],
            'cancelado' => [],
        ];

        foreach ($tabla as $origen => $permitidos) {
            $pedido = new Pedido(['estado' => $origen]);
            foreach ([...array_keys($tabla), 'desconocido'] as $destino) {
                $this->assertSame(in_array($destino, $permitidos, true), $pedido->puedePasarA($destino), "$origen → $destino");
            }
        }
        $this->assertFalse((new Pedido(['estado' => 'desconocido']))->puedePasarA('pendiente'));
        $this->assertSame(array_keys($tabla), Pedido::ESTADOS);
        $this->assertSame($tabla, Pedido::TRANSICIONES);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_pedido_creado_desde_checkout_registra_estado_pendiente(bool $autenticado): void
    {
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(12, 30));
        $usuario = null;
        if ($autenticado) {
            $usuario = $this->usuario();
            Cliente::create(['usuario_id' => $usuario->id]);
            $this->actingAs($usuario);
        }
        $presentacion = ProductoPresentacion::factory()->create();

        $this->withSession(['carrito' => [$presentacion->id => 1]])->post('/checkout', [
            'nombre_contacto' => 'María Paz', 'telefono' => '0981234567', 'metodo_entrega' => 'retiro',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $pedido = Pedido::sole();
        $this->assertDatabaseCount('pedido_estados', 1);
        $estado = $pedido->estados()->sole();
        $this->assertSame('pendiente', $estado->estado);
        $this->assertSame($usuario?->id, $estado->usuario_id);
        $this->assertTrue($estado->created_at->equalTo(now()));
        $this->assertTrue($estado->pedido->is($pedido));
        $this->assertSame($usuario?->id, $estado->usuario?->id);
        $this->assertSame(10, $presentacion->fresh()->stock);
    }

    public function test_fecha_confirmacion_sale_del_historial(): void
    {
        $pedido = Pedido::create(['fecha' => '2026-10-18 10:00:00', 'total' => 30000, 'estado' => 'entregado']);
        $this->assertNull($pedido->fechaConfirmacion());
        $confirmacion = $pedido->estados()->create(['estado' => 'confirmado', 'created_at' => '2026-11-01 09:00:00']);
        $inicial = $pedido->estados()->create(['estado' => 'pendiente', 'created_at' => '2026-10-18 10:00:00']);
        $entregado = $pedido->estados()->create(['estado' => 'entregado', 'created_at' => '2026-11-02 09:00:00']);

        $this->assertSame('2026-11-01 09:00:00', $pedido->fechaConfirmacion()->format('Y-m-d H:i:s'));
        $this->assertSame([$inicial->id, $confirmacion->id, $entregado->id], $pedido->estados->modelKeys());
    }

    #[TestWith(['pendiente', 'Pendiente', 'b-pend'])]
    #[TestWith(['confirmado', 'Confirmado', 'b-conf'])]
    #[TestWith(['preparando', 'Preparando', 'b-prep'])]
    #[TestWith(['enviado', 'Enviado', 'b-env'])]
    #[TestWith(['entregado', 'Entregado', 'b-ent'])]
    #[TestWith(['cancelado', 'Cancelado', 'b-can'])]
    public function test_etiquetas_de_estado(string $estado, string $etiqueta, string $clase): void
    {
        $pedido = new Pedido(['estado' => $estado]);

        $this->assertSame($etiqueta, $pedido->etiquetaEstado());
        $this->assertSame($clase, $pedido->claseInsignia());
    }

    public function test_movimientos_admiten_auditoria_nullable_y_usuario(): void
    {
        $presentacion = ProductoPresentacion::factory()->create();
        $datos = ['presentacion_id' => $presentacion->id, 'tipo' => 'entrada', 'cantidad' => 2, 'fecha' => now()];
        $historico = MovimientoInventario::create($datos)->fresh();
        $usuario = $this->usuario();
        $nuevo = MovimientoInventario::create([...$datos, 'usuario_id' => $usuario->id, 'stock_resultante' => 12])->fresh();

        $this->assertNull($historico->stock_resultante);
        $this->assertNull($historico->usuario_id);
        $this->assertSame(12, $nuevo->stock_resultante);
        $this->assertTrue($nuevo->usuario->is($usuario));
    }

    private function usuario(): Usuario
    {
        return Usuario::create(['rol_id' => Rol::create(['nombre' => 'cliente'])->id, 'nombre' => 'María Paz', 'email' => 'paz@example.com', 'password' => 'no-se-usa-en-este-test']);
    }
}
