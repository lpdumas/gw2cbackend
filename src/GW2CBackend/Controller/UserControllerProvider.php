<?php

namespace GW2CBackend\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app) {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use($app) {

           $users = $app['database']->getAllUsers();

           return $app['twig']->render('admin_users.twig', array('users' => $users));

        })->bind('admin_users');

        $controllers->post('/add', function(Request $request) use($app) {

            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $role = $request->request->get('role');

            if(!$username || !$password || !$role) {
                $message = "All the fields must be filled.";
            }
            else {

                $app['database']->createUser($username, $password, $role, $app['security.encoder_factory']);
                $message = "User '".$username."' has been created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect($app['url_generator']->generate('admin_users'));

        })->bind('admin_user_add');


        $controllers->get('/remove/{username}', function($username) use($app) {

            $username = strtolower($username);

            $app['database']->removeUser($username);

            if($app['security']->getToken()->getUser()->getUsername() == $username) {
                return $app->redirect('/admin/logout');
            }
            else {
                return $app->redirect($app['url_generator']->generate('admin_users'));
            }

        })->bind('admin_user_remove');

        return $controllers;
    }
}