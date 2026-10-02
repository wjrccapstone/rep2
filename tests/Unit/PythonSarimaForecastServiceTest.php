<?php

namespace Tests\Unit;

use App\Jobs\GenerateForecastSnapshot;
use App\Services\ForecastService;
use App\Services\PythonSarimaForecastService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PythonSarimaForecastServiceTest extends TestCase
{
    public function test_it_generates_a_six_year_monthly_forecast_from_a_synthetic_series(): void
    {
        config([
            'services.python_forecast.url' => 'https://sarima.example.test',
            'services.python_forecast.token' => 'test-token',
        ]);

        Http::fake([
            'https://sarima.example.test/forecast' => Http::response([
                'forecast' => array_fill(0, 72, [
                    'value' => 125,
                    'lower' => 100,
                    'upper' => 150,
                    'model_order' => 'SARIMA(1,1,1)(1,1,1,12)',
                ]),
                'model_order' => 'SARIMA(1,1,1)(1,1,1,12)',
            ]),
        ]);

        $service = new PythonSarimaForecastService();

        $series = [];
        $base = 120;
        for ($month = 0; $month < 72; $month++) {
            $seasonal = [1 => 0.82, 2 => 0.88, 3 => 0.94, 4 => 0.99, 5 => 1.08, 6 => 1.23, 7 => 1.4, 8 => 1.18, 9 => 0.87, 10 => 0.92, 11 => 1.27, 12 => 1.32][(int) date('n', strtotime("2020-01-01 +{$month} months"))] ?? 1.0;
            $trend = 1 + ($month * 0.012);
            $noise = 1 + (sin($month / 2.3) * 0.08);
            $series[] = round($base * $seasonal * $trend * $noise, 2);
        }

        $forecast = $service->forecastSeries($series, 72, 12, 99);

        $this->assertIsArray($forecast);
        $this->assertCount(72, $forecast);
        $this->assertTrue(str_starts_with((string) ($forecast[0]['model_order'] ?? ''), 'SARIMA'));
        $this->assertTrue(($forecast[0]['value'] ?? 0.0) >= 0);
        $this->assertTrue(($forecast[71]['value'] ?? 0.0) >= 0);

        Http::assertSent(fn ($request) => $request->url() === 'https://sarima.example.test/forecast'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && count($request['values']) === 72
            && $request['steps'] === 72
            && $request['seasonal_period'] === 12
            && $request['confidence'] === 99);
    }

    public function test_it_returns_box_jenkins_diagnostics_for_the_synthetic_series(): void
    {
        config([
            'services.python_forecast.url' => 'https://sarima.example.test',
            'services.python_forecast.token' => 'test-token',
        ]);

        Http::fake([
            'https://sarima.example.test/diagnostics' => Http::response([
                'forecast' => [],
                'diagnostics' => [
                    'adf_pvalue' => 0.01,
                    'stationary' => true,
                    'selected_order' => 'SARIMA(1,1,1)(1,1,1,12)',
                    'aic' => 135.2,
                    'bic' => 140.9,
                    'ljung_box_pvalue' => 0.45,
                    'residual_ok' => true,
                ],
            ]),
        ]);

        $service = new PythonSarimaForecastService();

        $series = [];
        $base = 120;
        for ($month = 0; $month < 72; $month++) {
            $seasonal = [1 => 0.82, 2 => 0.88, 3 => 0.94, 4 => 0.99, 5 => 1.08, 6 => 1.23, 7 => 1.4, 8 => 1.18, 9 => 0.87, 10 => 0.92, 11 => 1.27, 12 => 1.32][(int) date('n', strtotime("2020-01-01 +{$month} months"))] ?? 1.0;
            $trend = 1 + ($month * 0.012);
            $noise = 1 + (sin($month / 2.3) * 0.08);
            $series[] = round($base * $seasonal * $trend * $noise, 2);
        }

        $report = $service->diagnosticForecastSeries($series, 12, 12);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('forecast', $report);
        $this->assertArrayHasKey('diagnostics', $report);

        $diagnostics = $report['diagnostics'];
        $this->assertArrayHasKey('adf_pvalue', $diagnostics);
        $this->assertArrayHasKey('stationary', $diagnostics);
        $this->assertArrayHasKey('selected_order', $diagnostics);
        $this->assertArrayHasKey('aic', $diagnostics);
        $this->assertArrayHasKey('bic', $diagnostics);
        $this->assertArrayHasKey('ljung_box_pvalue', $diagnostics);
        $this->assertArrayHasKey('residual_ok', $diagnostics);
        $this->assertTrue(is_string($diagnostics['selected_order']) && $diagnostics['selected_order'] !== '');

        Http::assertSent(fn ($request) => $request->url() === 'https://sarima.example.test/diagnostics'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['steps'] === 12
            && $request['seasonal_period'] === 12);
    }

    public function test_it_reuses_the_same_forecast_results_within_one_request(): void
    {
        $service = new class extends ForecastService {
            public int $pythonForecastCalls = 0;
            public int $diagnosticCalls = 0;

            protected function monthlySeries(string $metric): Collection
            {
                $values = [];
                for ($i = 0; $i < 24; $i++) {
                    $values[] = [
                        'date' => now()->subMonths(24 - $i)->startOfMonth(),
                        'month' => (int) now()->subMonths(24 - $i)->format('n'),
                        'value' => 120 + ($i * 5),
                    ];
                }

                return collect($values);
            }

            protected function cachedPythonForecast(string $metric, array $ys, int $forecastMonths, int $confidence): ?array
            {
                $this->pythonForecastCalls++;

                return [
                    ['value' => 125, 'lower' => 100, 'upper' => 150],
                    ['value' => 130, 'lower' => 110, 'upper' => 160],
                ];
            }

            protected function cachedPythonDiagnostics(string $metric, array $ys, int $steps): ?array
            {
                $this->diagnosticCalls++;

                return [
                    'diagnostics' => [
                        'stationary' => true,
                        'adf_pvalue' => 0.01,
                        'selected_order' => '(1,0,0)(1,0,0,12)',
                        'aic' => 135.2,
                        'bic' => 140.9,
                        'ljung_box_pvalue' => 0.45,
                        'residual_ok' => true,
                    ],
                ];
            }
        };

        $first = $service->compute(95, 12, 'demand');
        $second = $service->compute(95, 12, 'demand');

        $this->assertSame($first['forecastPeriodLabel'], $second['forecastPeriodLabel']);
        $this->assertSame(1, $service->pythonForecastCalls);
        $this->assertSame(1, $service->diagnosticCalls);
        $this->assertSame('SARIMA (Python)', $first['modelOrder']);
        $this->assertTrue($first['hasData']);
    }

    public function test_it_queues_a_missing_snapshot_and_returns_without_inline_fitting(): void
    {
        $values = range(120, 143);
        $fingerprint = hash('crc32', implode(',', $values));
        Cache::forget('forecast:python:demand:12:95:'.$fingerprint);
        Cache::forget('forecast:queue:demand:12:95:'.$fingerprint);
        Queue::fake();

        $service = new class($values) extends ForecastService {
            public function __construct(private array $values) {}

            protected function monthlySeries(string $metric): Collection
            {
                return collect($this->values)->values()->map(fn ($value, $index) => [
                    'date' => now()->startOfMonth()->subMonths(count($this->values) - $index - 1),
                    'month' => (int) now()->startOfMonth()->subMonths(count($this->values) - $index - 1)->format('n'),
                    'value' => $value,
                ]);
            }
        };

        $result = $service->compute(95, 12, 'demand');

        $this->assertFalse($result['hasData']);
        $this->assertTrue($result['forecastPending']);
        Queue::assertPushed(GenerateForecastSnapshot::class);
    }

    public function test_refresh_clears_the_selected_snapshot_and_queues_the_requested_confidence(): void
    {
        $values = range(120, 143);
        $fingerprint = hash('crc32', implode(',', $values));
        $forecastKey = 'forecast:python:demand:12:90:'.$fingerprint;
        $diagnosticKey = 'forecast:diagnostics:demand:12:'.$fingerprint;
        $generatedKey = 'forecast:generated:demand:12:90:'.$fingerprint;

        Cache::put($forecastKey, [['value' => 1]]);
        Cache::put($diagnosticKey, ['diagnostics' => ['aic' => 1]]);
        Cache::put($generatedKey, now()->toIso8601String());
        Queue::fake();

        $service = new class($values) extends ForecastService {
            public function __construct(private array $values) {}

            protected function monthlySeries(string $metric): Collection
            {
                return collect($this->values)->values()->map(fn ($value, $index) => [
                    'date' => now()->startOfMonth()->subMonths(count($this->values) - $index - 1),
                    'month' => (int) now()->startOfMonth()->subMonths(count($this->values) - $index - 1)->format('n'),
                    'value' => $value,
                ]);
            }
        };

        $this->assertTrue($service->refreshSnapshot(90, 12, 'demand'));
        $this->assertFalse(Cache::has($forecastKey));
        $this->assertFalse(Cache::has($diagnosticKey));
        $this->assertFalse(Cache::has($generatedKey));
        Queue::assertPushed(GenerateForecastSnapshot::class, fn ($job) => $job->confidence === 90 && $job->series === $values);
    }

    public function test_it_does_not_queue_a_seasonal_forecast_with_fewer_than_two_years_of_history(): void
    {
        Queue::fake();

        $service = new class extends ForecastService {
            protected function monthlySeries(string $metric): Collection
            {
                return collect(range(1, 23))->map(fn ($value, $index) => [
                    'date' => now()->startOfMonth()->subMonths(22 - $index),
                    'month' => (int) now()->startOfMonth()->subMonths(22 - $index)->format('n'),
                    'value' => $value,
                ]);
            }
        };

        $result = $service->compute(95, 12, 'demand');

        $this->assertFalse($result['forecastPending']);
        Queue::assertNothingPushed();
    }

    public function test_snapshot_job_caches_fresh_results_and_releases_the_confidence_lock(): void
    {
        config([
            'services.python_forecast.url' => 'https://sarima.example.test',
            'services.python_forecast.token' => 'test-token',
        ]);

        $values = range(120, 143);
        $fingerprint = hash('crc32', implode(',', $values));
        $lockKey = 'forecast:queue:demand:12:90:'.$fingerprint;
        Cache::put($lockKey, true);
        Http::fake([
            'https://sarima.example.test/forecast' => Http::response([
                'forecast' => array_fill(0, 12, ['value' => 125, 'lower' => 100, 'upper' => 150]),
            ]),
            'https://sarima.example.test/diagnostics' => Http::response([
                'diagnostics' => ['selected_order' => 'SARIMA(1,1,1)(1,1,1,12)'],
            ]),
        ]);

        $job = new GenerateForecastSnapshot('demand', $values, 12, 12, 90);
        $job->handle();

        $this->assertFalse(Cache::has($lockKey));
        $this->assertTrue(Cache::has('forecast:python:demand:12:90:'.$fingerprint));
        $this->assertTrue(Cache::has('forecast:diagnostics:demand:12:'.$fingerprint));
        $this->assertTrue(Cache::has('forecast:generated:demand:12:90:'.$fingerprint));
        Http::assertSent(fn ($request) => $request->url() === 'https://sarima.example.test/forecast'
            && $request['confidence'] === 90);
    }
}
