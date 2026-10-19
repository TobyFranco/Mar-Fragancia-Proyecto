<?php

namespace Tests\Feature\Admin;

use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Inventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_muestra_movimientos_con_stock_resultante_y_usuario(): void
    {
        $p = ProductoPresentacion::factory()->create();
        app(Inventario::class)->registrar($p, 'entrada', 3, 'Reposición de prueba');
        app(Inventario::class)->registrar($p, 'salida', 1, 'Ajuste de prueba', $this->admin());
        $this->get('/admin/inventario')->assertOk()->assertSeeText(['Sistema', 'Paz', 'Reposición de prueba', 'Ajuste de prueba', '13', '12', $p->producto->nombre, $p->tamano->nombre]);
    }

    public function test_filtros_entradas_salidas_criticos_y_perfume(): void
    {
        $a = ProductoPresentacion::factory()->create(['stock' => 1]);
        $b = ProductoPresentacion::factory()->create();
        $s = app(Inventario::class);
        $s->registrar($a, 'entrada', 1, 'Entrada crítica');
        $s->registrar($b, 'salida', 1, 'Salida normal');
        $this->admin();
        $this->get('/admin/inventario?filtro=entrada')->assertOk()->assertSeeText('Entrada crítica')->assertDontSeeText('Salida normal');
        $this->get('/admin/inventario?filtro=salida')->assertOk()->assertSeeText('Salida normal')->assertDontSeeText('Entrada crítica');
        $this->get('/admin/inventario?filtro=critico')->assertOk()->assertSeeText('Entrada crítica')->assertDontSeeText('Salida normal');
        $this->get('/admin/inventario?producto_id='.$b->producto_id)->assertOk()->assertSeeText('Salida normal')->assertDontSeeText('Entrada crítica');
    }

    public function test_registrar_movimiento_manual(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $u = $this->admin();
        $this->get('/admin/inventario/create')->assertOk()->assertSeeText(['Registrar movimiento', $p->producto->nombre]);
        $this->post('/admin/inventario', ['presentacion_id' => $p->id, 'tipo' => 'entrada', 'cantidad' => 4, 'motivo' => 'Reposición'])->assertRedirect('/admin/inventario')->assertSessionHasNoErrors();
        $this->assertSame(6, $p->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $p->id, 'stock_resultante' => 6, 'usuario_id' => $u->id, 'motivo' => 'Reposición']);
    }

    public function test_movimiento_manual_que_deja_stock_negativo_se_rechaza(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $this->admin();
        $this->from('/admin/inventario/create')->post('/admin/inventario', ['presentacion_id' => $p->id, 'tipo' => 'salida', 'cantidad' => 3, 'motivo' => 'Ajuste'])->assertRedirect('/admin/inventario/create')->assertSessionHasErrors('cantidad');
        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith(['cantidad', 0])]
    #[TestWith(['cantidad', -1])]
    #[TestWith(['cantidad', 'texto'])]
    #[TestWith(['cantidad', 1.5])]
    #[TestWith(['motivo', ''])]
    #[TestWith(['tipo', 'otro'])]
    public function test_valida_campos_sin_modificar_stock(string $campo, mixed $valor): void
    {
        $p = ProductoPresentacion::factory()->create();
        $this->admin();
        $datos = ['presentacion_id' => $p->id, 'tipo' => 'entrada', 'cantidad' => 1, 'motivo' => 'Reposición'];
        $datos[$campo] = $valor;
        $this->post('/admin/inventario', $datos)->assertSessionHasErrors($campo);
        $this->assertSame(10, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_exige_admin_y_conserva_auditoria_historica_nullable(): void
    {
        $this->get('/admin/inventario')->assertRedirect('/login');
        $this->get('/admin/inventario/create')->assertRedirect('/login');
        $this->post('/admin/inventario', [])->assertRedirect('/login');
        $this->admin('cliente');
        $this->get('/admin/inventario')->assertForbidden();
        $this->post('/admin/inventario', [])->assertForbidden();
    }

    private function admin(string $rol = 'administrador'): Usuario
    {
        $u = Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']);
        $this->actingAs($u);

        return $u;
    }
}
