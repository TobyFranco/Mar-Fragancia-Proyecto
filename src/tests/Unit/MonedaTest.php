<?php

namespace Tests\Unit;

use App\Support\Moneda;
use PHPUnit\Framework\TestCase;

class MonedaTest extends TestCase
{
    public function test_formatea_guaranies_sin_decimales(): void
    {
        $this->assertSame('₲ 124.000', Moneda::gs(124000));
        $this->assertSame('₲ 0', Moneda::gs(0));
        $this->assertSame('₲ 1.500.000', Moneda::gs(1500000));
    }
}
