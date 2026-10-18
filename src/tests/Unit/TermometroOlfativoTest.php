<?php

namespace Tests\Unit;

use App\Services\TermometroOlfativo;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class TermometroOlfativoTest extends TestCase
{
    #[TestWith([10, 'frio'])]
    #[TestWith([18, 'frio'])]
    #[TestWith([19, 'templado'])]
    #[TestWith([26, 'templado'])]
    #[TestWith([27, 'calor'])]
    #[TestWith([32, 'calor'])]
    #[TestWith([33, 'calor_intenso'])]
    #[TestWith([40, 'calor_intenso'])]
    public function test_bordes_de_zona(int $temperatura, string $clave): void
    {
        $this->assertSame($clave, (new TermometroOlfativo)->zona($temperatura)['clave']);
    }

    #[TestWith([10, true, false, 4])]
    #[TestWith([36, false, true, 1])]
    #[TestWith([30, true, true, 2])]
    #[TestWith([22, false, false, 3])]
    #[TestWith([18, true, true, 3])]
    public function test_dosis_noche_y_humedad_con_limites(int $temperatura, bool $noche, bool $humedo, int $esperada): void
    {
        $this->assertSame($esperada, (new TermometroOlfativo)->dosis($temperatura, $noche, $humedo));
    }
}
