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
    ) {}

    public function handle(): void
    {
        $service = app(PythonSarimaForecastService::class);
        $fingerprint = hash('crc32', implode(',', $this->series));
        $lockKey = 'forecast:queue:'.$this->metric.':'.$this->forecastMonths.':'.$fingerprint;

        try {
            $forecast = $service->forecastSeries($this->series, $this->forecastMonths, $this->seasonalPeriod);
            $diagnostics = $service->diagnosticForecastSeries($this->series, 12, $this->seasonalPeriod);

            if (is_array($forecast) && $forecast !== []) {
                Cache::put('forecast:python:'.$this->metric.':'.$this->forecastMonths.':'.$fingerprint, $forecast, now()->addMinutes(60));
            }

            if ($diagnostics !== null) {
                Cache::put('forecast:diagnostics:'.$this->metric.':12:'.$fingerprint, $diagnostics, now()->addMinutes(60));
            }
        } finally {
            Cache::forget($lockKey);
        }
    }
}
