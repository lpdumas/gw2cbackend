<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Silex service provider for the GW2C-Backend database.
 *
 * Gives access to the silex application to the database service, an instance of \GW2CBackend\DatabaseAdapter.
 */
class DatabaseServiceProvider implements ServiceProviderInterface {

    /**
     * Registers the service into the Silex application.
     *
     * @param \Silex\Application $app the Silex Application object.
     */
    public function register(Application $app) {

        $app['database'] = $app->share(function() { return new DatabaseAdapter(); });
    }

    /**
     * Executed the first time the service is called.
     *
     * Opens the database connection.
     *
     * @param \Silex\Application $app the Silex Application object.
     */
    public function boot(Application $app) {

        $app['database']->connect($app['database.host'], $app['database.port'], $app['database.dbname'],
                                              $app['database.user'], $app['database.password']);
    }
}
