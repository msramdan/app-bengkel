<?php

namespace App\Providers;

use App\Pdf\SafeDompdf;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->app->bind('dompdf', function ($app) {
            $dompdf = new SafeDompdf($app->make('dompdf.options'));
            $path = realpath($app['config']->get('dompdf.public_path') ?: base_path('public'));

            if ($path === false) {
                throw new RuntimeException('Cannot resolve public path');
            }

            $dompdf->setBasePath($path);

            return $dompdf;
        });
    }
}
