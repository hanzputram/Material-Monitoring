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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('info', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['login' => 'Akun Anda dinonaktifkan oleh administrator.']);
        }

        if (!$user->hasModulePermission($module)) {
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
