<?php

namespace App\Services\AuthSecurity;

use App\Models\ApplicationKey;
use Illuminate\Database\Eloquent\Collection;

class ApplicationKeyService
{
    public function getAllKeys(): Collection
    {
        return ApplicationKey::with('roles')->get();
    }

    public function createKey(array $data): array
    {
        $appKey = ApplicationKey::create([
            'name' => $data['name'],
            'url' => $data['url'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        if (isset($data['roles'])) {
            $appKey->syncRoles($data['roles']);
        }
        
        // Generate Sanctum Token
        // The token model is stored in memory, and the plainTextToken is returned ONLY here
        $tokenName = $data['name'] . ' API Key';
        $token = $appKey->createToken($tokenName);
        
        // Optional: save partial token string to table if we wanted, but not needed
        // $appKey->update(['token' => substr($token->plainTextToken, 0, 10) . '...']);

        return [
            'application_key' => $appKey->load('roles'),
            'plainTextToken' => $token->plainTextToken
        ];
    }

    public function updateKey(ApplicationKey $appKey, array $data): ApplicationKey
    {
        $appKey->update([
            'name' => $data['name'],
            'url' => $data['url'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        if (isset($data['roles'])) {
            $appKey->syncRoles($data['roles']);
        }
        
        return $appKey;
    }

    public function deleteKey(ApplicationKey $appKey): bool
    {
        // Also revoke all tokens (optional, but good practice before deleting)
        $appKey->tokens()->delete();
        
        return $appKey->delete();
    }
}
