<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ForecastService;
use Illuminate\Http\Request;

class ForecastingController extends Controller
{
    public function index(Request $request, ForecastService $forecastService)
    {
        $confidence = (int) $request->query('confidence', 95);
        $years = $request->query('years', 'all');
        $months = match ($years) {
            '1' => 12,
            '3' => 36,
            default => 72,
        };
        $metric = match ($request->query('metric', 'demand')) {
            'revenue' => 'revenue',
            'product_sales' => 'product_sales',
            default => 'demand',
        };

        // Within the Sales Revenue tab, "View by" switches between the monthly forecast (the
        // SARIMA pipeline below) and a plain daily actuals report — the two are independent
        // enough (different query, no model) that daily is computed separately, only when asked for.
        $granularity = $metric === 'revenue' && $request->query('view') === 'daily' ? 'daily' : 'monthly';

        $result = $forecastService->compute($confidence, $months, $metric);

        // Reuse the same forecast payload for the supporting panels instead of re-running the
        // expensive Python SARIMA fit again for the same metric/horizon on the same page load.
        $revenueResult = $metric === 'revenue' ? $result : $forecastService->compute($confidence, $months, 'revenue');

        $salesTarget = (float) (Setting::current()->six_year_sales_target ?? 0);
        $targetProgress = null;
        if ($salesTarget > 0 && $revenueResult['hasData']) {
            $targetProgress = [
                'target' => $salesTarget,
                'projected' => $revenueResult['forecastTotal'],
                'percent' => min(999, round(($revenueResult['forecastTotal'] / $salesTarget) * 100)),
            ];
        }

        $descriptive = $forecastService->descriptiveSummary($confidence, 3, $metric, $result, $revenueResult);

        // "What's in demand?" ranks job-order services on the Job-Order tab, switches to product
        // categories/products (selectable in the UI) on Product Sales, and on Sales Revenue
        // becomes "Top earners" — services and products combined, ranked by revenue instead of
        // volume since the two aren't on a comparable count scale.
        $topServices = $metric === 'demand' ? $forecastService->topServices() : [];
        $topCategories = $metric === 'product_sales' ? $forecastService->topProductCategories() : [];
        $topProducts = $metric === 'product_sales' ? $forecastService->topProducts() : [];
        $topEarners = $metric === 'revenue' ? $forecastService->topEarners() : [];
        $daily = $granularity === 'daily' ? $forecastService->dailyRevenue(30) : null;
        // "View Details" describes whatever the chart is currently showing: the 6-year monthly
        // forecast, or — in the Sales Revenue → Daily view — the trailing daily actuals.
        $viewDetails = $granularity === 'daily'
            ? $forecastService->dailyRevenueDetails(30)
            : $forecastService->viewDetails($confidence, $metric, $result);

        // Backs the "Day-of-Week Patterns" mini-tab on the Job-Order / Product Sales views only —
        // Sales Revenue has its own Monthly/Daily toggle instead.
        $dow = $metric !== 'revenue' ? $forecastService->dayOfWeekPattern($metric) : null;
        $holiday = $metric !== 'revenue' ? $forecastService->holidayImpact($metric) : null;

        return view('forecasting.index', [
            'metric' => $metric,
            'granularity' => $granularity,
            'result' => $result,
            'revenueResult' => $revenueResult,
            'targetProgress' => $targetProgress,
            'descriptive' => $descriptive,
            'topServices' => $topServices,
            'topCategories' => $topCategories,
            'topProducts' => $topProducts,
            'topEarners' => $topEarners,
            'viewDetails' => $viewDetails,
            'years' => $years,
            'daily' => $daily,
            'dow' => $dow,
            'holiday' => $holiday,
        ]);
    }
}
