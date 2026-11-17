<?php

namespace App\Providers;

use App\Agent\GeminiTransport;
use App\Agent\ModelTransport;
use Illuminate\Support\ServiceProvider;
use Tests\Fakes\PortfolioModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ModelTransport::class, function () {
            if (app()->environment('testing') && config('agent.driver') === 'fake') {
                return new PortfolioModel;
            }

            return new GeminiTransport;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
