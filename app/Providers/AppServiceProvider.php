<?php

namespace App\Providers;

use App\Agent\GeminiTransport;
use App\Agent\ModelTransport;
use App\Agent\OwnerGeminiTransport;
use App\Agent\OwnerModelTransport;
use Illuminate\Support\ServiceProvider;
use Tests\Fakes\OwnerModel;
use Tests\Fakes\PortfolioModel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OwnerModelTransport::class, function () {
            return app()->environment('testing') && config('agent.driver') === 'fake'
                ? new OwnerModel : new OwnerGeminiTransport;
        });
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
