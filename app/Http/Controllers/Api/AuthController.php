<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\ApiAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(ApiAccess::profileForUser($request->user()));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    /**
     * Cross-domain auth (React on different registrable domain): use Bearer token.
     */
    public function tokenLogin(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $tokenName = $data['device_name'] ?? 'react';
        $token = $user->createToken($tokenName);

        return response()->json(array_merge(ApiAccess::profileForUser($user), [
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
        ]));
    }

    public function tokenLogout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if ($user && $token) {
            $user->tokens()->where('id', $token->id)->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(ApiAccess::profileForUser($request->user()));
    }
}
