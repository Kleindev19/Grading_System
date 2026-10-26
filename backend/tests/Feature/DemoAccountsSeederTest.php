<?php

namespace Tests\Feature;

use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_can_log_in_after_seeding(): void
    {
        $this->seed(DemoAccountsSeeder::class);

        foreach (['23-00001', 'mock.professor', 'mock.registrar'] as $username) {
            $this->postJson('/api/login', [
                'username' => $username,
                'password' => 'Password123!',
            ])->assertOk()
                ->assertJsonPath('user.username', $username);
        }
    }

    public function test_vercel_origin_is_allowed_for_api_preflight_requests(): void
    {
        config()->set('cors.allowed_origins', ['https://gradingsystem05.vercel.app']);

        $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'https://gradingsystem05.vercel.app',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ])->assertNoContent()
            ->assertHeader('access-control-allow-origin', 'https://gradingsystem05.vercel.app');
    }

    public function test_demo_accounts_seeding_is_disabled_in_production_by_default(): void
    {
        config()->set('app.env', 'production');
        config()->set('app.seed_demo_accounts', false);

        $this->expectException(\RuntimeException::class);

        $this->seed(DemoAccountsSeeder::class);
    }
}
