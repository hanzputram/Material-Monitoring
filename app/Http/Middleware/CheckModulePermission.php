<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Handle an incoming request.
     *
     * Mendukung beberapa modul sekaligus (cukup salah satu), mis. "module:materials,rab".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $module = implode(', ', $modules);
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->with('info', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['login' => 'Akun Anda dinonaktifkan oleh administrator.']);
        }

        if (! $user->hasAnyModulePermission($modules)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses Ditolak: Anda tidak memiliki izin akses untuk modul '{$module}'.",
                ], 403);
            }

            abort(403, "Akses Ditolak: Akun Anda tidak memiliki izin untuk membuka modul '{$module}'. Hubungi Super Administrator.");
        }

        return $next($request);
    }
}
