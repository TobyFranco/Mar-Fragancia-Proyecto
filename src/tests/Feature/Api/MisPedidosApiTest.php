<?php

namespace Tests\Feature\Api;

use App\Models\ProductoPresentacion;

class MisPedidosApiTest extends ApiTestCase
{
    public function test_lista_solo_propios_del_mas_nuevo_al_mas_viejo(): void
    {
        $u = $this->usuario();
        $a = $this->pedido($u, ['fecha' => now()->subDay()]);
        $b = $this->pedido($u);
        $otro = $this->pedido($this->usuario());
        $this->bearer($u)->getJson('/api/v1/mis-pedidos?cliente_id='.$otro->cliente_id)->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $b->id)->assertJsonPath('data.1.id', $a->id)->assertJsonStructure(['links', 'meta']);
    }

    public function test_detalle_tiene_items_historial_y_rechaza_ajeno(): void
    {
        $u = $this->usuario();
        $p = $this->pedido($u);
        $pres = ProductoPresentacion::factory()->create();
        $p->detalles()->create(['presentacion_id' => $pres->id, 'cantidad' => 2, 'precio_unitario' => 30000, 'subtotal' => 60000]);
        $p->estados()->create(['estado' => 'pendiente', 'created_at' => now()]);
        $this->bearer($u)->getJson('/api/v1/mis-pedidos/'.$p->id)->assertOk()->assertJsonPath('data.items.0.cantidad', 2)->assertJsonPath('data.historial.0.estado', 'pendiente');
        $this->bearer($this->usuario())->get('/api/v1/mis-pedidos/'.$p->id)->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_sin_perfil_no_ve_pedidos_y_requiere_token(): void
    {
        $p = $this->pedido($this->usuario());
        $this->getJson('/api/v1/mis-pedidos')->assertUnauthorized();
        $this->bearer($this->usuario())->getJson('/api/v1/mis-pedidos')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/mis-pedidos/'.$p->id)->assertNotFound();
    }
}
