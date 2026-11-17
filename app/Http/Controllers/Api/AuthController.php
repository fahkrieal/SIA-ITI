<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login pengguna (Mahasiswa atau Dosen) dan mengembalikan token Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                message: 'Email atau password yang Anda masukkan salah.',
                errors: [
                    'code' => 'INVALID_CREDENTIALS',
                    'detail' => 'Kombinasi email dan kata sandi tidak cocok.'
                ],
                statusCode: 401
            );
        }

        // Muat relasi profil mahasiswa atau dosen
        $user->load(['mahasiswa', 'dosen']);

        // Buat token akses Sanctum baru
        $token = $user->createToken('sia-auth-token', [$user->role])->plainTextToken;

        return $this->successResponse(
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ],
            message: 'Login berhasil.',
            statusCode: 200
        );
    }

    /**
     * Dapatkan data profil pengguna yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['mahasiswa', 'dosen']);

        return $this->successResponse(
            data: new UserResource($user),
            message: 'Profil pengguna berhasil diambil.',
            statusCode: 200
        );
    }

    /**
     * Logout pengguna dan cabut token saat ini.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            data: null,
            message: 'Logout berhasil. Token telah dicabut.',
            statusCode: 200
        );
    }
}
