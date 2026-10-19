<?php

namespace Tests\Feature\Admin;

use App\Models\Pedido;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Inventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FechasLocalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplicacion_usa_reloj_de_asuncion(): void
    {
        $this->assertSame('America/Asuncion', config('app.timezone'));
        $this->assertSame('America/Asuncion', now()->timezoneName);
    }

    public function test_fecha_guardada_se_muestra_en_asuncion_en_pedido_y_kardex(): void
    {
        $this->usarAsuncion();
        $fecha = Carbon::parse('2026-10-19 04:26:00', 'UTC');
        $pedido = Pedido::create(['fecha' => $fecha, 'estado' => 'pendiente', 'total' => 0, 'subtotal' => 0, 'costo_envio' => 0]);
        $pedido->estados()->create(['estado' => 'pendiente', 'created_at' => $fecha]);
        $pres = ProductoPresentacion::factory()->create();
        $pres->movimientos()->create(['tipo' => 'entrada', 'cantidad' => 1, 'fecha' => $fecha, 'motivo' => 'Registro histórico', 'stock_resultante' => 10]);
        $this->actingAs(Usuario::create(['rol_id' => Rol::create(['nombre' => 'administrador'])->id, 'nombre' => 'Paz', 'email' => 'paz@example.com', 'password' => 'no-se-usa']));
        $this->get('/admin/pedidos/'.$pedido->id)->assertOk()->assertSeeText('19/10/2026 01:26')->assertDontSeeText('19/10/2026 04:26');
        $this->get('/admin/inventario')->assertOk()->assertSeeText('19/10/2026 01:26')->assertDontSeeText('19/10/2026 04:26');
        $this->assertDatabaseHas('pedido_estados', ['pedido_id' => $pedido->id, 'created_at' => '2026-10-19 04:26:00']);
    }

    public function test_nuevas_fechas_se_persisten_en_utc_y_conservan_el_instante(): void
    {
        $this->usarAsuncion();
        $this->travelTo(Carbon::parse('2026-10-19 01:26:00', 'America/Asuncion'));
        $p = ProductoPresentacion::factory()->create();
        $m = app(Inventario::class)->registrar($p, 'entrada', 1, 'Reposición');
        $pedido = Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 0]);
        $estado = $pedido->estados()->create(['estado' => 'pendiente', 'created_at' => now()]);
        $this->assertDatabaseHas('movimientos_inventario', ['id' => $m->id, 'fecha' => '2026-10-19 04:26:00', 'created_at' => '2026-10-19 04:26:00']);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'fecha' => '2026-10-19 04:26:00']);
        $this->assertDatabaseHas('pedido_estados', ['id' => $estado->id, 'created_at' => '2026-10-19 04:26:00']);
        $this->assertTrue($estado->fresh()->created_at->equalTo(now()));
    }

    private function usarAsuncion(): void
    {
        $anterior = date_default_timezone_get();
        $this->beforeApplicationDestroyed(fn () => date_default_timezone_set($anterior));
        config(['app.timezone' => 'America/Asuncion']);
        date_default_timezone_set('America/Asuncion');
    }
}
