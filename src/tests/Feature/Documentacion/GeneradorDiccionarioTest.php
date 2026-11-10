<?php

namespace Tests\Feature\Documentacion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneradorDiccionarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_publica_tipos_sqlite_como_mysql(): void
    {
        $this->artisan('docs:diccionario')->expectsOutputToContain('MySQL')->assertFailed();
    }

    public function test_no_sobrescribe_un_archivo_si_la_conexion_no_es_mysql(): void
    {
        $archivo = tempnam(sys_get_temp_dir(), 'mar-diccionario-');
        file_put_contents($archivo, 'Documento MySQL anterior');
        try {
            $this->artisan('docs:diccionario', ['--output' => $archivo])->assertFailed();
            $this->assertSame('Documento MySQL anterior', file_get_contents($archivo));
        } finally {
            unlink($archivo);
        }
    }
}
