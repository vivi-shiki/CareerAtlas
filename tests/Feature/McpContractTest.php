<?php

namespace Tests\Feature;

use App\Domain\OperationCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpContractTest extends TestCase
{
    use RefreshDatabase;

    private function rpc(string $method, array $params = [])
    {
        return $this->withHeaders(['Accept' => 'application/json, text/event-stream', 'MCP-Protocol-Version' => '2025-11-25'])
            ->postJson('/mcp/career', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    }

    public function test_mcp_requires_a_scoped_bearer_credential(): void
    {
        $this->rpc('tools/list')->assertUnauthorized();
        $user = User::factory()->create();
        $this->withToken($user->createToken('wrong-scope', ['portfolio:read'])->plainTextToken);
        $this->rpc('tools/list')->assertForbidden();
    }

    public function test_mcp_lists_atomic_tools_and_uses_the_same_owner_rules_as_http(): void
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('developer', ['career:manage'])->plainTextToken);
        $this->rpc('initialize', ['protocolVersion' => '2025-11-25', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'acceptance', 'version' => '1.0']])
            ->assertOk()->assertJsonPath('result.protocolVersion', '2025-11-25');
        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');
        $names = array_column($tools, 'name');
        $catalog = OperationCatalog::all();
        $this->assertEqualsCanonicalizing(array_keys($catalog), $names);
        foreach ($tools as $tool) {
            $this->assertFalse($tool['inputSchema']['additionalProperties']);
            $this->assertArrayNotHasKey('owner_id', $tool['inputSchema']['properties']);
        }
        $this->rpc('tools/call', ['name' => 'create_project', 'arguments' => ['title' => 'MCP project', 'type' => 'learning', 'role' => 'Developer']])
            ->assertOk()->assertJsonPath('result.isError', false);
        $this->assertDatabaseHas('projects', ['title' => 'MCP project', 'owner_id' => $user->id]);
        $this->rpc('tools/call', ['name' => 'create_project', 'arguments' => ['title' => 'Invalid']])
            ->assertOk()->assertJsonPath('result.isError', true);
        $this->assertDatabaseCount('projects', 1);
    }
}
