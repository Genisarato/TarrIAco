<?php

namespace App\Http\Controllers;

use App\Services\PythonApiService;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    /**
     * Mostra la pàgina de classificació de rols.
     * Si es passa ?grup=X, consulta l'API Python per obtenir l'usuari promig.
     */
    public function index(Request $request, PythonApiService $api)
    {
        $grup     = $request->query('grup');
        $resultat = null;
        $error    = null;

        if ($grup) {
            $result = $api->getGroupClassification($grup);

            if ($result['success']) {
                $resultat = $result['data'];
            } else {
                $error = $result['error'];
            }
        }

        return view('classificacio', compact('grup', 'resultat', 'error'));
    }
}
