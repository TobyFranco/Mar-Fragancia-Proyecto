<?php

namespace Tests\Feature\Infraestructura;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ScriptsDockerTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/mar-balanceo-'.bin2hex(random_bytes(8));
        mkdir($this->workspace.'/bin', 0700, true);
        mkdir($this->workspace.'/src');
        mkdir($this->workspace.'/docker/scripts', 0700, true);
        mkdir($this->workspace.'/src/public');
        mkdir($this->workspace.'/src/storage/app/public', 0700, true);
        file_put_contents($this->workspace.'/src/.env.example', "APP_KEY=\n");
        file_put_contents($this->workspace.'/storage-link.php', <<<'PHP'
<?php
$src = getenv('SRC_REAL');
require $src.'/vendor/autoload.php';
$app = require $src.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$target = $app['config']->get('filesystems.links')[$app->publicPath('storage')];
$app['config']->set('filesystems.links', [getenv('PRUEBA_DIR').'/src/public/storage' => $target]);
exit($kernel->call('storage:link '.implode(' ', array_slice($argv, 1))));
PHP);

        $this->crearComando('sleep', "#!/bin/sh\nexit 0\n");
        $this->crearComando('docker', <<<'BASH'
#!/bin/bash
set -eu
printf '%s\n' "$*" >> "$PRUEBA_DIR/comandos"
case "$*" in
    'compose stop web-a') echo caido > "$PRUEBA_DIR/estado" ;;
    'compose start web-a') echo recuperado > "$PRUEBA_DIR/estado" ;;
    'compose exec -T app-a php -r '*)
        if [[ "${BD_NUEVA:-0}" == 1 ]]; then exit 10; fi
        exit "${BD_NUEVA:-0}"
        ;;
    'compose exec -T app-a php artisan storage:link'*)
        shift 7
        exec "$PHP_REAL" "$PRUEBA_DIR/storage-link.php" "$@"
        ;;
    'compose down'*) exit 99 ;;
esac
BASH);
        $this->crearComando('curl', <<<'BASH'
#!/bin/bash
set -eu
estado=$(cat "$PRUEBA_DIR/estado" 2>/dev/null || echo inicial)
if [[ "$estado" == caido && "${FALLO_HTTP:-0}" == 1 ]]; then
    echo 'curl: HTTP 503' >&2
    exit 22
fi
contador=$(cat "$PRUEBA_DIR/contador" 2>/dev/null || echo 0)
contador=$((contador + 1))
echo "$contador" > "$PRUEBA_DIR/contador"
nodo=arq-b
if [[ "$estado" != caido && $((contador % 2)) == 1 ]]; then
    nodo=arq-a
fi
if [[ "${SIN_CABECERA:-0}" == 1 ]]; then
    printf 'HTTP/1.1 200 OK\r\n\r\n'
else
    printf 'HTTP/1.1 %s Demo\r\nX-Servido-Por: %s\r\n\r\n' "${CODIGO_HTTP:-200}" "$nodo"
fi
BASH);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_demo_comprueba_balanceo_caida_y_recuperacion(): void
    {
        $proceso = $this->ejecutar('docker/scripts/demo-failover.sh');

        $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
        $this->assertStringContainsString('arq-a', $proceso->getOutput());
        $this->assertStringContainsString('arq-b', $proceso->getOutput());
        $this->assertSame("compose stop web-a\ncompose start web-a\n", $this->comandos());
    }

    public function test_demo_falla_ante_error_http_y_restaura_el_nodo(): void
    {
        $proceso = $this->ejecutar('docker/scripts/demo-failover.sh', ['FALLO_HTTP' => '1']);

        $this->assertFalse($proceso->isSuccessful());
        $this->assertStringContainsString('compose stop web-a', $this->comandos());
        $this->assertStringContainsString('compose start web-a', $this->comandos());
    }

    public function test_demo_rechaza_respuestas_sin_identificacion_del_nodo(): void
    {
        $proceso = $this->ejecutar('docker/scripts/demo-failover.sh', ['SIN_CABECERA' => '1']);

        $this->assertFalse($proceso->isSuccessful());
        $this->assertSame('', $this->comandos());
    }

    public function test_demo_rechaza_redireccion_aunque_identifique_el_nodo(): void
    {
        $proceso = $this->ejecutar('docker/scripts/demo-failover.sh', ['CODIGO_HTTP' => '302']);

        $this->assertFalse($proceso->isSuccessful());
        $this->assertSame('', $this->comandos());
    }

    public function test_setup_conserva_entorno_y_datos_existentes(): void
    {
        $entorno = "APP_KEY=base64:clave-existente\nTIENDA_WHATSAPP=595981234567\n";
        file_put_contents($this->workspace.'/src/.env', $entorno);

        $proceso = $this->ejecutar('setup.sh');

        $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
        $this->assertSame($entorno, file_get_contents($this->workspace.'/src/.env'));
        $comandos = $this->comandos();
        $this->assertStringNotContainsString('compose down', $comandos);
        $this->assertStringNotContainsString('migrate:fresh', $comandos);
        $this->assertStringNotContainsString('composer update', $comandos);
        $this->assertStringNotContainsString('key:generate', $comandos);
        $this->assertStringNotContainsString('db:seed', $comandos);
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan migrate --force'));
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan storage:link'));
        $this->assertStringNotContainsString('exec -T app-b', $comandos);
    }

    public function test_setup_inicial_siembra_y_genera_clave_una_vez_desde_app_a(): void
    {
        $proceso = $this->ejecutar('setup.sh', ['BD_NUEVA' => '1']);

        $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
        $comandos = $this->comandos();
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan key:generate'));
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan migrate --force'));
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan db:seed --force'));
        $this->assertSame(1, substr_count($comandos, 'compose exec -T app-a php artisan storage:link'));
        $this->assertStringNotContainsString('exec -T app-b', $comandos);
    }

    public function test_setup_crea_enlace_storage_resoluble_en_el_checkout_compartido(): void
    {
        $proceso = $this->ejecutar('setup.sh');

        $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
        $this->assertSame(
            realpath($this->workspace.'/src/storage/app/public'),
            realpath($this->workspace.'/src/public/storage'),
            $proceso->getErrorOutput().$proceso->getOutput().$this->comandos(),
        );
    }

    public function test_setup_aborta_sin_migrar_ni_sembrar_si_falla_el_diagnostico(): void
    {
        $proceso = $this->ejecutar('setup.sh', ['BD_NUEVA' => '2']);

        $this->assertFalse($proceso->isSuccessful());
        $this->assertStringNotContainsString('php artisan migrate --force', $this->comandos());
        $this->assertStringNotContainsString('php artisan db:seed', $this->comandos());
    }

    private function crearComando(string $nombre, string $contenido): void
    {
        file_put_contents($this->workspace.'/bin/'.$nombre, $contenido);
        chmod($this->workspace.'/bin/'.$nombre, 0700);
    }

    private function ejecutar(string $script, array $entorno = []): Process
    {
        $origen = base_path('../'.$script);
        $this->assertFileExists($origen);
        copy($origen, $this->workspace.'/'.$script);

        $proceso = new Process(['bash', $this->workspace.'/'.$script], $this->workspace, array_merge([
            'PATH' => $this->workspace.'/bin:'.getenv('PATH'),
            'PRUEBA_DIR' => $this->workspace,
            'SRC_REAL' => base_path(),
            'PHP_REAL' => PHP_BINARY,
        ], $entorno));
        $proceso->setTimeout(10);
        $proceso->run();

        return $proceso;
    }

    private function comandos(): string
    {
        $archivo = $this->workspace.'/comandos';

        return is_file($archivo) ? file_get_contents($archivo) : '';
    }
}
