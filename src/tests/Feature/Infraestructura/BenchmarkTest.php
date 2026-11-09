<?php

namespace Tests\Feature\Infraestructura;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BenchmarkTest extends TestCase
{
    public function test_mide_ambas_arquitecturas_endpoints_y_exporta_csv(): void
    {
        $this->ejecutar(false);
    }

    public function test_restaura_nodo_y_configuracion_tras_un_fallo(): void
    {
        $this->ejecutar(true);
    }

    private function ejecutar(bool $fallar): void
    {
        $dir = sys_get_temp_dir().'/mar-bench-'.bin2hex(random_bytes(8));
        mkdir($dir.'/bin', 0700, true);
        file_put_contents($dir.'/bin/docker', <<<'BASH'
#!/bin/bash
printf '%s\n' "$*" >> "$BENCHMARK_OUTPUT/comandos"
if [[ "$*" == 'compose stop web-nginx' ]]; then
 touch "$BENCHMARK_OUTPUT/caido"
elif [[ "$*" == 'compose start web-nginx' && "${FALLAR:-0}" == 1 && -e "$BENCHMARK_OUTPUT/caido" ]]; then
 rm "$BENCHMARK_OUTPUT/caido"
 exit 2
elif [[ "$*" == *'run --rm --no-deps benchmark'* ]]; then
 if [[ "$*" == *' -t '* && "${FALLAR:-0}" == 1 ]]; then exit 2; fi
 cat <<'AB'
Complete requests:      2000
Failed requests:        0
Requests per second:    100.00 [#/sec] (mean)
Total: 1 20 3 19 50
  50% 19
  95% 40
  99% 48
AB
elif [[ "$*" == *benchmark-opcache* ]]; then
 printf OPCACHE_OK
elif [[ "$*" == *curl* ]]; then
 printf '# pxname,svname,stot,bin,bout,status\nnodos_web,nginx-php-fpm,10,20,30,UP\nnodos_web,apache-mod-php,10,20,30,UP\n'
fi
BASH);
        chmod($dir.'/bin/docker', 0700);
        try {
            $script = base_path('../docker/scripts/benchmark.sh');
            $this->assertFileExists($script);
            $process = new Process(['bash', $script], base_path('..'), [
                'PATH' => $dir.'/bin:'.getenv('PATH'),
                'BENCHMARK_OUTPUT' => $dir,
                'BENCHMARK_HOLD_SECONDS' => '0',
                'FALLAR' => $fallar ? '1' : '0',
            ]);
            $process->setTimeout(30);
            $process->run();
            $this->assertSame(! $fallar, $process->isSuccessful(), $process->getErrorOutput());
            $commands = file_get_contents($dir.'/comandos');
            $this->assertStringContainsString('start web-nginx', $commands);
            $this->assertStringContainsString('config:clear', $commands);
            $this->assertStringContainsString('route:clear', $commands);
            $this->assertStringContainsString('view:clear', $commands);
            if ($fallar) {
                $this->assertSame(2, substr_count($commands, 'start web-nginx'));
            }
            if (! $fallar) {
                $rows = array_map('str_getcsv', file($dir.'/resultados.csv', FILE_IGNORE_NEW_LINES));
                $this->assertSame(['arquitectura', 'endpoint', 'concurrencia', 'rps', 'latencia_media_ms', 'p50_ms', 'p95_ms', 'p99_ms', 'errores', 'requests'], $rows[0]);
                $this->assertCount(27, $rows);
                $this->assertCount(4, array_unique(array_column(array_slice($rows, 1, 12), 1)));
                $this->assertStringContainsString('http://web-nginx/up', $commands);
                $this->assertStringContainsString('http://apache-php/perfumes/coromandel', $commands);
                $this->assertStringContainsString('/build/assets/app-', $commands);
            }
        } finally {
            (new Filesystem)->deleteDirectory($dir);
        }
    }
}
