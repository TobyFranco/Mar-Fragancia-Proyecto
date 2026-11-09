<?php

namespace Tests\Feature\Api;

use App\Models\Nota;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Models\Tamano;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_solo_visibles_y_pagina_y_cuenta_marcas(): void
    {
        $p = ProductoPresentacion::factory()->create()->producto;
        Producto::factory()->create();
        ProductoPresentacion::factory()->create(['producto_id' => Producto::factory()->create(['activo' => false])->id]);
        $this->get('/api/v1/productos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $p->id)->assertJsonStructure(['links', 'meta']);
        $this->getJson('/api/v1/marcas')->assertOk()->assertJsonFragment(['id' => $p->marca_id, 'productos_visibles' => 1]);
        $this->getJson('/api/v1/categorias')->assertOk()->assertJsonFragment(['id' => $p->categoria_id]);
    }

    #[TestWith(['marca'])] #[TestWith(['categoria'])] #[TestWith(['genero'])] #[TestWith(['tamano'])] #[TestWith(['q'])]
    public function test_filtra_catalogo(string $filtro): void
    {
        $p = Producto::factory()->create(['nombre' => 'Ámbar exclusivo', 'genero' => 'mujer']);
        ProductoPresentacion::factory()->create(['producto_id' => $p->id, 'tamano_id' => Tamano::factory()->create(['nombre' => '10 ml'])->id]);
        ProductoPresentacion::factory()->create();
        $valor = match ($filtro) {
            'marca' => $p->marca->slug,'categoria' => $p->categoria_id,'genero' => 'mujer','tamano' => '10','q' => 'ambar'
        };
        $this->getJson('/api/v1/productos?'.http_build_query([$filtro => $valor]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $p->id);
    }

    public function test_ficha_expone_presentaciones_activas_precio_stock_y_notas(): void
    {
        $p = ProductoPresentacion::factory()->create(['precio' => 45000, 'stock' => 0]);
        ProductoPresentacion::factory()->create(['producto_id' => $p->producto_id, 'activo' => false]);
        ProductoPresentacion::factory()->create(['producto_id' => $p->producto_id, 'tamano_id' => Tamano::factory()->create(['nombre' => '100 ml'])->id]);
        $p->producto->notas()->attach(Nota::factory()->create(['nombre' => 'Vainilla']));
        $this->getJson('/api/v1/productos/'.$p->producto->slug)->assertOk()->assertJsonCount(1, 'data.presentaciones')->assertJsonPath('data.presentaciones.0.precio', '45000.00')->assertJsonPath('data.presentaciones.0.stock', 0)->assertJsonPath('data.presentaciones.0.disponible', false)->assertJsonPath('data.notas.0.nombre', 'Vainilla');
        $p->producto->update(['activo' => false]);
        $this->get('/api/v1/productos/'.$p->producto->slug)->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_busca_por_marca_y_notas_y_valida_filtros(): void
    {
        $p = ProductoPresentacion::factory()->create()->producto;
        $p->notas()->attach(Nota::factory()->create(['nombre' => 'Sándalo']));
        $this->getJson('/api/v1/productos?q=sandalo')->assertOk()->assertJsonPath('data.0.id', $p->id);
        $this->getJson('/api/v1/productos?q='.urlencode($p->marca->nombre))->assertOk()->assertJsonPath('data.0.id', $p->id);
        $this->getJson('/api/v1/productos?genero=otro')->assertUnprocessable()->assertJsonValidationErrors('genero');
        $this->getJson('/api/v1/productos?tamano=100')->assertUnprocessable();
    }
}
