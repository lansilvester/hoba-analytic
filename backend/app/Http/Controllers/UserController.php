<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with(['role', 'tenant'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(fn ($q) => $q
                    ->where('name', 'ilike', "%{$request->string('search')}%")
                    ->orWhere('email', 'ilike', "%{$request->string('search')}%"));
            })
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $data['role_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return (new UserResource($user->load(['role', 'tenant'])))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load(['role', 'tenant'])),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($user->is(Auth::user()) && isset($data['role_id']) && $data['role_id'] !== $user->role_id) {
            return response()->json([
                'message' => 'Tidak bisa mengubah role akun sendiri',
                'errors' => ['role_id' => ['Tidak bisa mengubah role akun sendiri']],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'] ?? $user->role_id,
            'tenant_id' => array_key_exists('tenant_id', $data) ? $data['tenant_id'] : $user->tenant_id,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        return response()->json(['data' => new UserResource($user->load(['role', 'tenant']))]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->is(Auth::user())) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri',
                'errors' => ['user' => ['Tidak bisa menghapus akun sendiri']],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
