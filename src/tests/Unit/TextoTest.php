<?php

namespace Tests\Unit;

use App\Support\Texto;
use PHPUnit\Framework\TestCase;

class TextoTest extends TestCase
{
    public function test_normaliza_tildes_mayusculas_y_espacios_sin_cambiar_apostrofos(): void
    {
        $this->assertSame('hermes terre d’hermes', Texto::normalizar('Hermès  Terre D’HERMÈS'));
        $this->assertSame('vainilla y limon', Texto::normalizar("  VAINILLA\t y  LIMÓN  "));
    }
}
