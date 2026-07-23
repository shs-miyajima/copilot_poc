<?php

namespace App\Providers;

use App\Models\Survey;
use App\Policies\SurveyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        // 本番環境では HTTPS を強制
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::policy(Survey::class, SurveyPolicy::class);
        Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
    }
}
