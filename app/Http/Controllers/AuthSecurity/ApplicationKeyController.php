<?php

namespace App\Http\Controllers\AuthSecurity;

use App\Http\Controllers\Controller;
use App\Models\ApplicationKey;
use App\Services\AuthSecurity\ApplicationKeyService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class ApplicationKeyController extends Controller
{
    public function __construct(protected ApplicationKeyService $appKeyService)
    {
    }

    public function index()
    {
        return Inertia::render('AuthSecurity/ApplicationKeys/Index', [
            'application_keys' => $this->appKeyService->getAllKeys(),
            'roles' => Role::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:application_keys,name',
            'url' => 'nullable|url',
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $result = $this->appKeyService->createKey($validated);

        return redirect()->route('application-keys.index')
            ->with('success', 'Application Key created successfully.')
            ->with('plainTextToken', $result['plainTextToken']);
    }

    public function update(Request $request, ApplicationKey $application_key)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:application_keys,name,' . $application_key->id,
            'url' => 'nullable|url',
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $this->appKeyService->updateKey($application_key, $validated);

        return redirect()->route('application-keys.index')->with('success', 'Application Key updated successfully.');
    }

    public function destroy(ApplicationKey $application_key)
    {
        $this->appKeyService->deleteKey($application_key);
        return redirect()->route('application-keys.index')->with('success', 'Application Key deleted successfully.');
    }

    // Internal API methods

    public function apiIndex()
    {
        return response()->json($this->appKeyService->getAllKeys());
    }

    public function apiShow(ApplicationKey $application_key)
    {
        return response()->json($application_key->load('roles'));
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:application_keys,name',
            'url' => 'nullable|url',
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $result = $this->appKeyService->createKey($validated);

        return response()->json([
            'message' => 'Application Key created successfully.',
            'data' => $result['application_key'],
            'plainTextToken' => $result['plainTextToken']
        ], 201);
    }

    public function apiUpdate(Request $request, ApplicationKey $application_key)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:application_keys,name,' . $application_key->id,
            'url' => 'nullable|url',
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $updated = $this->appKeyService->updateKey($application_key, $validated);

        return response()->json([
            'message' => 'Application Key updated successfully.',
            'data' => $updated
        ]);
    }

    public function apiToggleStatus(ApplicationKey $application_key)
    {
        $updated = $this->appKeyService->toggleStatus($application_key);

        return response()->json([
            'message' => 'Application Key status updated to ' . $updated->status . '.',
            'data' => $updated
        ]);
    }

    public function apiRegenerateToken(ApplicationKey $application_key)
    {
        $result = $this->appKeyService->regenerateToken($application_key);

        return response()->json([
            'message' => 'Token regenerated successfully.',
            'data' => $result['application_key'],
            'plainTextToken' => $result['plainTextToken']
        ]);
    }

    public function apiDestroy(ApplicationKey $application_key)
    {
        $this->appKeyService->deleteKey($application_key);
        return response()->json(['message' => 'Application Key deleted successfully.']);
    }
}
