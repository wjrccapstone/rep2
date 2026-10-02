<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PythonSarimaForecastService
{
    public function forecastSeries(array $values, int $steps = 72, int $seasonalPeriod = 12, int $confidence = 95): ?array
    {
        $values = array_values(array_map(fn ($value) => (float) $value, $values));

        if ($steps < 1 || $values === []) {
            return [];
        }

        $payload = $this->post('/forecast', [
            'values' => $values,
            'steps' => $steps,
            'seasonal_period' => $seasonalPeriod,
            'confidence' => $confidence,
        ]);

        if (! is_array($payload) || ! isset($payload['forecast']) || ! is_array($payload['forecast'])) {
            return null;
        }

        return $payload['forecast'];
    }

    public function diagnosticForecastSeries(array $values, int $steps = 12, int $seasonalPeriod = 12, int $confidence = 95): ?array
    {
        $values = array_values(array_map(fn ($value) => (float) $value, $values));

        if ($steps < 1 || $values === []) {
            return null;
        }

        return $this->post('/diagnostics', [
            'values' => $values,
            'steps' => $steps,
            'seasonal_period' => $seasonalPeriod,
            'confidence' => $confidence,
        ]);
    }

    private function post(string $path, array $payload): ?array
    {
        $baseUrl = rtrim((string) config('services.python_forecast.url'), '/');
        $token = (string) config('services.python_forecast.token');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout((int) config('services.python_forecast.connect_timeout', 10))
                ->timeout((int) config('services.python_forecast.timeout', 180))
                ->post($baseUrl.$path, $payload);
        } catch (ConnectionException) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }
}
