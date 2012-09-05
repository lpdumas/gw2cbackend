<?php
/**
 * This file is part of Guild Wars 2 : Cartographers - Crowdsourcing Tool.
 *
 * @link https://github.com/lpdumas/gw2cbackend
 */

namespace GW2CBackend\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Controllers for areas management.
 */
class AreaControllerProvider extends ControllerProvider implements ControllerProviderInterface {

    /**
     * Connects the controllers to the Silex application.
     *
     * @param \Silex\Application $app the Silex application objet.
     */
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
                "section" => 'areas',
            );

            return $app['twig']->render('areas/index.twig', $params);

        })->bind('admin_areas');

        $controllers->post('/new', function(Request $request) use($app) {

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
                $addStatus = $app['database']->createArea($name, $rangeLvl, $swLat, $swLng, $neLat, $neLng);
                if ($addStatus) {
                    $message = "O";
                } else {
                    $message = "Area '".$name."' has been created.";
                }
                
                $message = "Area '".$name."' has been created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect($app['url_generator']->generate('admin_areas'));

        })->after($this->getClosure('generate_config'))->bind('admin_areas_add_new');

        $controllers->get('/update/{idArea}', function(Request $request, $idArea) use($app) {
            $areasList = $app['database']->retrieveAreasListFromId($idArea);
            $params = array(
                "areas" => $areasList,
                "section" => "areas"
            );
            return $app['twig']->render('areas/update.twig', $params);
        })->bind('admin_areas_update');

        
        $controllers->get('/add/', function(Request $request) use($app) {
            // $form = $app['form.factory']->createBuilder('form')
                // ->add('name')
                // ->add('email')
                // ->getForm();
                
            $params = array(
                "section" => "areas"
                // "form" => $form->createView()
            );
            
            
            return $app['twig']->render('areas/add.twig', $params);
        })->bind('admin_areas_add_form');



        
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
                $action = $app['database']->editArea($id, $name, $rangeLvl, $swLat, $swLng, $neLat, $neLng);
                if($action) {
                    $feedback = array(
                        "type" => "good",
                        "message" => "The area has been successfully updated."
                    );
                } else {                    
                    $feedback = array(
                        "type" => "good",
                        "message" => "Ooops .. something went wrong."
                    );
                }
                
            }
            $app['session']->set('feedback', $feedback);

            return $app->redirect($app['url_generator']->generate('admin_areas'));

        })->after($this->getClosure('generate_config'))->bind('admin_areas_edit');

        return $controllers;
    }
}