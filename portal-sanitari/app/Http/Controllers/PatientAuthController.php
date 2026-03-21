<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PatientAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('pacient.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|max:20',
        ], [
            'dni.required' => 'L\'ID de pacient és obligatori.',
        ]);

        // Simulem login desant l'ID a la sessió (com a prova pilot del hackathon)
        session(['pacient_id' => $request->dni]);

        return redirect()->route('pacient.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('pacient_id');
        return redirect()->route('pacient.login');
    }
}
