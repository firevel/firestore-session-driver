<?php

namespace Firevel\FirestoreSessionDriver;

use Firevel\FirestoreSessionDriver\FirestoreSessionHandler;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class FirestoreSessionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Session::extend('firestore', function ($app) {
            return new FirestoreSessionHandler(
                new FirestoreClient,
                $app['config']['session.table'],
                $app['config']['session.lifetime'],
                $app
            );
        });
    }
}
