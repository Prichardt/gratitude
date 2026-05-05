<?php

namespace App\Services\AuthSecurity;

use App\Models\ApplicationKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationKeyService
{
    public function getAllKeys(): Collection
    {
        return ApplicationKey::with('roles')->get();
    }

    public function createKey(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $appKey = ApplicationKey::create([
                'name' => $data['name'],
                'url' => $data['url'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            if (array_key_exists('roles', $data)) {
                $appKey->syncRoles($data['roles'] ?? []);
            }

            $token = $appKey->createToken($data['name'].' API Key');

            $appKey->update(['token' => $token->plainTextToken]);

            return [
                'application_key' => $appKey->load('roles'),
                'plainTextToken' => $token->plainTextToken,
            ];
        });
    }

    public function updateKey(ApplicationKey $appKey, array $data): ApplicationKey
    {
        $appKey->update([
            'name' => $data['name'],
            'url' => $data['url'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        if (array_key_exists('roles', $data)) {
            $appKey->syncRoles($data['roles'] ?? []);
        }

        return $appKey->load('roles');
    }

    public function regenerateToken(ApplicationKey $appKey): array
    {
        // Revoke all existing tokens
        $appKey->tokens()->delete();

        // Generate a new token
        $token = $appKey->createToken($appKey->name.' API Key');

        $appKey->update(['token' => $token->plainTextToken]);

        return [
            'application_key' => $appKey->load('roles'),
            'plainTextToken' => $token->plainTextToken,
        ];
    }

    public function toggleStatus(ApplicationKey $appKey): ApplicationKey
    {
        $newStatus = $appKey->status === 'active' ? 'inactive' : 'active';
        $appKey->update(['status' => $newStatus]);

        return $appKey->load('roles');
    }

    public function deleteKey(ApplicationKey $appKey): bool
    {
        $appKey->tokens()->delete();

        return $appKey->delete();
    }
}
