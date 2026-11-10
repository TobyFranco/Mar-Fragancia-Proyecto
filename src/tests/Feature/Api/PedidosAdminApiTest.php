<?php

namespace Tests\Feature\Api;

use App\Models\ProductoPresentacion;

class PedidosAdminApiTest extends ApiTestCase
{
    public function test_admin_requiere_token_y_rol(): void
    {
        $p = $this->pedido($this->usuario());
        $this->getJson('/api/v1/admin/pedidos')->assertUnauthorized();
        $this->bearer($this->usuario())->get('/api/v1/admin/pedidos')->assertForbidden()->assertHeader('Content-Type', 'application/json');
        $this->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertForbidden();
    }

    public function test_filtra_estado_y_detalle_completo(): void
    {
        $u = $this->usuario();
        $p = $this->pedido($u, ['nombre_contacto' => 'Ana']);
        $this->pedido($u, ['estado' => 'cancelado']);
        $this->bearer($this->usuario('administrador'))->getJson('/api/v1/admin/pedidos?estado=pendiente')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $p->id);
        $this->getJson('/api/v1/admin/pedidos/'.$p->id)->assertOk()->assertJsonPath('data.contacto.nombre', 'Ana')->assertJsonStructure(['data' => ['items', 'historial', 'entrega']]);
        $this->getJson('/api/v1/admin/pedidos?estado=incorrecto')->assertUnprocessable();
    }

    public function test_confirma_descontando_stock_una_sola_vez_y_cancelar_devuelve(): void
    {
        $p = $this->pedido($this->usuario());
        $a = ProductoPresentacion::factory()->create(['stock' => 4]);
        $p->detalles()->create(['presentacion_id' => $a->id, 'cantidad' => 2, 'precio_unitario' => 30000, 'subtotal' => 60000]);
        $this->bearer($this->usuario('administrador'))->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertOk()->assertJsonPath('data.estado', 'confirmado');
        $this->assertSame(2, $a->fresh()->stock);
        $this->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'cancelado'])->assertOk();
        $this->assertSame(4, $a->fresh()->stock);
        $this->assertDatabaseCount('pedido_estados', 2);
    }

    public function test_sin_stock_o_transicion_invalida_devuelve422_sin_cambios(): void
    {
        $p = $this->pedido($this->usuario());
        $a = ProductoPresentacion::factory()->create(['stock' => 1]);
        $p->detalles()->create(['presentacion_id' => $a->id, 'cantidad' => 2, 'precio_unitario' => 30000, 'subtotal' => 60000]);
        $this->bearer($this->usuario('administrador'))->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'confirmado'])->assertUnprocessable()->assertJsonValidationErrors('estado');
        $this->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', ['estado' => 'enviado'])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/pedidos/'.$p->id.'/estado', [])->assertUnprocessable();
        $this->assertSame('pendiente', $p->fresh()->estado);
        $this->assertSame(1, $a->fresh()->stock);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }
}
