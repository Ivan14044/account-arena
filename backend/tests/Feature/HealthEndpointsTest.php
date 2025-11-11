<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointsTest extends TestCase
{
    /** @test */
    public function ping_returns_ok_status(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'timestamp',
            ])
            ->assertJson(['status' => 'ok']);
    }

    /** @test */
    public function health_check_returns_services_status(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'timestamp',
            'services' => ['database', 'redis', 'storage'],
        ]);
    }
}



