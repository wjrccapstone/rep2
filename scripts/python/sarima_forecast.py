#!/usr/bin/env python3

import argparse
import json
import sys
import warnings
from pathlib import Path

import numpy as np
import pandas as pd
from statsmodels.tools.sm_exceptions import ConvergenceWarning
from statsmodels.tsa.statespace.sarimax import SARIMAX

warnings.filterwarnings('ignore', category=ConvergenceWarning)


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
            if best is None or result.aic < best['result'].aic:
                best = {'order': order, 'seasonal_order': seasonal_order, 'result': result}
        except Exception:
            continue

    if best is None:
        raise ValueError('Unable to fit any candidate seasonal SARIMA model.')

    order = best['order']
    seasonal_order = best['seasonal_order']
    result = best['result']
    forecast = np.asarray(result.forecast(steps=steps), dtype=float)
    alpha = 1 - confidence / 100
    forecast_ci = np.asarray(result.get_forecast(steps=steps).conf_int(alpha=alpha), dtype=float)

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
            'model_order': f'SARIMA{order}{seasonal_order}',
        })

    return {
        'forecast': points,
        'model_order': f'SARIMA{order}{seasonal_order}',
    }


def main():
    parser = argparse.ArgumentParser(description='Run seasonal SARIMA forecasting on a monthly synthetic series.')
    parser.add_argument('--input', required=True, help='Path to the JSON input file.')
    parser.add_argument('--output', required=True, help='Path to the JSON output file.')
    args = parser.parse_args()

    try:
        with open(args.input, 'r', encoding='utf-8-sig') as f:
            payload = json.load(f)

        values = payload.get('values', [])
        steps = int(payload.get('steps', 72))
        seasonal_period = int(payload.get('seasonal_period', 12))
        confidence = int(payload.get('confidence', 95))

        if not values:
            raise ValueError('Series is empty.')

        output = build_forecast(values, steps, seasonal_period, confidence)
        out_path = Path(args.output)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        with out_path.open('w', encoding='utf-8') as f:
            json.dump(output, f)
    except Exception as exc:  # pragma: no cover - for CLI safety
        print(f'PYTHON_FORECAST_ERROR: {exc}', file=sys.stderr)
        raise SystemExit(1)


if __name__ == '__main__':
    main()
