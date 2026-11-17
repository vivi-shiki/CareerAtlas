<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_login_logout_and_private_boundary(): void
    {
        $this->postJson('/api/operations/list_projects')->assertUnauthorized();
        $registration = ['name' => 'Alex Morgan', 'email' => 'alex@example.test', 'password' => 'Fictional-demo-passphrase-123', 'password_confirmation' => 'Fictional-demo-passphrase-123'];
        $this->postJson('/api/auth/register', $registration)->assertCreated()->assertJsonMissingPath('data.password');
        $user = User::where('email', $registration['email'])->firstOrFail();
        $this->assertTrue(Hash::check($registration['password'], $user->password));
        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.email', 'alex@example.test');
        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->postJson('/api/operations/list_projects')->assertUnauthorized();
        $this->postJson('/api/auth/login', ['email' => 'alex@example.test', 'password' => 'incorrect'])->assertUnprocessable();
        $this->postJson('/api/auth/login', $registration)->assertOk();
        $this->postJson('/api/operations/list_projects')->assertOk();
    }

    public function test_duplicate_registration_and_weak_password_are_rejected(): void
    {
        User::factory()->create(['email' => 'alex@example.test']);
        $this->postJson('/api/auth/register', ['name' => 'Alex', 'email' => 'alex@example.test', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }
}
