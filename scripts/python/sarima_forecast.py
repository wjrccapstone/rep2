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


def build_forecast(values, steps, seasonal_period):
    values = np.asarray(values, dtype=float)
    if values.size < max(12, seasonal_period * 2):
        raise ValueError('Not enough data for a seasonal SARIMA fit.')

    order = (1, 1, 1)
    seasonal_order = (1, 1, 1, seasonal_period)

    model = SARIMAX(
        values,
        order=order,
        seasonal_order=seasonal_order,
        enforce_stationarity=False,
        enforce_invertibility=False,
    )
    result = model.fit(disp=False)
    forecast = np.asarray(result.forecast(steps=steps), dtype=float)
    forecast_ci = np.asarray(result.get_forecast(steps=steps).conf_int(alpha=0.05), dtype=float)

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

        if not values:
            raise ValueError('Series is empty.')

        output = build_forecast(values, steps, seasonal_period)
        out_path = Path(args.output)
        out_path.parent.mkdir(parents=True, exist_ok=True)
        with out_path.open('w', encoding='utf-8') as f:
            json.dump(output, f)
    except Exception as exc:  # pragma: no cover - for CLI safety
        print(f'PYTHON_FORECAST_ERROR: {exc}', file=sys.stderr)
        raise SystemExit(1)


if __name__ == '__main__':
    main()
