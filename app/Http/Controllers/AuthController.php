<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan form login
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login (mendukung Email maupun Username)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Silakan masukkan alamat email atau username Anda.',
            'password.required' => 'Silakan masukkan kata sandi.',
        ]);

        $loginInput = trim($credentials['login']);
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Tentukan apakah input berupa email atau username
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Cari user terlebih dahulu untuk memeriksa status aktif
        $user = User::where($field, $loginInput)->first();

        // Jika tidak ditemukan dengan username, coba periksa apakah kolom username null dan email cocok
        if (! $user && $field === 'username') {
            $user = User::where('email', $loginInput)->first();
            if ($user) {
                $field = 'email';
            }
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withInput($request->only('login', 'remember'))->withErrors([
                'login' => 'Email/Username atau kata sandi yang Anda masukkan tidak sesuai.',
            ]);
        }

        if (! $user->is_active) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Akun Anda saat ini dinonaktifkan oleh Administrator. Hubungi Super Admin.',
            ]);
        }

        // Lakukan login
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('success', 'Selamat datang kembali, '.$user->name.'!');
    }

    /**
     * Logout pengguna
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil keluar dari sistem.');
    }
}
