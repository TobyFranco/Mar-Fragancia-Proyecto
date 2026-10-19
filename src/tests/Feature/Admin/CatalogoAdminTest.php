<?php

namespace Tests\Feature\Admin;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Tamano;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_marcas_muestran_cantidad_de_perfumes(): void
    {
        $marca = Marca::factory()->create(['nombre' => 'Marca de prueba']);
        Producto::factory()->count(2)->for($marca)->create();
        $this->admin();
        $this->get('/admin/marcas')->assertOk()->assertSeeText(['Marca de prueba', 'Perfumes', '2'])->assertSee('admin-sidebar', false);
    }

    public function test_marca_gestiona_destacada_orden_menu_e_imagen(): void
    {
        Storage::fake('public');
        $m = Marca::factory()->create(['nombre' => 'Mi marca', 'destacada' => false]);
        $this->admin();
        $this->put('/admin/marcas/'.$m->id, ['nombre' => 'Mi marca renovada', 'destacada' => 1, 'orden_menu' => 1, 'imagen' => UploadedFile::fake()->image('panel.jpg')])->assertSessionHasNoErrors();
        $m->refresh();
        $this->assertTrue($m->destacada);
        $this->assertSame(1, $m->orden_menu);
        $this->assertNotNull($m->imagen);
        Storage::disk('public')->assertExists($m->imagen);
        $this->get('/')->assertOk()->assertSeeText('Mi marca renovada')->assertSee(Storage::disk('public')->url($m->imagen), false);
    }

    public function test_no_permite_mas_de_ocho_marcas_destacadas(): void
    {
        Marca::factory()->count(8)->create(['destacada' => true]);
        $this->admin();
        $this->post('/admin/marcas', ['nombre' => 'Novena', 'destacada' => 1])->assertSessionHasErrors('destacada');
        $no = Marca::factory()->create(['destacada' => false]);
        $this->put('/admin/marcas/'.$no->id, ['nombre' => $no->nombre, 'destacada' => 1])->assertSessionHasErrors('destacada');
        $si = Marca::where('destacada', true)->first();
        $this->put('/admin/marcas/'.$si->id, ['nombre' => $si->nombre, 'destacada' => 1, 'orden_menu' => 2])->assertSessionHasNoErrors();
        $this->assertSame(8, Marca::where('destacada', true)->count());
    }

    public function test_no_elimina_marca_o_categoria_con_perfumes(): void
    {
        $p = Producto::factory()->create();
        $this->admin();
        $this->delete('/admin/marcas/'.$p->marca_id)->assertSessionHasErrors('marca');
        $this->delete('/admin/categorias/'.$p->categoria_id)->assertSessionHasErrors('categoria');
        $this->assertModelExists($p->marca);
        $this->assertModelExists($p->categoria);
    }

    public function test_elimina_marca_sin_perfumes(): void
    {
        $m = Marca::factory()->create();
        $c = Categoria::factory()->create();
        $this->admin();
        $this->delete('/admin/marcas/'.$m->id)->assertRedirect('/admin/marcas');
        $this->delete('/admin/categorias/'.$c->id)->assertRedirect('/admin/categorias');
        $this->assertModelMissing($m);
        $this->assertModelMissing($c);
    }

    public function test_tamanos_es_solo_lectura(): void
    {
        $t = Tamano::factory()->create(['nombre' => '5 ml']);
        Tamano::factory()->create(['nombre' => '10 ml']);
        Tamano::factory()->create(['nombre' => '15 ml']);
        $this->admin();
        $this->get('/admin/tamanos')->assertOk()->assertSeeText(['5 ml', '10 ml'])->assertDontSeeText('15 ml')->assertDontSeeText('Nuevo tamaño');
        $this->get('/admin/tamanos/create')->assertNotFound();
        $this->get('/admin/tamanos/'.$t->id.'/edit')->assertNotFound();
        $this->post('/admin/tamanos', ['nombre' => '15 ml'])->assertStatus(405);
        $this->put('/admin/tamanos/'.$t->id, [])->assertNotFound();
        $this->delete('/admin/tamanos/'.$t->id)->assertNotFound();
        $this->assertModelExists($t);
    }

    public function test_formularios_y_categoria_validan_y_guardan(): void
    {
        $this->admin();
        $this->get('/admin/marcas/create')->assertOk()->assertSeeText('Orden del menú');
        $this->get('/admin/categorias/create')->assertOk()->assertSeeText('Nueva categoría');
        $this->post('/admin/categorias', ['nombre' => 'Nueva familia'])->assertSessionHasNoErrors();
        $c = Categoria::where('nombre', 'Nueva familia')->sole();
        $this->put('/admin/categorias/'.$c->id, ['nombre' => 'Familia renovada'])->assertSessionHasNoErrors();
        $this->assertSame('Familia renovada', $c->fresh()->nombre);
        $this->post('/admin/categorias', [])->assertSessionHasErrors('nombre');
        $this->post('/admin/marcas', ['nombre' => 'Marca nueva', 'orden_menu' => -1])->assertSessionHasErrors('orden_menu');
    }

    public function test_rutas_exigen_administrador(): void
    {
        $this->get('/admin/marcas')->assertRedirect('/login');
        $this->admin('cliente');
        foreach (['marcas', 'categorias', 'tamanos'] as $recurso) {
            $this->get('/admin/'.$recurso)->assertForbidden();
        }
        $this->post('/admin/marcas', ['nombre' => 'Intrusa'])->assertForbidden();
        $this->post('/admin/categorias', ['nombre' => 'Intrusa'])->assertForbidden();
    }

    private function admin(string $rol = 'administrador'): void
    {
        $this->actingAs(Usuario::create(['rol_id' => Rol::firstOrCreate(['nombre' => $rol])->id, 'nombre' => 'Paz', 'email' => $rol.'@example.com', 'password' => 'no-se-usa']));
    }
}
