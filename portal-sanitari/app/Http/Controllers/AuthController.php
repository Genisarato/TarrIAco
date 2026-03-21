<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Mostra el formulari de login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Processa el login de l'usuari.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => "El nom d'usuari és obligatori.",
            'password.required' => 'La contrasenya és obligatòria.',
        ]);

        // Intentem autenticar amb el camp 'name' com a username
        if (Auth::attempt(['name' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'username' => 'Les credencials no són correctes.',
        ])->onlyInput('username');
    }

    /**
     * Tanca la sessió de l'usuari.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
