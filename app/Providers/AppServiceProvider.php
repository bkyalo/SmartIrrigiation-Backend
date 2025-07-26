<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Plot;
use App\Models\Tank;

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
        // Define the morph map for polymorphic relationships
        Relation::morphMap([
            'tank' => Tank::class,
            'plot' => Plot::class,
        ]);
    }
}
