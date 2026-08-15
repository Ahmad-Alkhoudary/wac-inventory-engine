<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new User and return token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token, $user, 201);
    }

    /**
     * Get a JWT via given credentials.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid email or password',
            ], 401);
        }

        return $this->respondWithToken($token, auth('api')->user());
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh(), auth('api')->user());
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, ?User $user = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user ?? auth('api')->user(),
        ], $status);
    }
}
