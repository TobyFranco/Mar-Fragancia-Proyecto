<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class DocumentacionApiTest extends TestCase
{
    public function test_cada_ruta_de_la_api_esta_documentada_en_openapi(): void
    {
        $spec = Yaml::parseFile(base_path('../docs/api/openapi.yaml'));
        $documentadas = collect($spec['paths'])->flatMap(fn (array $operaciones, string $ruta) => collect(array_keys($operaciones))->map(fn ($metodo) => strtoupper($metodo).' '.$ruta))->sort()->values()->all();

        $rutas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_starts_with($ruta->uri(), 'api/v1/'))
            ->flatMap(fn ($ruta) => collect($ruta->methods())->reject(fn ($metodo) => $metodo === 'HEAD')->map(fn ($metodo) => $metodo.' '.substr($ruta->uri(), strlen('api/v1'))))
            ->sort()->values()->all();

        $this->assertSame($rutas, $documentadas);
    }
}
