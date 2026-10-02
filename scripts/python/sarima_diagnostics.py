#!/usr/bin/env python3

import argparse
import json
import math
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from statsmodels.stats.diagnostic import acorr_ljungbox
from statsmodels.tsa.stattools import acf, adfuller, pacf
from statsmodels.tsa.statespace.sarimax import SARIMAX

warnings.filterwarnings('ignore')


def safe_float(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def build_forecast(values, steps, seasonal_period, confidence=95):
    values = np.asarray(values, dtype=float)
    if values.size < max(12, seasonal_period * 2):
        raise ValueError('Not enough data for a seasonal SARIMA fit.')

    candidate_orders = [
        ((0, 1, 0), (0, 1, 0, seasonal_period)),
        ((1, 1, 0), (1, 1, 0, seasonal_period)),
        ((0, 1, 1), (0, 1, 1, seasonal_period)),
        ((1, 1, 1), (1, 1, 1, seasonal_period)),
    ]

    best = None
    for order, seasonal_order in candidate_orders:
        try:
            model = SARIMAX(
                values,
                order=order,
                seasonal_order=seasonal_order,
                enforce_stationarity=False,
                enforce_invertibility=False,
            )
            result = model.fit(disp=False)
            score = {
                'order': order,
                'seasonal_order': seasonal_order,
                'aic': float(result.aic),
                'bic': float(result.bic),
                'result': result,
            }
            if best is None or score['aic'] < best['aic']:
                best = score
        except Exception:
            continue

    if best is None:
        best = {
            'order': (1, 1, 1),
            'seasonal_order': (1, 1, 1, seasonal_period),
            'aic': float('inf'),
            'bic': float('inf'),
            'result': None,
        }

    selected_order = best['order']
    selected_seasonal_order = best['seasonal_order']
    selected_result = best['result']

    if selected_result is None:
        selected_model = SARIMAX(
            values,
            order=selected_order,
            seasonal_order=selected_seasonal_order,
            enforce_stationarity=False,
            enforce_invertibility=False,
        )
        selected_result = selected_model.fit(disp=False)

    forecast = np.asarray(selected_result.forecast(steps=steps), dtype=float)
    alpha = 1 - confidence / 100
    forecast_ci = np.asarray(selected_result.get_forecast(steps=steps).conf_int(alpha=alpha), dtype=float)

    lower = forecast_ci[:, 0]
    upper = forecast_ci[:, 1]

    anchor = pd.Timestamp.today().normalize() - pd.offsets.MonthBegin(1)
    months = pd.date_range(start=anchor + pd.DateOffset(months=1), periods=steps, freq='MS')

    points = []
    for idx, month in enumerate(months):
        value = float(forecast[idx])
        points.append({
            'date': month.strftime('%Y-%m-%d'),
            'label': month.strftime('%b %Y'),
            'value': round(max(0.0, value), 3),
            'lower': round(max(0.0, float(lower[idx])), 3),
            'upper': round(max(float(upper[idx]), value), 3),
            'model_order': f'SARIMA{selected_order}{selected_seasonal_order}',
        })

    adf_stat, adf_pvalue, *_ = adfuller(values, autolag='AIC')
    stationary = bool(adf_pvalue < 0.05)

    acf_lags = min(12, max(6, len(values) // 4))
    acf_values = acf(values, nlags=acf_lags, fft=False)
    pacf_values = pacf(values, nlags=acf_lags, method='ywadjusted')

    residuals = np.asarray(selected_result.resid, dtype=float)
    residuals = residuals[np.isfinite(residuals)]
    if residuals.size > 0:
        lag = min(10, max(1, residuals.size // 5))
        lb_df = acorr_ljungbox(residuals, lags=[lag], return_df=True)
        ljung_pvalue = float(lb_df.iloc[0]['lb_pvalue'])
    else:
        ljung_pvalue = 1.0

    residual_ok = bool(ljung_pvalue > 0.05)

    diagnostics = {
        'adf_statistic': round(float(adf_stat), 6),
        'adf_pvalue': round(float(adf_pvalue), 12),
        'stationary': stationary,
        'selected_order': f'SARIMA{selected_order}{selected_seasonal_order}',
        'aic': round(float(selected_result.aic), 6),
        'bic': round(float(selected_result.bic), 6),
        'ljung_box_pvalue': round(float(ljung_pvalue), 12),
        'residual_ok': residual_ok,
        'acf_lags': [round(float(v), 6) for v in acf_values.tolist()],
        'pacf_lags': [round(float(v), 6) for v in pacf_values.tolist()],
        'candidate_orders_checked': len(candidate_orders),
    }

    return {
        'forecast': points,
        'diagnostics': diagnostics,
    }


def main():
    parser = argparse.ArgumentParser(description='Run Box-Jenkins SARIMA diagnostics on a monthly synthetic series.')
    parser.add_argument('--input', required=True, help='Path to the JSON input file.')
    parser.add_argument('--output', required=True, help='Path to the JSON output file.')
    args = parser.parse_args()

    try:
        with open(args.input, 'r', encoding='utf-8-sig') as f:
            payload = json.load(f)

        values = payload.get('values', [])
        steps = int(payload.get('steps', 12))
        seasonal_period = int(payload.get('seasonal_period', 12))
        confidence = int(payload.get('confidence', 95))

        if not values:
            raise ValueError('Series is empty.')

        output = build_forecast(values, steps, seasonal_period, confidence)
        out_path = Path(args.output)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        with out_path.open('w', encoding='utf-8') as f:
            json.dump(output, f)
    except Exception as exc:  # pragma: no cover - CLI safety
        print(f'FORECAST_DIAGNOSTICS_ERROR: {exc}', file=sys.stderr)
        raise SystemExit(1)


if __name__ == '__main__':
    import sys
    main()
