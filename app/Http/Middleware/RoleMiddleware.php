<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use ApiResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse(
                message: 'Unauthenticated. Silakan login terlebih dahulu.',
                errors: ['code' => 'UNAUTHENTICATED'],
                statusCode: 401
            );
        }

        if (! in_array($user->role, $roles, true)) {
            return $this->errorResponse(
                message: 'Forbidden. Anda tidak memiliki hak akses untuk tindakan ini.',
                errors: ['code' => 'FORBIDDEN_ACCESS'],
                statusCode: 403
            );
        }

        return $next($request);
    }
}
