<?php

namespace GW2CBackend\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app) {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use ($app) {

            $app['database']->retrieveOptions();
            $options = $app['database']->getData("options");

            return $app['twig']->render('admin_options.twig', array('options' => $options));

        })->bind('admin_options');

        $controllers->get('/dump', function() use($app) {
            $content = $app['database']->dumpDatabase();

            $date = date('Y-m-d-H:i:s');

            $headers = array(
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename="'.$date.'-dump.sql',
            );

            return new Response($content, 200, $headers);

        })->bind('admin_options_dumpdb');

        $controllers->post('/edit', function(Request $request) use($app) {

            $app['database']->editOptions($request->request->all());

            return $app->redirect($app['url_generator']->generate('admin_options'));

        })->bind('admin_options_edit');

        return $controllers;
    }
}