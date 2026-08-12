<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Services\ForecastingService;

/**
 * Employment forecasting (linear regression) management (admin).
 */
class ForecastingController extends Controller
{
    public function index(Request $request): void
    {
        $service = new ForecastingService();

        $benchmarks = [];
        foreach (array_keys(ForecastingService::INDICATORS) as $key) {
            $benchmarks[$key] = ForecastingService::benchmark($key);
        }

        $this->view('admin/forecasting/index', [
            'title'      => 'Forecasting',
            'subtitle'   => 'Linear regression employment projections',
            'indicators' => ForecastingService::INDICATORS,
            'benchmarks' => $benchmarks,
            'models'     => $service->recentModels(),
            'target'     => $service->employmentTarget(),
        ]);
    }

    public function generate(Request $request): void
    {
        Csrf::validateOrAbort();
        $indicator = (string) $request->input('indicator', 'employment_rate');
        if (!isset(ForecastingService::INDICATORS[$indicator])) {
            $this->error('Invalid indicator.', 'admin/forecasting');
        }

        $yearsAhead = min(5, max(1, (int) $request->input('years_ahead', 3)));
        $service = new ForecastingService();
        $forecast = $service->forecast($indicator, $yearsAhead);

        $historicalCount = $forecast['historical_count'] ?? count(array_filter($forecast['series'], static fn ($s) => !$s['is_forecast']));
        if ($historicalCount === 0) {
            $this->error('No historical data available to build a forecast.', 'admin/forecasting');
        }
        if ($historicalCount < ForecastingService::MIN_OBSERVATIONS) {
            $this->error(
                'Not enough historical data to build a reliable forecast. At least '
                    . ForecastingService::MIN_OBSERVATIONS . ' years of data are required.',
                'admin/forecasting'
            );
        }

        $modelId = $service->store($forecast, $yearsAhead);
        $this->audit('generate', 'forecasting', "Generated {$indicator} forecast (model #{$modelId}).");
        $this->success('Forecast generated successfully.', 'admin/forecasting/' . $modelId);
    }

    public function show(Request $request, array $params): void
    {
        $service = new ForecastingService();
        $models = $service->recentModels(1000);
        $model = null;
        foreach ($models as $m) {
            if ((int) $m['id'] === (int) $params['id']) {
                $model = $m;
                break;
            }
        }
        if (!$model) {
            abort(404, 'Forecast model not found.');
        }

        $results = $service->modelResults((int) $model['id']);
        $series = [];
        foreach ($results as $r) {
            $series[] = [
                'year' => (int) $r['year'],
                'value' => $r['value'] !== null ? (float) $r['value'] : null,
                'is_forecast' => (bool) $r['is_forecast'],
                'lower' => $r['lower_bound'] !== null ? (float) $r['lower_bound'] : null,
                'upper' => $r['upper_bound'] !== null ? (float) $r['upper_bound'] : null,
            ];
        }
        $params = json_decode((string) ($model['parameters'] ?? 'null'), true) ?? [];

        $this->view('admin/forecasting/show', [
            'title'    => 'Forecast',
            'subtitle' => $model['indicator_label'],
            'model'    => $model,
            'series'   => $series,
            'results'  => $results,
            'params'   => $params,
            'target'   => $service->employmentTarget(),
            'unit'     => ForecastingService::unit((string) ($model['indicator'] ?? '')),
            'benchmark' => ForecastingService::benchmark((string) ($model['indicator'] ?? '')),
            'interpretation' => ForecastingService::interpretation((string) ($model['indicator'] ?? ''), [
                'slope' => $model['slope'],
                'intercept' => $model['intercept'],
                'r_squared' => $model['r_squared'],
            ]),
        ]);
    }

    public function destroy(Request $request, array $params): void
    {
        Csrf::validateOrAbort();
        $model = (new ForecastingService())->recentModels(1000);
        $exists = false;
        foreach ($model as $m) {
            if ((int) $m['id'] === (int) $params['id']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            abort(404, 'Forecast model not found.');
        }
        \App\Core\Database::run('DELETE FROM forecast_models WHERE id = ?', [(int) $params['id']]);
        $this->audit('delete', 'forecasting', "Deleted forecast model #{$params['id']}.");
        $this->success('Forecast model deleted.', 'admin/forecasting');
    }
}
