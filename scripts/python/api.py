import hmac
import os

from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from scripts.python.sarima_diagnostics import build_forecast as build_diagnostics
from scripts.python.sarima_forecast import build_forecast

app = FastAPI(title='WJRC SARIMA Forecast Service', version='1.0.0')


class ForecastRequest(BaseModel):
    values: list[float] = Field(min_length=24)
    steps: int = Field(default=72, ge=1, le=120)
    seasonal_period: int = Field(default=12, ge=2, le=24)


def require_token(authorization: str | None) -> None:
    expected = os.getenv('FORECAST_API_TOKEN', '')
    if not expected:
        raise HTTPException(status_code=503, detail='Forecast API token is not configured.')

    supplied = authorization.removeprefix('Bearer ').strip() if authorization else ''
    if not hmac.compare_digest(supplied, expected):
        raise HTTPException(status_code=401, detail='Unauthorized.')


@app.get('/health')
def health() -> dict[str, str]:
    return {'status': 'ok'}


@app.post('/forecast')
def forecast(request: ForecastRequest, authorization: str | None = Header(default=None)) -> dict:
    require_token(authorization)
    try:
        return build_forecast(request.values, request.steps, request.seasonal_period)
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error


@app.post('/diagnostics')
def diagnostics(request: ForecastRequest, authorization: str | None = Header(default=None)) -> dict:
    require_token(authorization)
    try:
        return build_diagnostics(request.values, request.steps, request.seasonal_period)
    except ValueError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error