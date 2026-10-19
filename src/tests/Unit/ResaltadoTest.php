<?php

namespace Tests\Unit;

use App\Support\Texto;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ResaltadoTest extends TestCase
{
    #[TestWith(['Hermès', 'hermes', '<mark>Hermès</mark>'])]
    #[TestWith(['Sauvage Sauvage', 'SAU', '<mark>Sau</mark>vage <mark>Sau</mark>vage'])]
    #[TestWith(['<script>á & luz</script>', 'a', '&lt;script&gt;<mark>á</mark> &amp; luz&lt;/script&gt;'])]
    #[TestWith(['Eau   de parfum', 'eau de', '<mark>Eau   de</mark> parfum'])]
    #[TestWith(['niño', 'nino', '<mark>niño</mark>'])]
    #[TestWith(['Ámbar', '', 'Ámbar'])]
    public function test_resalta_sin_tildes_conservando_original_y_escapando_html(string $texto, string $q, string $esperado): void
    {
        $this->assertSame($esperado, (string) Texto::resaltar($texto, $q));
    }
}
