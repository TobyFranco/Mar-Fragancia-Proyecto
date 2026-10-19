<?php

namespace Tests\Feature\Admin;

use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use App\Services\MetricasAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requiere_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->actingAs($this->usuario('cliente'))->get('/admin/dashboard')->assertForbidden();
    }

    public function test_menu_muestra_grupos_y_contadores(): void
    {
        Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 0]);
        ProductoPresentacion::factory()->create(['stock' => 3]);

        $this->actingAs($this->usuario())->get('/admin/dashboard')->assertOk()
            ->assertSeeText(['VENTAS', 'CATÁLOGO', 'STOCK', 'Pedidos por confirmar', 'Stock crítico'])
            ->assertSee('aria-label="1 pedidos pendientes"', false)->assertSee('aria-label="1 presentaciones críticas"', false);
    }

    public function test_metricas_cuentan_por_fecha_de_confirmacion(): void
    {
        $this->pedido('2026-10-31 23:00:00', '2026-11-01 03:00:00', 100000);
        $this->pedido('2026-11-01 01:00:00', '2026-11-01 02:59:59', 200000);
        $this->pedido('2026-11-30 20:00:00', '2026-12-01 02:59:59', 300000);
        $this->pedido('2026-11-30 20:00:00', '2026-12-01 03:00:00', 400000);

        $r = app(MetricasAdmin::class)->resumen(Carbon::parse('2026-11-15', 'America/Asuncion'));

        $this->assertSame(400000, $r['ventas_mes']);
        $this->assertSame(2, $r['pedidos_mes']);
    }

    public function test_metricas_excluyen_cancelados_y_pendientes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 12:00:00', 'UTC'));
        $this->pedido('2026-11-01', '2026-11-02', 50000, 'cancelado');
        Pedido::create(['fecha' => '2026-11-01 10:00:00', 'estado' => 'pendiente', 'total' => 60000]);
        Pedido::create(['fecha' => '2026-11-01', 'estado' => 'confirmado', 'total' => 90000]);
        foreach (['confirmado', 'preparando', 'enviado', 'entregado'] as $estado) {
            $this->pedido('2026-11-01', '2026-11-02', 10000, $estado);
        }

        $r = app(MetricasAdmin::class)->resumen();

        $this->assertSame(40000, $r['ventas_mes']);
        $this->assertSame(4, $r['pedidos_mes']);
        $this->assertSame(1, $r['pendientes']);
        $this->assertSame('2026-11-01 10:00:00', $r['pendiente_mas_viejo']->format('Y-m-d H:i:s'));
    }

    public function test_mas_vendidos_top_5_por_unidades(): void
    {
        $pedido = $this->pedido('2026-11-01', '2026-11-02', 100000);
        $presentaciones = [];
        foreach ([1, 3, 2, 5, 4, 6] as $cantidad) {
            $p = ProductoPresentacion::factory()->create();
            $presentaciones[$cantidad] = $p->id;
            $pedido->detalles()->create(['presentacion_id' => $p->id, 'cantidad' => $cantidad, 'precio_unitario' => 1000, 'subtotal' => 1000 * $cantidad]);
        }

        $r = app(MetricasAdmin::class)->resumen(Carbon::parse('2026-11-15', 'America/Asuncion'));

        $this->assertSame([6, 5, 4, 3, 2], $r['mas_vendidos']->pluck('unidades')->all());
        $this->assertSame([$presentaciones[6], $presentaciones[5], $presentaciones[4], $presentaciones[3], $presentaciones[2]], $r['mas_vendidos']->pluck('presentacion.id')->all());
    }

    public function test_porcentaje_de_5ml(): void
    {
        $mes = Carbon::parse('2026-11-15', 'America/Asuncion');
        $this->assertSame(0, app(MetricasAdmin::class)->resumen($mes)['porcentaje_5ml']);
        $pedido = $this->pedido('2026-11-01', '2026-11-02', 100000);
        foreach (['5 ml' => 3, '10 ml' => 2] as $tamano => $cantidad) {
            $p = ProductoPresentacion::factory()->for(Tamano::factory()->create(['nombre' => $tamano]), 'tamano')->create();
            $pedido->detalles()->create(['presentacion_id' => $p->id, 'cantidad' => $cantidad, 'precio_unitario' => 1000, 'subtotal' => 1000 * $cantidad]);
        }

        $r = app(MetricasAdmin::class)->resumen($mes);

        $this->assertSame(5, $r['decants_mes']);
        $this->assertSame(60, $r['porcentaje_5ml']);
    }

    private function pedido(string $fecha, string $confirmacion, int $total, string $estado = 'confirmado'): Pedido
    {
        $p = Pedido::create(['fecha' => $fecha, 'estado' => $estado, 'total' => $total]);
        $p->estados()->create(['estado' => 'confirmado', 'created_at' => $confirmacion]);

        return $p;
    }

    private function usuario(string $rol = 'administrador'): Usuario
    {
        return Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']);
    }
}
