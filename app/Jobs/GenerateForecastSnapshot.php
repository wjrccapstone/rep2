<?php

namespace App\Jobs;

use App\Services\PythonSarimaForecastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateForecastSnapshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 420;

    public function __construct(
        public string $metric,
        public array $series,
        public int $forecastMonths,
        public int $seasonalPeriod = 12,
        public int $confidence = 95,
    ) {}

    public function handle(): void
    {
        $service = app(PythonSarimaForecastService::class);
        $fingerprint = hash('crc32', implode(',', $this->series));
        $lockKey = 'forecast:queue:'.$this->metric.':'.$this->forecastMonths.':'.$this->confidence.':'.$fingerprint;
        $refreshKey = 'forecast:refreshing:'.$this->metric.':'.$this->forecastMonths.':'.$this->confidence.':'.$fingerprint;
        $refreshFailedKey = 'forecast:refresh-failed:'.$this->metric.':'.$this->forecastMonths.':'.$this->confidence.':'.$fingerprint;
        $completed = false;

        try {
            $report = $service->diagnosticForecastSeries($this->series, $this->forecastMonths, $this->seasonalPeriod, $this->confidence);
            $forecast = $report['forecast'] ?? null;
            $diagnostics = is_array($report['diagnostics'] ?? null) ? $report['diagnostics'] : null;

            if (is_array($forecast) && $forecast !== []) {
                Cache::put('forecast:python:'.$this->metric.':'.$this->forecastMonths.':'.$this->confidence.':'.$fingerprint, $forecast, now()->addMinutes(60));
            }

            if ($diagnostics !== null) {
                Cache::put('forecast:diagnostics:'.$this->metric.':12:'.$fingerprint, $diagnostics, now()->addMinutes(60));
            }

            if (is_array($forecast) && $forecast !== [] && $diagnostics !== null) {
                Cache::put('forecast:generated:'.$this->metric.':'.$this->forecastMonths.':'.$this->confidence.':'.$fingerprint, now()->toIso8601String(), now()->addMinutes(60));
                Cache::forget($refreshKey);
                Cache::forget($refreshFailedKey);
                $completed = true;
            }
        } finally {
            Cache::forget($lockKey);
            if (! $completed && Cache::has($refreshKey)) {
                Cache::forget($refreshKey);
                Cache::put($refreshFailedKey, true, now()->addMinutes(10));
            }
        }
    }
}
