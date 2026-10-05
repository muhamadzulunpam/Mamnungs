<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

         if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            ActivityLog::record(
                'login_failed',
                'Gagal login dengan email ' . $credentials['email'],
                null,
                ['email' => $credentials['email']],
            );
                return back()->withErrors([
                'email' => 'Email atau password salah, atau akun dinonaktifkan.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();
        ActivityLog::record('login', 'Login berhasil');

        return redirect()->intended($this->homeFor(Auth::user()->role));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout', 'Logout'); 

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function homeFor(string $role): string
    {
        return $role === 'admin' ? '/admin/dashboard' : '/kasir';
    }
}