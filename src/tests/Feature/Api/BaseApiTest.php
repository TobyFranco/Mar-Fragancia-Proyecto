<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_errores_son_json_sin_accept_json(): void
    {
        $this->get('/api/v1/inexistente')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_404_de_modelo_no_expone_clases_internas(): void
    {
        $this->get('/api/v1/productos/no-existe')->assertNotFound()->assertExactJson(['message' => 'Recurso no encontrado.']);
        $this->get('/api/v1/inexistente')->assertNotFound()->assertExactJson(['message' => 'Recurso no encontrado.']);
    }

    public function test_ruta_protegida_exige_token_y_devuelve_json(): void
    {
        $this->get('/api/v1/auth/me')->assertUnauthorized()->assertHeader('Content-Type', 'application/json');
    }
}
