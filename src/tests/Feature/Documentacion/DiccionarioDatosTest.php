<?php

namespace Tests\Feature\Documentacion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiccionarioDatosTest extends TestCase
{
    use RefreshDatabase;

    private const INFRAESTRUCTURA = ['cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'migrations', 'personal_access_tokens'];

    public function test_cada_tabla_y_columna_migrada_tiene_descripcion(): void
    {
        $descripciones = require base_path('../docs/datos/descripciones.php');
        foreach (Schema::getTables() as $tabla) {
            $nombre = $tabla['name'];
            $this->assertArrayHasKey($nombre, $descripciones, 'Falta descripción de tabla: '.$nombre);
            $this->assertIsString($descripciones[$nombre]['_tabla'] ?? null, 'Falta descripción de tabla: '.$nombre);
            $this->assertNotSame('', trim($descripciones[$nombre]['_tabla']));
            foreach (Schema::getColumns($nombre) as $columna) {
                $clave = $columna['name'];
                $this->assertArrayHasKey($clave, $descripciones[$nombre], 'Falta descripción: '.$nombre.'.'.$clave);
                $this->assertIsString($descripciones[$nombre][$clave]);
                $this->assertNotSame('', trim($descripciones[$nombre][$clave]), 'Descripción vacía: '.$nombre.'.'.$clave);
            }
        }
    }

    public function test_diccionario_menciona_cada_tabla_de_negocio(): void
    {
        $documento = file_get_contents(base_path('../docs/datos/diccionario-de-datos.md'));
        $negocio = explode('## Tablas de infraestructura', $documento)[0];
        foreach (Schema::getTables() as $tabla) {
            $nombre = $tabla['name'];
            if (! in_array($nombre, self::INFRAESTRUCTURA, true)) {
                $this->assertStringContainsString('### '.$nombre."\n", $negocio, 'Falta tabla de negocio: '.$nombre);
            }
        }
    }
}
