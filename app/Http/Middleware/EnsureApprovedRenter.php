<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedRenter
{
    /**
     * Periksa persetujuan akun sebelum penyewa dapat merencanakan atau mengonfirmasi sewa baru.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canRent(), 403);

        return $next($request);
    }
}
