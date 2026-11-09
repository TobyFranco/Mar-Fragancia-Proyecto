<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $d = $request->validated();
        $u = Usuario::whereRaw('LOWER(email) = ?', [Str::lower($d['email'])])->first();
        if (! $u || ! Hash::check($d['password'], $u->password)) {
            throw ValidationException::withMessages(['email' => 'Las credenciales no son válidas.']);
        }

        return response()->json(['token' => $u->createToken($d['device_name'])->plainTextToken, 'usuario' => new UsuarioResource($u->load(['rol', 'cliente']))]);
    }

    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user()->load(['rol', 'cliente']));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revocado.']);
    }
}
