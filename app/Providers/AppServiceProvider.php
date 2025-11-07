<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // предотвратить выполнение команд, таких как migrate:fresh!
        DB::prohibitDestructiveCommands(app()->isProduction());

        Model::shouldBeStrict(! app()->isProduction());
        // строчка выше, заменяет 3 нижние
        //        Model::preventLazyLoading(! app()->isProduction());
        //        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());
        //        Model::preventAccessingMissingAttributes(! app()->isProduction());

        if (app()->isProduction()) {
            // долгий запрос БД
            DB::listen(function ($query) {
                if ($query->time > 200) {
                    logger()->debug('DB Query longer than 2s: '.$query->sql, $query->bindings);
                }
            });

            // долгий запрос HTTP
            app(Kernel::class)->whenRequestLifecycleIsLongerThan(
                CarbonInterval::seconds(4),
                function () {
                    logger()->debug('Request longer than 4s: '.request()->url());
                }
            );
        }
    }
}
