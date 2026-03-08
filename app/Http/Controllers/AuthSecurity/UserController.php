<?php

namespace App\Http\Controllers\AuthSecurity;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthSecurity\UserService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
    }

    public function index()
    {
        return Inertia::render('AuthSecurity/Users/Index', [
            'users' => $this->userService->getAllUsers(),
            'roles' => Role::all()
        ]);
    }

    public function create()
    {
        return Inertia::render('AuthSecurity/Users/Create', [
            'roles' => Role::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'password' => ['required', Rules\Password::defaults()],
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $this->userService->createUser($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('roles');
        return Inertia::render('AuthSecurity/Users/Edit', [
            'user' => $user,
            'roles' => Role::all()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class . ',email,' . $user->id,
            'password' => ['nullable', Rules\Password::defaults()],
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $this->userService->updateUser($user, $validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->userService->deleteUser($user);
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function apiIndex()
    {
        return response()->json($this->userService->getAllUsers());
    }

    public function apiShow(User $user)
    {
        return response()->json($user->load('roles'));
    }

    public function apiStore(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'password' => ['required', Rules\Password::defaults()],
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $user = $this->userService->createUser($validated);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user
        ], 201);
    }

    public function apiUpdate(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class . ',email,' . $user->id,
            'password' => ['nullable', Rules\Password::defaults()],
            'status' => 'required|string|in:active,inactive',
            'roles' => 'array'
        ]);

        $updated = $this->userService->updateUser($user, $validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $updated
        ]);
    }

    public function apiDestroy(User $user)
    {
        $this->userService->deleteUser($user);
        return response()->json(['message' => 'User deleted successfully.']);
    }
}
