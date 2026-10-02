<?php

namespace App\Services\Forecasting;

/**
 * Seasonal ARIMA (p,d,q)(P,D,Q)s.
 *
 * Fit: differences the series (d regular, D seasonal), de-means the result, and
 * estimates AR/MA coefficients by minimizing the conditional sum of squares (CSS)
 * with a Nelder-Mead simplex search (no external numerical library available).
 *
 * Forecast: point forecasts are produced by recursively forecasting the
 * differenced/centered series and integrating back through each differencing
 * step (classical Box-Jenkins approach, so drift/mean is handled naturally).
 * Forecast-error variance uses the psi-weight (MA(infinity)) expansion of the
 * *fully* expanded polynomial — AR/MA combined with the differencing operators —
 * which is the standard way ARIMA prediction intervals are derived.
 */
class Sarima
{
    /** @var float[] */
    public array $phi = [];

    /** @var float[] */
    public array $theta = [];

    /** @var float[] */
    public array $seasonalPhi = [];

    /** @var float[] */
    public array $seasonalTheta = [];

    public float $mean = 0.0;

    public float $sigma2 = 0.0;

    public float $aicc = INF;

    public bool $fitted = false;

    /** @var float[] */
    private array $y;

    /** @var array<int, float[]> levels[0] = original series ... levels[last] = fully differenced series */
    private array $levels = [];

    /** @var int[] lag used to produce levels[i+1] from levels[i] */
    private array $lags = [];

    /** @var float[] fully differenced series (levels[last]) */
    private array $w = [];

    /** @var float[] residuals of the centered, differenced series */
    private array $residuals = [];

    public function __construct(
        array $y,
        public readonly int $p,
        public readonly int $d,
        public readonly int $q,
        public readonly int $P,
        public readonly int $D,
        public readonly int $Q,
        public readonly int $s,
    ) {
        $this->y = array_values(array_map(fn ($v) => (float) $v, $y));
    }

    public static function search(array $y, int $s = 12): ?self
    {
        $n = count($y);
        if ($n < 4) {
            return null;
        }

        $d = self::detectRegularDiff($y);
        $seasonalOk = $s > 1 && $n >= (2 * $s + 6);
        $D = $seasonalOk ? self::detectSeasonalDiff($y, $d, $s) : 0;

        $pMax = $n >= 30 ? 2 : ($n >= 16 ? 1 : 0);
        $qMax = $pMax;
        $PMax = $seasonalOk ? 1 : 0;
        $QMax = $PMax;

        $best = null;
        foreach (range(0, $pMax) as $p) {
            foreach (range(0, $qMax) as $q) {
                foreach (range(0, $PMax) as $P) {
                    foreach (range(0, $QMax) as $Q) {
                        // When there's enough data for a seasonal fit, only consider models that actually
                        // carry a seasonal component (P, D, or Q > 0) — this system standardizes on SARIMA.
                        if ($seasonalOk && $P === 0 && $Q === 0 && $D === 0) {
                            continue;
                        }

                        $k = $p + $q + $P + $Q;
                        $nDiff = $n - $d - $D * $s;
                        if ($nDiff < 10 || $nDiff < ($k + 2) * 3) {
                            continue;
                        }

                        $model = new self($y, $p, $d, $q, $P, $D, $Q, $s);
                        if (! $model->fit()) {
                            continue;
                        }

                        if ($best === null || $model->aicc < $best->aicc) {
                            $best = $model;
                        }
                    }
                }
            }
        }

        return $best;
    }

    public function fit(): bool
    {
        $this->buildLevels();
        $w = $this->w;
        $n = count($w);
        $k = $this->p + $this->q + $this->P + $this->Q;

        if ($n < max(3, $k + 3)) {
            $this->fitted = false;

            return false;
        }

        $this->mean = array_sum($w) / $n;
        $wc = array_map(fn ($v) => $v - $this->mean, $w);
        $burnIn = max($this->p + $this->P * $this->s, $this->q + $this->Q * $this->s, 1);

        $objective = function (array $x) use ($wc, $burnIn) {
            [$phi, $theta, $Phi, $Theta] = $this->unpack($x);
            foreach ([...$phi, ...$theta, ...$Phi, ...$Theta] as $v) {
                if (abs($v) >= 0.98) {
                    return 1e12 + abs($v) * 1e6;
                }
            }

            $e = $this->computeResiduals($wc, $phi, $theta, $Phi, $Theta);
            $sse = 0.0;
            $cnt = 0;
            foreach ($e as $t => $val) {
                if ($t >= $burnIn) {
                    $sse += $val * $val;
                    $cnt++;
                }
            }

            return $cnt > 0 ? $sse : 1e12;
        };

        if ($k === 0) {
            $best = [];
            $bestVal = $objective([]);
        } else {
            [$best, $bestVal] = $this->nelderMead($objective, array_fill(0, $k, 0.1));
            [$best2, $bestVal2] = $this->nelderMead($objective, array_fill(0, $k, -0.1));
            if ($bestVal2 < $bestVal) {
                $best = $best2;
                $bestVal = $bestVal2;
            }
        }

        [$this->phi, $this->theta, $this->seasonalPhi, $this->seasonalTheta] = $this->unpack($best);
        $e = $this->computeResiduals($wc, $this->phi, $this->theta, $this->seasonalPhi, $this->seasonalTheta);
        $this->residuals = $e;

        $used = array_slice($e, $burnIn);
        $nUsed = count($used);
        if ($nUsed < 1) {
            $this->fitted = false;

            return false;
        }

        $sse = array_sum(array_map(fn ($v) => $v * $v, $used));
        $this->sigma2 = max($sse / $nUsed, 1e-6);

        $numParams = $k + 1;
        $logLik = -0.5 * $nUsed * (log(2 * M_PI) + log($this->sigma2) + 1);
        $aic = -2 * $logLik + 2 * $numParams;
        $denom = $nUsed - $numParams - 1;
        $this->aicc = $denom > 0 ? $aic + (2 * $numParams * ($numParams + 1)) / $denom : INF;

        $this->fitted = true;

        return true;
    }

    /**
     * @return array<int, array{value: float, lower: float, upper: float}>
     */
    public function forecast(int $steps, float $z): array
    {
        if (! $this->fitted || $steps < 1) {
            return [];
        }

        [$arCoefs, $maCoefs] = $this->combined($this->phi, $this->seasonalPhi, $this->theta, $this->seasonalTheta);

        $levels = array_map(fn ($lvl) => array_values($lvl), $this->levels);
        $deepest = count($levels) - 1;
        $eHist = $this->residuals;

        $diffPoly = $this->binomialDiffPoly($this->d, 1);
        $seasDiffPoly = $this->binomialDiffPoly($this->D, $this->s);
        $arFullProd = $this->polyMultiply(
            $this->polyMultiply($this->fullPoly($this->phi, 1, true), $this->fullPoly($this->seasonalPhi, $this->s, true)),
            $this->polyMultiply($diffPoly, $seasDiffPoly)
        );
        $arFullCoefs = array_map(fn ($v) => -$v, array_slice($arFullProd, 1));
        $psi = $this->psiWeights($arFullCoefs, $maCoefs, $steps);

        $results = [];
        $cumVar = 0.0;
        for ($h = 1; $h <= $steps; $h++) {
            $w = &$levels[$deepest];
            $t = count($w);

            $arSum = 0.0;
            foreach ($arCoefs as $i => $c) {
                $lag = $i + 1;
                if ($t - $lag >= 0) {
                    $arSum += $c * ($w[$t - $lag] - $this->mean);
                }
            }

            $maSum = 0.0;
            foreach ($maCoefs as $j => $c) {
                $lag = $j + 1;
                if ($t - $lag >= 0 && ($t - $lag) < count($eHist)) {
                    $maSum += $c * $eHist[$t - $lag];
                }
            }

            $wcHat = $arSum + $maSum;
            $wHat = $wcHat + $this->mean;
            $w[] = $wHat;
            $eHist[] = 0.0;
            unset($w);

            $childValue = $wHat;
            for ($i = $deepest; $i >= 1; $i--) {
                $lag = $this->lags[$i - 1];
                $tp = count($levels[$i - 1]);
                $parentVal = $levels[$i - 1][$tp - $lag] + $childValue;
                $levels[$i - 1][] = $parentVal;
                $childValue = $parentVal;
            }

            $cumVar += ($psi[$h - 1] ?? 0.0) ** 2;
            $sd = sqrt(max($this->sigma2 * $cumVar, 0.0));
            $point = $childValue;

            $results[] = [
                'value' => $point,
                'lower' => $point - $z * $sd,
                'upper' => $point + $z * $sd,
            ];
        }

        return $results;
    }

    public function orderLabel(): string
    {
        if ($this->s > 1 && ($this->P + $this->D + $this->Q) > 0) {
            return "SARIMA({$this->p},{$this->d},{$this->q})({$this->P},{$this->D},{$this->Q}){$this->s}";
        }

        return "ARIMA({$this->p},{$this->d},{$this->q})";
    }

    private function buildLevels(): void
    {
        $this->levels = [$this->y];
        $this->lags = [];
        $current = $this->y;

        for ($i = 0; $i < $this->d; $i++) {
            $next = [];
            for ($t = 1; $t < count($current); $t++) {
                $next[] = $current[$t] - $current[$t - 1];
            }
            $this->levels[] = $next;
            $this->lags[] = 1;
            $current = $next;
        }

        for ($i = 0; $i < $this->D; $i++) {
            $next = [];
            for ($t = $this->s; $t < count($current); $t++) {
                $next[] = $current[$t] - $current[$t - $this->s];
            }
            $this->levels[] = $next;
            $this->lags[] = $this->s;
            $current = $next;
        }

        $this->w = $current;
    }

    private function unpack(array $x): array
    {
        $i = 0;
        $phi = array_slice($x, $i, $this->p);
        $i += $this->p;
        $theta = array_slice($x, $i, $this->q);
        $i += $this->q;
        $Phi = array_slice($x, $i, $this->P);
        $i += $this->P;
        $Theta = array_slice($x, $i, $this->Q);

        return [$phi, $theta, $Phi, $Theta];
    }

    private function computeResiduals(array $wc, array $phi, array $theta, array $Phi, array $Theta): array
    {
        [$arCoefs, $maCoefs] = $this->combined($phi, $Phi, $theta, $Theta);
        $n = count($wc);
        $e = array_fill(0, $n, 0.0);

        for ($t = 0; $t < $n; $t++) {
            $arSum = 0.0;
            foreach ($arCoefs as $i => $c) {
                $lag = $i + 1;
                if ($t - $lag >= 0) {
                    $arSum += $c * $wc[$t - $lag];
                }
            }

            $maSum = 0.0;
            foreach ($maCoefs as $j => $c) {
                $lag = $j + 1;
                if ($t - $lag >= 0) {
                    $maSum += $c * $e[$t - $lag];
                }
            }

            $e[$t] = $wc[$t] - $arSum - $maSum;
        }

        return $e;
    }

    /**
     * Multiplies the regular and seasonal AR polynomials together (and separately
     * the MA ones), returning additive coefficients: w_t = sum(arCoefs[i] * w_{t-i-1}) + e_t + sum(maCoefs[j] * e_{t-j-1}).
     */
    private function combined(array $phi, array $Phi, array $theta, array $Theta): array
    {
        $arProd = $this->polyMultiply($this->fullPoly($phi, 1, true), $this->fullPoly($Phi, $this->s, true));
        $arCoefs = array_map(fn ($v) => -$v, array_slice($arProd, 1));

        $maProd = $this->polyMultiply($this->fullPoly($theta, 1, false), $this->fullPoly($Theta, $this->s, false));
        $maCoefs = array_slice($maProd, 1);

        return [$arCoefs, $maCoefs];
    }

    /**
     * Full polynomial (leading coefficient 1) for a set of lag coefficients spaced `step` apart.
     * AR polynomials are built as (1 - c1 B^step - c2 B^2*step - ...); MA as (1 + c1 B^step + ...).
     */
    private function fullPoly(array $coeffs, int $step, bool $negate): array
    {
        $len = 1 + count($coeffs) * $step;
        $poly = array_fill(0, max($len, 1), 0.0);
        $poly[0] = 1.0;
        foreach ($coeffs as $i => $c) {
            $poly[($i + 1) * $step] = $negate ? -$c : $c;
        }

        return $poly;
    }

    private function binomialDiffPoly(int $d, int $step): array
    {
        $poly = [1.0];
        if ($d <= 0) {
            return $poly;
        }

        $factor = array_fill(0, $step + 1, 0.0);
        $factor[0] = 1.0;
        $factor[$step] = -1.0;

        for ($i = 0; $i < $d; $i++) {
            $poly = $this->polyMultiply($poly, $factor);
        }

        return $poly;
    }

    private function polyMultiply(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0.0);
        foreach ($a as $i => $av) {
            if ($av === 0.0) {
                continue;
            }
            foreach ($b as $j => $bv) {
                if ($bv !== 0.0) {
                    $result[$i + $j] += $av * $bv;
                }
            }
        }

        return $result;
    }

    /**
     * Psi-weights of the MA(infinity) representation of arFullCoefs/maCoefs, used to
     * grow the forecast-error variance with horizon: Var(h) = sigma2 * sum(psi[0..h-1]^2).
     */
    private function psiWeights(array $arFullCoefs, array $maCoefs, int $steps): array
    {
        $psi = [1.0];
        for ($j = 1; $j < $steps; $j++) {
            $val = $maCoefs[$j - 1] ?? 0.0;
            foreach ($arFullCoefs as $i => $c) {
                $lag = $i + 1;
                if ($j - $lag >= 0) {
                    $val += $c * $psi[$j - $lag];
                }
            }
            $psi[$j] = $val;
        }

        return $psi;
    }

    /**
     * @return array{0: float[], 1: float}
     */
    private function nelderMead(callable $f, array $x0, float $tol = 1e-8, int $maxIter = 400): array
    {
        $n = count($x0);
        if ($n === 0) {
            return [[], $f([])];
        }

        $alpha = 1.0;
        $gamma = 2.0;
        $rho = 0.5;
        $sigma = 0.5;

        $simplex = [$x0];
        for ($i = 0; $i < $n; $i++) {
            $point = $x0;
            $step = $point[$i] !== 0.0 ? 0.1 * abs($point[$i]) : 0.1;
            $point[$i] += $step;
            $simplex[] = $point;
        }
        $fvals = array_map($f, $simplex);

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $order = range(0, $n);
            usort($order, fn ($a, $b) => $fvals[$a] <=> $fvals[$b]);
            $simplex = array_map(fn ($i) => $simplex[$i], $order);
            $fvals = array_map(fn ($i) => $fvals[$i], $order);

            if (abs($fvals[$n] - $fvals[0]) < $tol) {
                break;
            }

            $worst = $simplex[$n];
            $centroid = array_fill(0, $n, 0.0);
            for ($i = 0; $i < $n; $i++) {
                for ($k = 0; $k < $n; $k++) {
                    $centroid[$k] += $simplex[$i][$k] / $n;
                }
            }

            $xr = [];
            for ($k = 0; $k < $n; $k++) {
                $xr[$k] = $centroid[$k] + $alpha * ($centroid[$k] - $worst[$k]);
            }
            $fxr = $f($xr);

            if ($fxr < $fvals[0]) {
                $xe = [];
                for ($k = 0; $k < $n; $k++) {
                    $xe[$k] = $centroid[$k] + $gamma * ($xr[$k] - $centroid[$k]);
                }
                $fxe = $f($xe);
                if ($fxe < $fxr) {
                    $simplex[$n] = $xe;
                    $fvals[$n] = $fxe;
                } else {
                    $simplex[$n] = $xr;
                    $fvals[$n] = $fxr;
                }
            } elseif ($fxr < $fvals[$n - 1]) {
                $simplex[$n] = $xr;
                $fvals[$n] = $fxr;
            } else {
                $xc = [];
                for ($k = 0; $k < $n; $k++) {
                    $xc[$k] = $centroid[$k] + $rho * ($worst[$k] - $centroid[$k]);
                }
                $fxc = $f($xc);
                if ($fxc < $fvals[$n]) {
                    $simplex[$n] = $xc;
                    $fvals[$n] = $fxc;
                } else {
                    for ($i = 1; $i <= $n; $i++) {
                        for ($k = 0; $k < $n; $k++) {
                            $simplex[$i][$k] = $simplex[0][$k] + $sigma * ($simplex[$i][$k] - $simplex[0][$k]);
                        }
                        $fvals[$i] = $f($simplex[$i]);
                    }
                }
            }
        }

        $order = range(0, $n);
        usort($order, fn ($a, $b) => $fvals[$a] <=> $fvals[$b]);

        return [$simplex[$order[0]], $fvals[$order[0]]];
    }

    private static function detectRegularDiff(array $y): int
    {
        $n = count($y);
        if ($n < 8) {
            return 0;
        }

        $xs = range(0, $n - 1);
        $meanX = array_sum($xs) / $n;
        $meanY = array_sum($y) / $n;
        $sxy = 0.0;
        $sxx = 0.0;
        foreach ($xs as $i => $x) {
            $sxy += ($x - $meanX) * ($y[$i] - $meanY);
            $sxx += ($x - $meanX) ** 2;
        }
        if ($sxx == 0.0) {
            return 0;
        }

        $slope = $sxy / $sxx;
        $sse = 0.0;
        foreach ($xs as $i => $x) {
            $resid = $y[$i] - ($meanY + $slope * ($x - $meanX));
            $sse += $resid ** 2;
        }
        $se = $n > 2 ? sqrt(($sse / ($n - 2)) / $sxx) : 0.0;
        $tStat = $se > 0 ? abs($slope) / $se : 0.0;

        return $tStat > 2.0 ? 1 : 0;
    }

    private static function detectSeasonalDiff(array $y, int $d, int $s): int
    {
        $w = $y;
        for ($i = 0; $i < $d; $i++) {
            $next = [];
            for ($t = 1; $t < count($w); $t++) {
                $next[] = $w[$t] - $w[$t - 1];
            }
            $w = $next;
        }

        $n = count($w);
        if ($n < 2 * $s) {
            return 0;
        }

        $mean = array_sum($w) / $n;
        $num = 0.0;
        $den = 0.0;
        foreach ($w as $t => $v) {
            $den += ($v - $mean) ** 2;
            if ($t >= $s) {
                $num += ($v - $mean) * ($w[$t - $s] - $mean);
            }
        }

        $acf = $den > 0 ? $num / $den : 0.0;

        return $acf > 0.3 ? 1 : 0;
    }
}
