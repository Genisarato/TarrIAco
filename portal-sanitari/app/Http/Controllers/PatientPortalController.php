<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PatientPortalController extends Controller
{
    public function index(Request $request)
    {
        $pacient_id = session('pacient_id');
        
        if (!$pacient_id) {
            return redirect()->route('pacient.login')->withErrors(['La teva sessió ha caducat, si us plau, torna a identificar-te.']);
        }

        try {
            // Cridar al nou endpoint de la API Python
            $response = Http::timeout(120)->post('http://127.0.0.1:5000/api/pacient-info', [
                'id_pacient' => $pacient_id,
            ]);

            if ($response->successful()) {
                return view('pacient.dashboard', [
                    'pacient_id' => $pacient_id,
                    'resultat' => $response->json()
                ]);
            } else {
                return back()->withErrors(['Error al carregar les teves dades clíniques: ' . $response->body()]);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['Error de connexió amb el sistema intel·ligent: ' . $e->getMessage()]);
        }
    }
}
