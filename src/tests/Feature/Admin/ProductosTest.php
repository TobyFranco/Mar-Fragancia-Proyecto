<?php

namespace Tests\Feature\Admin;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Nota;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use App\Services\Inventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_muestra_stock_de_5_y_10_ml_y_marca_criticos(): void
    {
        $producto = Producto::factory()->create(['nombre' => 'Perfume de prueba', 'genero' => 'mujer']);
        ProductoPresentacion::factory()->for($producto)->for(Tamano::factory()->create(['nombre' => '5 ml']), 'tamano')->create(['stock' => 3]);
        ProductoPresentacion::factory()->for($producto)->for(Tamano::factory()->create(['nombre' => '10 ml']), 'tamano')->create(['stock' => 8]);

        $this->actingAs($this->admin())->get('/admin/productos')->assertOk()->assertSeeText(['Perfume de prueba', 'Mujer', '5 ml', '10 ml', '3', '8'])->assertSee('class="admin-low"', false)
            ->assertSee('href="'.route('admin.productos.edit', $producto->id).'"', false);
    }

    #[TestWith(['mujer'])]
    #[TestWith(['hombre'])]
    #[TestWith(['unisex'])]
    #[TestWith(['sin-stock'])]
    #[TestWith(['inactivos'])]
    #[TestWith(['busqueda'])]
    public function test_filtros_genero_sin_stock_inactivos_y_busqueda(string $filtro): void
    {
        $a = Producto::factory()->create(['nombre' => 'Único especial', 'genero' => in_array($filtro, Producto::GENEROS) ? $filtro : 'mujer', 'activo' => $filtro !== 'inactivos']);
        $b = Producto::factory()->create(['nombre' => 'Otro perfume', 'genero' => $filtro === 'hombre' ? 'mujer' : 'hombre']);
        ProductoPresentacion::factory()->for($a)->create(['stock' => $filtro === 'sin-stock' ? 0 : 5]);
        ProductoPresentacion::factory()->for($b)->create(['stock' => 5]);
        $query = $filtro === 'busqueda' ? 'q=unico' : 'filtro='.$filtro;

        $this->actingAs($this->admin())->get('/admin/productos?'.$query)->assertOk()->assertSeeText('Único especial')->assertDontSeeText('Otro perfume');
    }

    public function test_crear_perfume_con_genero_notas_y_precios(): void
    {
        $nota = Nota::factory()->create(['nombre' => 'Vainilla']);
        $datos = $this->datos();
        $datos['notas'] = ['Vainilla'];
        $datos['notas_nuevas'] = 'vainilla, Bergamota, Bergamota';
        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertRedirect('/admin/productos')->assertSessionHasNoErrors();

        $p = Producto::where('nombre', 'Nuevo perfume')->sole();
        $this->assertSame('mujer', $p->genero);
        $this->assertTrue($p->destacado);
        $this->assertTrue($p->activo);
        $this->assertSame(2, $p->notas()->count());
        $this->assertTrue($p->notas->contains($nota));
        $this->assertDatabaseCount('notas', 2);
        $this->assertDatabaseHas('producto_presentacion', ['producto_id' => $p->id, 'precio' => 0, 'stock' => 0, 'activo' => true]);
        $this->assertDatabaseHas('producto_presentacion', ['producto_id' => $p->id, 'precio' => 60000]);
    }

    public function test_stock_inicial_se_registra_como_entrada(): void
    {
        $datos = $this->datos();
        $cinco = array_key_first($datos['presentaciones']);
        $datos['presentaciones'][$cinco]['stock_inicial'] = 7;
        $datos['presentaciones'][$cinco]['stock'] = 999;
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/productos', $datos)->assertSessionHasNoErrors();

        $p = Producto::where('nombre', 'Nuevo perfume')->sole();
        $pres = $p->presentaciones()->where('tamano_id', $cinco)->sole();
        $this->assertSame(7, $pres->stock);
        $this->assertDatabaseCount('movimientos_inventario', 1);
        $this->assertDatabaseHas('movimientos_inventario', ['presentacion_id' => $pres->id, 'tipo' => 'entrada', 'cantidad' => 7, 'stock_resultante' => 7, 'motivo' => 'Stock inicial', 'usuario_id' => $admin->id]);
    }

    public function test_editar_producto_ignora_stock_enviado(): void
    {
        $p = Producto::factory()->create();
        $datos = $this->datos();
        $cinco = array_key_first($datos['presentaciones']);
        $pres = ProductoPresentacion::factory()->for($p)->create(['tamano_id' => $cinco, 'stock' => 9]);
        $datos['stock'] = 999;
        $datos['presentaciones'][$cinco]['stock'] = 999;
        $datos['presentaciones'][$cinco]['stock_inicial'] = 999;

        $this->actingAs($this->admin())->put('/admin/productos/'.$p->id, $datos)->assertRedirect('/admin/productos')->assertSessionHasNoErrors();

        $this->assertSame(9, $pres->fresh()->stock);
        $this->assertSame('0.00', $pres->fresh()->precio);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->get('/admin/productos/'.$p->id.'/edit')->assertOk()->assertSeeText(['El stock se cambia desde Inventario', '9'])->assertDontSee('name="presentaciones['.$cinco.'][stock]"', false)->assertDontSee('[stock_inicial]', false);
    }

    #[TestWith(['nombre', null])]
    #[TestWith(['marca_id', null])]
    #[TestWith(['categoria_id', null])]
    #[TestWith(['genero', null])]
    #[TestWith(['genero', 'otro'])]
    #[TestWith(['presentaciones', []])]
    public function test_validaciones(string $campo, mixed $valor): void
    {
        $datos = $this->datos();
        $datos[$campo] = $valor;
        $this->actingAs($this->admin())->from('/admin/productos/create')->post('/admin/productos', $datos)->assertRedirect('/admin/productos/create')->assertSessionHasErrors($campo);
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[TestWith([-1])]
    #[TestWith(['texto'])]
    public function test_precio_invalido_se_rechaza(mixed $precio): void
    {
        $datos = $this->datos();
        $id = array_key_first($datos['presentaciones']);
        $datos['presentaciones'][$id]['precio'] = $precio;
        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertSessionHasErrors('presentaciones.'.$id.'.precio');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_al_menos_un_tamano_con_precio_y_solo_5_o_10ml(): void
    {
        $datos = $this->datos();
        foreach ($datos['presentaciones'] as &$fila) {
            $fila['precio'] = '';
        }
        unset($fila);
        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertSessionHasErrors('presentaciones');
        $datos = $this->datos();
        $quince = Tamano::factory()->create(['nombre' => '15 ml']);
        $datos['presentaciones'][$quince->id] = ['precio' => 50000, 'stock_inicial' => 5];
        $this->post('/admin/productos', $datos)->assertSessionHasErrors('presentaciones');
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_no_elimina_producto_con_pedidos(): void
    {
        $pres = ProductoPresentacion::factory()->create();
        $p = $pres->producto;
        $pedido = Pedido::create(['fecha' => now(), 'estado' => 'pendiente', 'total' => 30000]);
        $pedido->detalles()->create(['presentacion_id' => $pres->id, 'cantidad' => 1, 'precio_unitario' => 30000, 'subtotal' => 30000]);
        $this->actingAs($this->admin())->delete('/admin/productos/'.$p->id)->assertRedirect('/admin/productos/'.$p->id.'/edit')->assertSessionHasErrors('producto');
        $this->assertModelExists($p);
        $this->assertModelExists($pres);
        $this->assertDatabaseCount('detalle_pedidos', 1);
        $this->get('/admin/productos/'.$p->id.'/edit')->assertOk()->assertSeeText('Visible en la tienda');
    }

    public function test_no_elimina_producto_con_movimientos(): void
    {
        $pres = ProductoPresentacion::factory()->create();
        $p = $pres->producto;
        $movimiento = app(Inventario::class)->registrar($pres, 'entrada', 1, 'Reposición');

        $this->actingAs($this->admin())->delete('/admin/productos/'.$p->id)->assertRedirect('/admin/productos/'.$p->id.'/edit')
            ->assertSessionHasErrors(['producto' => 'Este perfume tiene movimientos de inventario y no se puede eliminar. Desmarcá Visible en la tienda para ocultarlo.']);

        $this->assertModelExists($p);
        $this->assertModelExists($pres);
        $this->assertModelExists($movimiento);
        $this->assertSame(11, $pres->fresh()->stock);
        $this->assertTrue($p->fresh()->activo);
    }

    public function test_elimina_producto_sin_pedidos(): void
    {
        $pres = ProductoPresentacion::factory()->create();
        $p = $pres->producto;
        $p->notas()->attach(Nota::factory()->create());

        $this->actingAs($this->admin())->delete('/admin/productos/'.$p->id)->assertRedirect('/admin/productos');

        $this->assertModelMissing($p);
        $this->assertModelMissing($pres);
        $this->assertDatabaseCount('nota_producto', 0);
    }

    public function test_foto_se_guarda_y_se_conserva_al_editar(): void
    {
        Storage::fake('public');
        $datos = [...$this->datos(), 'imagen' => UploadedFile::fake()->image('perfume.jpg')];
        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertSessionHasNoErrors();
        $p = Producto::where('nombre', 'Nuevo perfume')->sole();
        Storage::disk('public')->assertExists($p->imagen);
        unset($datos['imagen']);
        $datos['nombre'] = 'Otro nombre';
        $this->put('/admin/productos/'.$p->id, $datos)->assertSessionHasNoErrors();
        $this->assertSame($p->imagen, $p->fresh()->imagen);
    }

    public function test_cliente_no_puede_crear_editar_ni_eliminar(): void
    {
        $p = Producto::factory()->create();
        $this->actingAs($this->admin('cliente'))->post('/admin/productos', $this->datos())->assertForbidden();
        $this->put('/admin/productos/'.$p->id, [])->assertForbidden();
        $this->delete('/admin/productos/'.$p->id)->assertForbidden();
        $this->assertModelExists($p);
    }

    public function test_foto_invalida_muestra_error_en_espanol(): void
    {
        $datos = [...$this->datos(), 'imagen' => UploadedFile::fake()->create('texto.txt', 1, 'text/plain')];

        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertSessionHasErrors(['imagen' => 'Elegí una imagen válida.']);
        $this->assertDatabaseCount('productos', 0);
    }

    public function test_stock_inicial_sin_precio_se_rechaza_sin_perder_datos(): void
    {
        $datos = $this->datos();
        $id = array_key_first($datos['presentaciones']);
        $datos['presentaciones'][$id]['precio'] = null;
        $datos['presentaciones'][$id]['stock_inicial'] = 7;

        $this->actingAs($this->admin())->post('/admin/productos', $datos)->assertSessionHasErrors('presentaciones.'.$id.'.precio');
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    public function test_paginacion_en_espanol(): void
    {
        Producto::factory()->count(16)->create();

        $this->actingAs($this->admin())->get('/admin/productos')->assertOk()->assertSeeText(['Mostrando', 'resultados'])->assertDontSeeText('Showing');
    }

    private function datos(): array
    {
        $cinco = Tamano::firstOrCreate(['nombre' => '5 ml'])->id;
        $diez = Tamano::firstOrCreate(['nombre' => '10 ml'])->id;

        return ['nombre' => 'Nuevo perfume', 'marca_id' => Marca::factory()->create()->id, 'categoria_id' => Categoria::factory()->create()->id, 'genero' => 'mujer', 'descripcion' => 'Descripción del perfume', 'activo' => 1, 'destacado' => 1, 'notas' => [], 'presentaciones' => [$cinco => ['precio' => 0, 'activo' => 1], $diez => ['precio' => 60000, 'activo' => 1]]];
    }

    private function admin(string $rol = 'administrador'): Usuario
    {
        return Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']);
    }
}
