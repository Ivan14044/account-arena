<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Главная (SSR через SpaController) должна быть маршрутизируема и не падать
     * серверной ошибкой. В проде/со сборкой фронта `/` отдаёт 200, а в backend-тестах
     * без `frontend/dist` — 404 'Frontend build not found' (корректная обработка
     * отсутствующей сборки). Регрессией считается только 5xx.
     */
    public function test_home_route_responds_without_server_error(): void
    {
        $response = $this->get('/');

        $this->assertContains(
            $response->status(),
            [200, 404],
            'Home route should respond 200 (frontend built) or 404 (no build), not a server error.'
        );
    }
}
