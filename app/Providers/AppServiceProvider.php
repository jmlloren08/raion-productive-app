<?php

namespace App\Providers;

use App\Console\Commands\SyncProductiveDataRefactored;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(SyncProductiveDataRefactored::class, function ($app) {
        //     return new SyncProductiveDataRefactored(
        //         $app->make('App\Actions\Productive\InitializeClient'),
        //         $app->make('App\Actions\Productive\FetchCompanies'),
        //         $app->make('App\Actions\Productive\FetchProjects'),
        //         $app->make('App\Actions\Productive\FetchDeals'),
        //         $app->make('App\Actions\Productive\FetchTimeEntries'),
        //         $app->make('App\Actions\Productive\FetchTimeEntryVersions'),
        //         $app->make('App\Actions\Productive\StoreData'),
        //         $app->make('App\Actions\Productive\ValidateDataIntegrity')
        //     );
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
