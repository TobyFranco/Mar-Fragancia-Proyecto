<?php

namespace Tests\Feature\Tienda;

use App\Models\Nota;
use App\Models\Producto;
use App\Models\ProductoPresentacion;
use App\Services\ClimaAsuncion;
use App\Services\TermometroOlfativo;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class TermometroTest extends TestCase
{
    use RefreshDatabase;

    public function test_recomendaciones_priorizan_coincidencias_y_excluyen_notas_a_evitar(): void
    {
        $a = $this->perfume('Dos coincidencias', ['limón', 'menta']);
        $b = $this->perfume('Una coincidencia', ['limón']);
        $this->perfume('No recomendado', ['limón', 'vainilla']);
        $this->perfume('Sin coincidencias', ['rosa']);
        $this->perfume('Agotado', ['limón', 'menta'], ['stock' => 0]);
        $inactivo = $this->perfume('Inactivo', ['limón', 'menta']);
        $inactivo->update(['activo' => false]);

        $this->assertSame([$a->id, $b->id], (new TermometroOlfativo)->recomendaciones(36)->modelKeys());
        $this->assertSame([$a->id], (new TermometroOlfativo)->recomendaciones(36, 1)->modelKeys());
    }

    public function test_clima_usa_open_meteo_y_cache_de_treinta_minutos(): void
    {
        $this->freezeTime();
        Cache::forget('clima_asuncion');
        Http::preventStrayRequests();
        Http::fake(['api.open-meteo.com/*' => Http::response(['current' => ['temperature_2m' => 31.6, 'relative_humidity_2m' => 70]])]);
        $clima = new ClimaAsuncion;
        $this->assertSame(['temperatura' => 32, 'humedo' => true, 'fuente' => 'open-meteo'], $clima->actual());
        $clima->actual();
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r['latitude'] == -25.28 && $r['longitude'] == -57.63 && $r['current'] === 'temperature_2m,relative_humidity_2m');
        $this->travel(31)->minutes();
        $clima->actual();
        Http::assertSentCount(2);
    }

    #[TestWith(['http'])]
    #[TestWith(['conexion'])]
    #[TestWith(['malformado'])]
    public function test_si_open_meteo_falla_usa_promedio_del_mes(string $falla): void
    {
        $this->travelTo(now()->setDate(2026, 10, 18));
        Cache::forget('clima_asuncion');
        Http::preventStrayRequests();
        Http::fake(['api.open-meteo.com/*' => match ($falla) {
            'http' => Http::response([], 500),
            'conexion' => Http::failedConnection(),
            default => Http::response(['current' => ['temperature_2m' => null]]),
        }]);
        $this->assertSame(['temperatura' => 30, 'humedo' => false, 'fuente' => 'promedio'], (new ClimaAsuncion)->actual());
        $this->get('/termometro-olfativo')->assertOk()->assertDontSeeText(['Error', 'Open-Meteo', 'Exception']);
    }

    public function test_promedio_usa_el_mes_local_de_asuncion(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-01T01:00:00Z'));
        Cache::forget('clima_asuncion');
        Http::preventStrayRequests();
        Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
        $this->assertSame(30, (new ClimaAsuncion)->actual()['temperatura']);
    }

    public function test_pagina_muestra_termometro_meses_y_recomendaciones(): void
    {
        Cache::forget('clima_asuncion');
        Http::preventStrayRequests();
        Http::fake(['api.open-meteo.com/*' => Http::response(['current' => ['temperature_2m' => 36, 'relative_humidity_2m' => 60]])]);
        foreach (['Fresco A', 'Fresco B', 'Fresco C'] as $nombre) {
            $this->perfume($nombre, ['limón']);
        }
        $r = $this->get('/termometro-olfativo')->assertOk()->assertSeeText(['Cada clima tiene su perfume.', 'Usar el clima de hoy', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic', 'Fresco A']);
        $this->assertCount(3, $r->viewData('recomendaciones')['calor_intenso']);
        $this->assertSame(1, $r->viewData('zonas')['calor_intenso']['estados']['dia_humedo']['dosis']);
        $this->assertCount(31, $r->viewData('porTemperatura'));
    }

    private function perfume(string $nombre, array $notas, array $presentacion = []): Producto
    {
        $p = Producto::factory()->create(['nombre' => $nombre]);
        foreach ($notas as $nombreNota) {
            $p->notas()->attach(Nota::firstOrCreate(['nombre' => $nombreNota]));
        }
        ProductoPresentacion::factory()->for($p, 'producto')->create($presentacion);

        return $p;
    }
}
