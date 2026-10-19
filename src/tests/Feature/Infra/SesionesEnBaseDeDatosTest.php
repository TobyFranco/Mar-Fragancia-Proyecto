<?php

namespace Tests\Feature\Infra;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SesionesEnBaseDeDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_tiene_las_columnas_estandar_de_laravel(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasColumns('sessions', ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity']));
    }

    public function test_inicio_guarda_la_sesion_con_driver_database(): void
    {
        config(['session.driver' => 'database']);

        $this->get('/')->assertOk();

        $this->assertDatabaseCount('sessions', 1);
        $this->assertDatabaseHas('sessions', ['id' => session()->getId(), 'user_id' => null]);
    }
}
