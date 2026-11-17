<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_mutations_require_csrf_even_with_an_authenticated_session(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'local');
        $this->postJson('/api/operations/create_project', ['title' => 'Forged', 'type' => 'personal', 'role' => 'Developer'])->assertStatus(419);
        $this->assertDatabaseCount('projects', 0);
        $this->withSession(['_token' => 'known-test-csrf']);
        $this->withHeader('X-CSRF-TOKEN', 'known-test-csrf')->postJson('/api/operations/create_project', ['title' => 'Authorized', 'type' => 'personal', 'role' => 'Developer'])->assertOk();
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_mcp_rejects_browser_sessions_foreign_origins_and_revoked_tokens(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $message = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'];
        $this->postJson('/mcp/career', $message)->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $token = $user->createToken('temporary', ['career:manage']);
        $this->withToken($token->plainTextToken)->withHeader('Origin', 'https://untrusted.example')->postJson('/mcp/career', $message)->assertForbidden();
        $this->flushHeaders();
        $token->accessToken->delete();
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->postJson('/mcp/career', $message)->assertUnauthorized();
    }

    public function test_public_and_private_responses_are_not_cached(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson('/api/operations/get_profile')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
