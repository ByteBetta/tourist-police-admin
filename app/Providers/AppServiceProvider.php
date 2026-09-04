<?php

namespace App\Providers;

use App\Services\Firebase\DocumentStore;
use App\Services\Firebase\FirestoreRestStore;
use App\Services\Firebase\LocalJsonStore;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentStore::class, function (): DocumentStore {
            $credentials = config('services.firebase.credentials');
            $projectId = config('services.firebase.project_id');

            if (is_string($credentials) && is_file($credentials) && filled($projectId)) {
                return new FirestoreRestStore;
            }

            return new LocalJsonStore;
        });
    }

    public function boot(): void
    {
        //
    }
}
