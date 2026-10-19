<?php

namespace Tests\Feature\Tienda;

use Tests\TestCase;

class AuditoriaVistasTest extends TestCase
{
    public function test_vistas_publicas_no_contienen_emojis_ni_simbolos_icono(): void
    {
        $hallazgos = [];

        foreach (['tienda', 'components/tienda', 'auth', 'errors'] as $directorio) {
            $archivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/'.$directorio), \FilesystemIterator::SKIP_DOTS));

            foreach ($archivos as $archivo) {
                if (! $archivo->isFile() || ! str_ends_with($archivo->getFilename(), '.blade.php')) {
                    continue;
                }

                foreach (file($archivo->getPathname()) as $numero => $linea) {
                    if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2192}\x{25BE}]/u', $linea)) {
                        $hallazgos[] = $directorio.'/'.$archivo->getFilename().':'.($numero + 1);
                    }
                }
            }
        }

        $this->assertSame([], $hallazgos, 'Usá íconos Phosphor en estas vistas: '.implode(', ', $hallazgos));
    }
}
