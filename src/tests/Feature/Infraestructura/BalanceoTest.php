<?php

namespace Tests\Feature\Infraestructura;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BalanceoTest extends TestCase
{
    public function test_health_check_responde_200(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_url_usa_el_protocolo_y_host_del_proxy(): void
    {
        Route::get('/prueba-balanceo', fn () => url('/'));

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'marfragancias.example',
        ])->get('/prueba-balanceo')
            ->assertOk()
            ->assertContent('https://marfragancias.example');
    }
}
