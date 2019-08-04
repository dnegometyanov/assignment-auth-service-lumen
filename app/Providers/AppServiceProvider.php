<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register(): void
    {
        // Init mailer
        $this->app->singleton(
            'mailer',
            function ($app) {
                return $app->loadComponent('mail', 'Illuminate\Mail\MailServiceProvider', 'mailer');
            }
        );

        // Aliases
        $this->app->alias('mailer', \Illuminate\Contracts\Mail\Mailer::class);

        // Lara Stan Static analysis tool
        $this->app->register(\NunoMaduro\Larastan\LarastanServiceProvider::class);
        $this->app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');
        $this->app->instance('path.config', app()->basePath() . DIRECTORY_SEPARATOR . 'config');

        // Fix for error from https://github.com/laravel/lumen-framework/issues/567
        // Error appeared after adding phpstan config here
        $this->app->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app) {
            return new \Illuminate\Routing\ResponseFactory(
                $app['Illuminate\Contracts\View\Factory'],
                $app['Illuminate\Routing\Redirector']
            );
        });
    }
}
