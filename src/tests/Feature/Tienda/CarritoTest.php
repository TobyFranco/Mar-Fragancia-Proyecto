<?php

namespace Tests\Feature\Tienda;

use App\Models\Nota;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use App\Services\Carrito;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CarritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_agrega_y_suma_cantidades_y_muestra_confirmacion(): void
    {
        $p = $this->presentacion();
        $this->from('/perfumes/sauvage')->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1])->assertRedirect('/perfumes/sauvage');
        $this->from('/perfumes/sauvage')->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1])
            ->assertSessionHas('carrito.'.$p->id, 2)->assertSessionHas('carrito_ultimo', $p->producto_id)->assertSessionHas('carrito_abierto', true);
        $this->get('/carrito')->assertOk()->assertSeeText(['Agregaste Sauvage · Decant 10 ml', '₲ 120.000']);
        $this->assertSame(2, app(Carrito::class)->unidades());
        $this->assertSame(120000, app(Carrito::class)->subtotal());
        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_no_supera_el_stock(): void
    {
        $p = $this->presentacion(['stock' => 3]);
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 5])
            ->assertSessionHas('carrito.'.$p->id, 3)->assertSessionHas('aviso_carrito', 'Solo quedan 3 de Sauvage · Decant 10 ml.');
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 2])->assertSessionHas('carrito.'.$p->id, 3);
    }

    #[TestWith([0])]
    #[TestWith([-2])]
    #[TestWith(['abc'])]
    #[TestWith([1.5])]
    #[TestWith([['1']])]
    public function test_no_acepta_cantidades_invalidas(mixed $cantidad): void
    {
        $p = $this->presentacion();
        $this->withSession(['carrito' => [$p->id => 1]])->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => $cantidad])
            ->assertSessionHasErrors('cantidad')->assertSessionHas('carrito', [$p->id => 1]);
    }

    #[TestWith(['stock', 0])]
    #[TestWith(['activo', false])]
    public function test_no_agrega_presentacion_sin_stock_o_inactiva(string $campo, mixed $valor): void
    {
        $p = $this->presentacion([$campo => $valor]);
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1])->assertSessionHasErrors('carrito')->assertSessionMissing('carrito');
    }

    public function test_no_agrega_producto_inactivo_ni_tamano_fuera_de_catalogo(): void
    {
        $p = $this->presentacion();
        $p->producto->update(['activo' => false]);
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1])->assertSessionHasErrors('carrito')->assertSessionMissing('carrito');
        $p->producto->update(['activo' => true]);
        $p->tamano->update(['nombre' => '15 ml']);
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1])->assertSessionHasErrors('carrito')->assertSessionMissing('carrito');
    }

    public function test_cantidad_muy_grande_y_actualizacion_respetan_stock(): void
    {
        $p = $this->presentacion(['stock' => 3]);
        $this->withSession(['carrito' => [$p->id => 2]])->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => PHP_INT_MAX])
            ->assertRedirect()->assertSessionHas('carrito.'.$p->id, 3)->assertSessionHas('aviso_carrito');
        $this->patch('/carrito/'.$p->id, ['cantidad' => 9])->assertSessionHas('carrito.'.$p->id, 3)->assertSessionHas('aviso_carrito');
    }

    public function test_actualizar_a_cero_elimina_la_linea(): void
    {
        $p = $this->presentacion();
        $this->withSession(['carrito' => [$p->id => 1]])->patch('/carrito/'.$p->id, ['cantidad' => 2])->assertSessionHas('carrito.'.$p->id, 2);
        $this->patch('/carrito/'.$p->id, ['cantidad' => -1])->assertSessionHasErrors('cantidad')->assertSessionHas('carrito.'.$p->id, 2);
        $this->patch('/carrito/'.$p->id, ['cantidad' => 0])->assertSessionHas('carrito', []);
    }

    public function test_elimina_sin_depender_de_que_la_presentacion_siga_existiendo(): void
    {
        $this->withSession(['carrito' => [999 => 2]])->delete('/carrito/999')->assertRedirect()->assertSessionHas('carrito', []);
    }

    public function test_muestra_recomendados_con_notas_parecidas(): void
    {
        $p = $this->presentacion();
        $nota = Nota::factory()->create(['nombre' => 'bergamota']);
        $p->producto->notas()->attach($nota);
        foreach (range(1, 4) as $n) {
            $producto = Producto::factory()->create(['nombre' => 'Parecido '.$n]);
            $producto->notas()->attach($nota);
            ProductoPresentacion::factory()->for($producto, 'producto')->for(Tamano::factory()->create(['nombre' => '5 ml']), 'tamano')->create();
        }
        $this->post('/carrito', ['presentacion_id' => $p->id, 'cantidad' => 1]);
        $respuesta = $this->get('/carrito')->assertOk()->assertSeeText(['Con notas parecidas a Sauvage', '100% parecido', 'bergamota', '5 ml']);
        $this->assertCount(3, $respuesta->viewData('recomendados'));
        $this->assertSame(['Parecido 1', 'Parecido 2', 'Parecido 3'], $respuesta->viewData('recomendados')->pluck('producto.nombre')->all());
    }

    public function test_subtotal_de_una_unidad_usa_singular(): void
    {
        $p = $this->presentacion();
        $this->withSession(['carrito' => [$p->id => 1]])->get('/carrito')->assertOk()->assertSeeText('Subtotal (1 unidad)')->assertDontSeeText('1 unidades');
    }

    public function test_carrito_vacio_muestra_estado_vacio(): void
    {
        $this->get('/carrito')->assertOk()->assertSeeText(['Tu carrito está vacío', 'Ver los más probados']);
    }

    public function test_contador_del_encabezado_muestra_unidades(): void
    {
        $p = $this->presentacion();
        $this->withSession(['carrito' => [$p->id => 3]])->get('/')->assertOk()->assertSee('Carrito, 3 unidades');
    }

    private function presentacion(array $atributos = []): ProductoPresentacion
    {
        return ProductoPresentacion::factory()->for(Producto::factory()->create(['nombre' => 'Sauvage']), 'producto')
            ->for(Tamano::factory()->create(['nombre' => '10 ml']), 'tamano')->create(['precio' => 60000, ...$atributos]);
    }
}
