<?php
/*
 *  Author: Raul Perusquia <raul@inikoo.com>
 *  Created: Tue, 20 Sept 2022 12:26:11 Malaysia Time, Kuala Lumpur, Malaysia
 *  Copyright (c) 2022, Raul A Perusquia Flores
 */

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';


    public function boot(): void
    {
        $this->configureRateLimiting();

        Route::middleware('grp')
            ->domain('app.'.config('app.domain'))
            ->name('grp.')
            ->group(base_path('routes/grp/web/app.php'));

        Route::middleware('webhooks')
            ->domain(config('app.domain'))
            ->prefix('webhooks')
            ->group(base_path('routes/grp/webhooks/webhooks.php'));

        Route::middleware('api')
            ->domain(config('app.domain'))
            ->prefix('api')
            ->group(base_path('routes/grp/api/api.php'));

        Route::middleware('aiku-public')
            ->domain(config('app.domain'))
            ->name('aiku-public.')
            ->group(base_path('routes/aiku-public/web/root.php'));

        Route::middleware('retina')
            ->prefix('app')
            ->name('retina.')
            ->group(base_path('routes/retina/app.php'));

        Route::middleware('iris')
            ->name('iris.')
            ->group(base_path('routes/iris/root.php'));

    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by($request->user()?->id ?: $request->ip());
        });
    }
}
