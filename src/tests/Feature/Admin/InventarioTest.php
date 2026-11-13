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

    public function test_filtros_entradas_salidas_y_perfume(): void
    {
        $a = ProductoPresentacion::factory()->create(['stock' => 1]);
        $b = ProductoPresentacion::factory()->create();
        $s = app(Inventario::class);
        $s->registrar($a, 'entrada', 1, 'Entrada crítica');
        $s->registrar($b, 'salida', 1, 'Salida normal');
        $this->admin();
        $this->get('/admin/inventario?filtro=entrada')->assertOk()->assertSeeText('Entrada crítica')->assertDontSeeText('Salida normal');
        $this->get('/admin/inventario?filtro=salida')->assertOk()->assertSeeText('Salida normal')->assertDontSeeText('Entrada crítica');
        $this->get('/admin/inventario?producto_id='.$b->producto_id)->assertOk()->assertSeeText('Salida normal')->assertDontSeeText('Entrada crítica');
    }

    public function test_stock_critico_muestra_presentaciones_sin_movimientos(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 0]);
        $this->admin();

        $this->get('/admin/inventario?filtro=critico')->assertOk()
            ->assertSeeText([$p->producto->nombre, $p->tamano->nombre, 'Stock actual', 'Registrar entrada'])
            ->assertSee('<td class="admin-low">0</td>', false)
            ->assertDontSeeText('No hay movimientos');
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_stock_critico_excluye_stock_mayor_a_tres_e_inactivas(): void
    {
        ProductoPresentacion::factory()->create(['stock' => 4]);
        ProductoPresentacion::factory()->create(['stock' => 0, 'activo' => false]);
        $this->admin();

        $this->get('/admin/inventario?filtro=critico')->assertOk()
            ->assertSeeText('No hay presentaciones con stock crítico.')
            ->assertDontSeeText('Registrar entrada');
    }

    public function test_stock_critico_respeta_el_filtro_de_perfume(): void
    {
        $a = ProductoPresentacion::factory()->create(['stock' => 3]);
        $b = ProductoPresentacion::factory()->create(['stock' => 0]);
        $this->admin();

        $this->get('/admin/inventario?filtro=critico&producto_id='.$a->producto_id)->assertOk()
            ->assertSeeText('Registrar entrada')
            ->assertSee(route('admin.inventario.create', ['presentacion_id' => $a->id, 'tipo' => 'entrada']))
            ->assertDontSee(route('admin.inventario.create', ['presentacion_id' => $b->id, 'tipo' => 'entrada']));
    }

    public function test_registrar_entrada_desde_stock_critico_preselecciona_presentacion_y_tipo(): void
    {
        ProductoPresentacion::factory()->create();
        $p = ProductoPresentacion::factory()->create(['stock' => 0]);
        $this->admin();
        $url = route('admin.inventario.create', ['presentacion_id' => $p->id, 'tipo' => 'entrada']);

        $this->get('/admin/inventario?filtro=critico')->assertOk()->assertSee($url);
        $respuesta = $this->get($url)->assertOk();
        $this->assertMatchesRegularExpression('/<option value="'.$p->id.'"\s+selected[^>]*>/', $respuesta->getContent());
        $this->assertMatchesRegularExpression('/<option value="entrada"\s+selected[^>]*>/', $respuesta->getContent());
    }

    #[TestWith(['presentacion_id', 'inexistente'])]
    #[TestWith(['presentacion_id', 999999])]
    #[TestWith(['presentacion_id', ['1']])]
    #[TestWith(['tipo', 'otro'])]
    #[TestWith(['tipo', ['entrada']])]
    public function test_preseleccion_invalida_se_rechaza(string $campo, mixed $valor): void
    {
        $this->admin();

        $this->get('/admin/inventario/create?'.http_build_query([$campo => $valor]))
            ->assertSessionHasErrors($campo);
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
        $this->get('/admin/inventario')->assertRedirect('/admin/login');
        $this->get('/admin/inventario/create')->assertRedirect('/admin/login');
        $this->post('/admin/inventario', [])->assertRedirect('/admin/login');
        $this->admin('cliente');
        $this->get('/admin/inventario')->assertForbidden();
        $this->post('/admin/inventario', [])->assertForbidden();
    }

    #[TestWith([2, 8, 'entrada', 6])]
    #[TestWith([3, 1, 'salida', 2])]
    #[TestWith([2, 0, 'salida', 2])]
    public function test_ajuste_registra_diferencia_stock_y_usuario(int $stock, int $nuevo, string $tipo, int $cantidad): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => $stock]);
        $u = $this->admin();

        $this->post(route('admin.inventario.ajustar', $p), ['stock_nuevo' => $nuevo, 'motivo' => 'Recuento físico', 'producto_id' => $p->producto_id])
            ->assertRedirect(route('admin.inventario.index', ['filtro' => 'critico', 'producto_id' => $p->producto_id]))
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Stock ajustado.');

        $this->assertSame($nuevo, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseHas('movimientos_inventario', [
            'presentacion_id' => $p->id, 'tipo' => $tipo, 'cantidad' => $cantidad,
            'stock_resultante' => $nuevo, 'usuario_id' => $u->id, 'motivo' => 'Recuento físico',
        ]);
    }

    public function test_ajuste_usa_motivo_por_defecto_y_sale_de_criticos(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 0]);
        $this->admin();

        $this->post(route('admin.inventario.ajustar', $p), ['stock_nuevo' => 5])
            ->assertRedirect(route('admin.inventario.index', ['filtro' => 'critico']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $p->id, 'motivo' => 'Ajuste manual de stock']);
        $this->get('/admin/inventario?filtro=critico')->assertOk()
            ->assertSeeText('No hay presentaciones con stock crítico.');
    }

    public function test_ajuste_sin_cambio_rechaza_sin_crear_movimiento(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $this->admin();

        $this->from('/admin/inventario?filtro=critico')->post(route('admin.inventario.ajustar', $p), ['stock_nuevo' => 2])
            ->assertRedirect('/admin/inventario?filtro=critico')
            ->assertSessionHasErrors(['stock_nuevo' => 'El stock ya es 2.']);

        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith(['stock_nuevo', null])]
    #[TestWith(['stock_nuevo', -1])]
    #[TestWith(['stock_nuevo', 1.5])]
    #[TestWith(['stock_nuevo', 'texto'])]
    #[TestWith(['stock_nuevo', 100001])]
    #[TestWith(['stock_nuevo', ['2']])]
    #[TestWith(['motivo', ['texto']])]
    public function test_ajuste_invalido_no_modifica_stock(string $campo, mixed $valor): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $this->admin();
        $datos = ['stock_nuevo' => 3, 'motivo' => 'Recuento'];
        $datos[$campo] = $valor;

        $this->post(route('admin.inventario.ajustar', $p), $datos)->assertSessionHasErrors($campo);

        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_ajuste_rechaza_motivo_demasiado_largo(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $this->admin();

        $this->post(route('admin.inventario.ajustar', $p), ['stock_nuevo' => 3, 'motivo' => str_repeat('a', 256)])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_ajuste_exige_administrador(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $url = route('admin.inventario.ajustar', $p);

        $this->post($url, ['stock_nuevo' => 5])->assertRedirect('/admin/login');
        $this->admin('cliente');
        $this->post($url, ['stock_nuevo' => 5])->assertForbidden();

        $this->assertSame(2, $p->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_stock_critico_muestra_formulario_de_ajuste_por_fila(): void
    {
        $p = ProductoPresentacion::factory()->create(['stock' => 2]);
        $this->admin();

        $this->get('/admin/inventario?filtro=critico&producto_id='.$p->producto_id)->assertOk()
            ->assertSee('action="'.route('admin.inventario.ajustar', $p).'"', false)
            ->assertSee('name="stock_nuevo"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="producto_id" value="'.$p->producto_id.'"', false)
            ->assertSeeText(['Stock nuevo', 'Motivo (opcional)', 'Guardar', 'Registrar entrada']);
    }

    private function admin(string $rol = 'administrador'): Usuario
    {
        $u = Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']);
        $this->actingAs($u);

        return $u;
    }
}
