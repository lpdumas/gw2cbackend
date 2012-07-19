<?php

namespace GW2CBackend;

use Silex\Application;
use Silex\ServiceProviderInterface;

class DatabaseServiceProvider implements ServiceProviderInterface {

    public function register(Application $app) {

        $app['database'] = $app->share(function() { return new DatabaseAdapter(); });
    }

    public function boot(Application $app) {

        $app['database']->connect($app['database.host'], $app['database.port'], $app['database.dbname'],
                                              $app['database.user'], $app['database.password']);
    }
}
