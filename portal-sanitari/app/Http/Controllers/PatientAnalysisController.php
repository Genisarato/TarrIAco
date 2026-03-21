<?php

namespace App\Http\Controllers;

use App\Services\PythonApiService;
use Illuminate\Http\Request;

class PatientAnalysisController extends Controller
{
    /**
     * Mostra el formulari d'anàlisi de pacient.
     */
    public function index()
    {
        return view('dashboard');
    }

    /**
     * Envia el DNI a l'API Python i retorna els resultats.
     */
    public function analyze(Request $request, PythonApiService $api)
    {
        $request->validate([
            'dni' => ['required', 'string', 'max:20'],
        ], [
            'dni.required' => 'El DNI/NIE és obligatori.',
        ]);

        $result = $api->analyzePatient($request->input('dni'));

        if ($result['success']) {
            return view('dashboard', [
                'dni'      => $request->input('dni'),
                'resultat' => $result['data'],
            ]);
        }

        return back()
            ->withInput()
            ->withErrors(['api' => $result['error']]);
    }
}
