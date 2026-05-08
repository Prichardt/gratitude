<?php

namespace Tests\Feature;

use App\Models\ApplicationKey;
use App\Models\Gratitude\Gratitude;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_key_can_be_created_with_web_guard_roles(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs(User::factory()->create());

        Role::create([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $response = $this->postJson('/internal-api/application-keys', [
            'name' => 'Partner App',
            'url' => 'https://example.com',
            'status' => 'active',
            'roles' => ['Super Admin'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Application Key created successfully.')
            ->assertJsonPath('data.name', 'Partner App')
            ->assertJsonPath('data.roles.0.name', 'Super Admin')
            ->assertJsonStructure(['plainTextToken']);

        $this->assertDatabaseHas('application_keys', [
            'name' => 'Partner App',
            'status' => 'active',
        ]);

        $this->assertTrue(ApplicationKey::where('name', 'Partner App')->first()->hasRole('Super Admin'));
        $this->assertSame(1, PersonalAccessToken::count());
    }

    public function test_external_api_accepts_active_application_key_token(): void
    {
        $appKey = ApplicationKey::create([
            'name' => 'External App',
            'status' => 'active',
        ]);

        $token = $appKey->createToken('External App API Key')->plainTextToken;

        Gratitude::create([
            'gratitudeNumber' => 'G-API-ACCESS',
            'level' => 'Explorer',
            'totalPoints' => 100,
            'useablePoints' => 100,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/gratitude/G-API-ACCESS');

        $response
            ->assertOk()
            ->assertJsonPath('gratitude.gratitudeNumber', 'G-API-ACCESS');
    }

    public function test_external_api_rejects_inactive_application_key_token(): void
    {
        $appKey = ApplicationKey::create([
            'name' => 'Inactive External App',
            'status' => 'inactive',
        ]);

        $token = $appKey->createToken('Inactive External App API Key')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/gratitude/G-API-ACCESS');

        $response->assertUnauthorized();
    }
}
