<?php

namespace Tests\Feature\Infraestructura;

use Tests\TestCase;

class ProcesosPhpTest extends TestCase
{
    public function test_ambas_arquitecturas_usan_la_misma_cantidad_fija_de_procesos_php(): void
    {
        $fpm = $this->leer('docker/php/procesos.conf');
        $prefork = $this->leer('docker/apache/mpm_prefork.conf');

        $this->assertMatchesRegularExpression('/^pm\s*=\s*static$/m', $fpm);
        preg_match('/^pm\.max_children\s*=\s*(\d+)$/m', $fpm, $hijos);
        $this->assertNotEmpty($hijos);

        foreach (['StartServers', 'MinSpareServers', 'MaxSpareServers', 'ServerLimit', 'MaxRequestWorkers'] as $directiva) {
            $this->assertMatchesRegularExpression('/^\s*'.$directiva.'\s+'.$hijos[1].'$/m', $prefork, $directiva);
        }
    }

    public function test_las_imagenes_copian_la_configuracion_de_procesos(): void
    {
        $this->assertStringContainsString('COPY docker/php/procesos.conf /usr/local/etc/php-fpm.d/zz-procesos.conf', $this->leer('docker/php/Dockerfile'));
        $this->assertStringContainsString('COPY docker/apache/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf', $this->leer('docker/apache/Dockerfile'));
    }

    private function leer(string $ruta): string
    {
        $archivo = base_path('../'.$ruta);
        $this->assertFileExists($archivo);

        return file_get_contents($archivo);
    }
}
