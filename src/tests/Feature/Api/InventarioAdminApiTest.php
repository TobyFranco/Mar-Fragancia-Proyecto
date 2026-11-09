<?php

namespace Tests\Feature\Api;

use App\Models\ProductoPresentacion;
use PHPUnit\Framework\Attributes\TestWith;

class InventarioAdminApiTest extends ApiTestCase
{
    public function test_stock_critico_comparte_criterio_y_exige_admin(): void
    {
        $critica = ProductoPresentacion::factory()->create(['stock' => 3]);
        ProductoPresentacion::factory()->create(['stock' => 4]);
        ProductoPresentacion::factory()->create(['stock' => 1, 'activo' => false]);

        $this->getJson('/api/v1/admin/inventario/stock-critico')->assertUnauthorized();
        $this->bearer($this->usuario())->getJson('/api/v1/admin/inventario/stock-critico')->assertForbidden();
        $this->bearer($this->usuario('administrador'))->getJson('/api/v1/admin/inventario/stock-critico')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $critica->id)
            ->assertJsonPath('data.0.producto.nombre', $critica->producto->nombre)
            ->assertJsonStructure(['links', 'meta']);
    }

    public function test_registra_entrada_y_salida_con_auditoria(): void
    {
        $presentacion = ProductoPresentacion::factory()->create(['stock' => 2]);
        $admin = $this->usuario('administrador');
        $datos = ['presentacion_id' => $presentacion->id, 'tipo' => 'entrada', 'cantidad' => 3, 'motivo' => 'Reposición'];

        $this->bearer($admin)->postJson('/api/v1/admin/inventario/movimientos', $datos)->assertCreated()->assertJsonPath('data.stock_resultante', 5);
        $this->assertSame(5, $presentacion->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $presentacion->id, 'usuario_id' => $admin->id, 'cantidad' => 3, 'stock_resultante' => 5]);

        $this->postJson('/api/v1/admin/inventario/movimientos', array_merge($datos, ['tipo' => 'salida', 'cantidad' => 2]))->assertCreated()->assertJsonPath('data.stock_resultante', 3);
        $this->postJson('/api/v1/admin/inventario/movimientos', array_merge($datos, ['tipo' => 'salida', 'cantidad' => 4]))->assertUnprocessable()->assertJsonValidationErrors('cantidad');

        $this->assertSame(3, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    #[TestWith(['cantidad', 0])]
    #[TestWith(['cantidad', 1.5])]
    #[TestWith(['tipo', 'ajuste'])]
    #[TestWith(['motivo', ''])]
    #[TestWith(['presentacion_id', 99999])]
    public function test_validaciones_no_escriben_kardex(string $campo, mixed $valor): void
    {
        $presentacion = ProductoPresentacion::factory()->create();
        $datos = ['presentacion_id' => $presentacion->id, 'tipo' => 'entrada', 'cantidad' => 1, 'motivo' => 'Ajuste'];
        $datos[$campo] = $valor;

        $this->bearer($this->usuario('administrador'))->postJson('/api/v1/admin/inventario/movimientos', $datos)->assertUnprocessable()->assertJsonValidationErrors($campo);

        $this->assertSame(10, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_rechaza_stock_resultante_fuera_de_rango_sin_escribir(): void
    {
        $presentacion = ProductoPresentacion::factory()->create();

        $this->bearer($this->usuario('administrador'))
            ->postJson('/api/v1/admin/inventario/movimientos', ['presentacion_id' => $presentacion->id, 'tipo' => 'entrada', 'cantidad' => 2147483647, 'motivo' => 'Exceso'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cantidad');

        $this->assertSame(10, $presentacion->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }
}
