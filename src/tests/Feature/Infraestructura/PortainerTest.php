<?php

namespace Tests\Feature\Infraestructura;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PortainerTest extends TestCase
{
    public function test_compose_de_portainer_no_depende_de_archivos_del_host(): void
    {
        $compose = file_get_contents(base_path('../compose.yaml'));
        $this->assertStringNotContainsString('./src:', $compose);
        $this->assertStringNotContainsString('./docker/', $compose);
        $this->assertStringContainsString('storage_data:/var/www/storage', $compose);
        $this->assertStringContainsString('${APP_KEY', $compose);
        $this->assertStringNotContainsString('container_name:', $compose);
        $this->assertStringContainsString('./src:/var/www', file_get_contents(base_path('../compose.override.yaml')));
    }

    public function test_override_local_conserva_comando_y_clave_del_env_en_ambos_nodos_php(): void
    {
        $override = file_get_contents(base_path('../compose.override.yaml'));

        // Al redefinir entrypoint, Compose descarta el CMD de la imagen: hay que repetirlo.
        $this->assertStringContainsString('command: [php-fpm]', $override);
        $this->assertStringContainsString('command: [apache2-foreground]', $override);
        // Sin APP_KEY: null, el nodo recibe una clave vacía que pisa la de src/.env.
        $this->assertSame(2, substr_count($override, 'APP_KEY: null'));
    }

    public function test_comandos_cli_no_requieren_clave_del_stack(): void
    {
        $dir = sys_get_temp_dir().'/mar-portainer-'.bin2hex(random_bytes(8));
        mkdir($dir, 0700);

        try {
            file_put_contents($dir.'/docker-php-entrypoint', <<<'SH'
#!/bin/sh
exec "$@"
SH);
            chmod($dir.'/docker-php-entrypoint', 0700);

            $proceso = new Process(['sh', base_path('../docker/php/entrypoint.sh'), 'php', '-r', 'echo "cli-ok";'], base_path(), [
                'APP_KEY' => '',
                'PATH' => $dir.':'.getenv('PATH'),
            ]);
            $proceso->run();

            $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput());
            $this->assertSame('cli-ok', $proceso->getOutput());
        } finally {
            (new Filesystem)->deleteDirectory($dir);
        }
    }

    public function test_ambas_imagenes_php_habilitan_jpeg_y_freetype(): void
    {
        foreach (['php', 'apache'] as $imagen) {
            $dockerfile = file_get_contents(base_path('../docker/'.$imagen.'/Dockerfile'));
            $this->assertStringContainsString('docker-php-ext-configure gd --with-jpeg --with-freetype', $dockerfile);
        }
    }
}
