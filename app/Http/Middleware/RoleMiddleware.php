<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActivityLog;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
        }

        if (! $user || ! in_array($user->role, $roles)) {
            ActivityLog::record('forbidden', 'Mencoba membuka ' . $request->path() . ' tanpa izin');
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}