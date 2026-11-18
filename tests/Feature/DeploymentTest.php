<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;

    private function productionConfiguration(): void
    {
        $this->app->instance('env', 'production');
        config([
            'app.debug' => false,
            'app.url' => 'https://portfolio.example',
            'session.secure' => true,
            'session.driver' => 'database',
            'cache.default' => 'file',
            'agent.driver' => 'gemini',
            'agent.enabled' => false,
        ]);
    }

    public function test_production_check_accepts_a_migrated_manual_only_deployment(): void
    {
        $this->productionConfiguration();
        $this->artisan('deploy:check')->assertSuccessful();
    }

    public function test_production_check_rejects_insecure_cookies_and_test_transport(): void
    {
        $this->productionConfiguration();
        config(['session.secure' => false]);
        $this->artisan('deploy:check')->assertFailed();
        config(['session.secure' => true, 'agent.driver' => 'fake']);
        $this->artisan('deploy:check')->assertFailed();
    }

    public function test_model_secrets_are_not_printed_when_configuration_is_incomplete(): void
    {
        $this->productionConfiguration();
        config(['agent.enabled' => true, 'agent.key' => 'synthetic-secret', 'agent.model' => '']);
        $this->artisan('deploy:check')
            ->expectsOutput('Deployment check failed: model configuration')
            ->doesntExpectOutput('synthetic-secret')
            ->assertFailed();
    }
}
