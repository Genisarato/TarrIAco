<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Servei per comunicar-se amb l'API Python de prediccions.
 *
 * Configura la URL base a .env:
 *   PYTHON_API_URL=http://localhost:5000
 */
class PythonApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.python_api.url', 'http://localhost:5000'), '/');
    }

    /**
     * Obté l'anàlisi predictiva d'un pacient pel seu DNI.
     *
     * @param string $dni
     * @return array  ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    public function analyzePatient(string $dni): array
    {
        return $this->request('POST', '/api/analyze', ['dni' => $dni]);
    }

    /**
     * Obté les dades de l'usuari promig d'un grup.
     *
     * @param string $grup
     * @return array  ['success' => bool, 'data' => mixed, 'error' => string|null]
     */
    public function getGroupClassification(string $grup): array
    {
        return $this->request('GET', '/api/classificacio', ['grup' => $grup]);
    }

    /**
     * Petició genèrica a l'API Python.
     */
    protected function request(string $method, string $endpoint, array $params = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;

            $response = match (strtoupper($method)) {
                'GET'  => Http::timeout(15)->get($url, $params),
                'POST' => Http::timeout(15)->post($url, $params),
                default => throw new \InvalidArgumentException("Mètode HTTP no suportat: {$method}"),
            };

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                    'error'   => null,
                ];
            }

            return [
                'success' => false,
                'data'    => null,
                'error'   => "Error de l'API (HTTP {$response->status()}): " . $response->body(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => "No s'ha pogut connectar amb l'API Python ({$this->baseUrl}). Assegura't que està en marxa.",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'data'    => null,
                'error'   => "Error inesperat: " . $e->getMessage(),
            ];
        }
    }
}
