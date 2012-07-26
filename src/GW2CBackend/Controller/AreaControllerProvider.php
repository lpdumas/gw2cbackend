<?php

namespace GW2CBackend\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AreaControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app) {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use($app) {

            // feedback management
            $feedback = null;
            if($app['session']->has('feedback')) {
                $feedback = $app['session']->get('feedback');
                $app['session']->remove('feedback');
            }

            $app['database']->retrieveAreasList();
            $areasList = $app['database']->getData("areas-list");

            $params = array(
                "feedback" => $feedback,
                "areas" => $areasList,
            );

            return $app['twig']->render('areas.twig', $params);

        })->bind('admin_areas');

        $controllers->post('/add', function(Request $request) use($app) {

            $name = $request->request->get('name');
            $rangeLvl = $request->request->get('rangeLvl');
            $swLat = $request->request->get('swLat');
            $swLng = $request->request->get('swLng');
            $neLat = $request->request->get('neLat');
            $neLng = $request->request->get('neLng');

            if(!$name || !$swLat || !$swLng || !$neLat || !$neLng) {
                $message = "All the fields must be filled.";
            }
            else {

                $app['database']->createArea($name, $rangeLvl, $swLat, $swLng, $neLat, $neLng);
                $message = "Area '".$name."' has been created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/areas');

        })->bind('admin_areas_add');

        $controllers->post('/edit', function(Request $request) use($app) {
            $name = $request->request->get('name');
            $rangeLvl = $request->request->get('rangeLvl');
            $swLat = $request->request->get('swLat');
            $swLng = $request->request->get('swLng');
            $neLat = $request->request->get('neLat');
            $neLng = $request->request->get('neLng');

            $id = $request->request->get('areaID');

            if(!$name || !$swLat || !$swLng || !$neLat || !$neLng && !$request->request->has('remove')) {
                $message = "All the fields must be filled.";
            }
            else {

                if($request->request->has('remove')) {
                    $app['database']->removeArea($id);
                    $message = "The area has been successfully removed.";
                }
                else if($request->request->has('edit')) {
                    $app['database']->editArea($id, $name, $rangeLvl, $swLat, $swLng, $neLat, $neLng);
                    $message = "The area has been successfully updated.";
                }
                else {
                    $message = "Action not found.";
                }
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/areas');

        })->bind('admin_areas_edit');

        return $controllers;
    }
}