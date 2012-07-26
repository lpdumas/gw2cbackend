<?php

namespace GW2CBackend\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizeControllerProvider implements ControllerProviderInterface {

    public function connect(Application $app) {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function() use($app) {

            $structure = $app['database']->getMarkersStructure();

            // feedback management
            $feedback = null;
            if($app['session']->has('feedback')) {

                $feedback = $app['session']->get('feedback');
                $app['session']->remove('feedback');
            }

            // fieldset list
            $fieldsets = $app['database']->getAllFieldsets();

            $params = array(
                "feedback" => $feedback,
                "fieldsets" => $fieldsets,
                "markers_structure" => $structure,
            );

            return $app['twig']->render('organize.twig', $params);
        })->bind('admin_organize');

        $controllers->post('add-fieldset', function(Request $request) use($app) {

            $fieldsetName = $request->request->get('fieldset_name');
            if(!$fieldsetName || empty($fieldsetName)) {
                $message = "The fieldset name must not be empty.";
            }
            else {
                $app['database']->addFieldset($fieldsetName);

                $message = "Fieldset \"".$fieldsetName."\" has been successfully created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize');

        })->bind('admin_add_fieldset');

        $controllers->post('/remove-fieldset', function(Request $request) use($app) {

            $fieldsetID = $request->request->get('fieldsets-list');
            if(!$fieldsetID || empty($fieldsetID) || !ctype_digit($fieldsetID)) {
                $message = "An error occurred during the deletion: can't determine the right fieldset to delete.";
            }
            else {
                $fieldsetName = $app['database']->removeFieldset($fieldsetID);

                $message = "Fieldset \"".$fieldsetName."\" has been successfully removed.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize');

        })->bind('admin_remove_fieldset');

        $controllers->post('/add-field', function(Request $request) use($app) {

            $fieldsetID = $request->request->get('fieldset-id');
            $fieldName = $request->request->get('fieldname');
            if(!$fieldsetID || !$fieldName || empty($fieldName) || empty($fieldsetID) || !ctype_digit($fieldsetID)) {
                $message = "The field name must not be empty.";
            }
            else {
                $fieldsetName = $app['database']->addField($fieldsetID, $fieldName);

                $message = "The Field \"".$fieldName."\" has been successfully added to fieldset \"".$fieldsetName."\".";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize');

        })->bind('admin_add_field');

        // handle edition AND deletion
        $controllers->post('/edit-field', function(Request $request) use($app) {

            $fieldID = $request->request->get('fieldID');
            $fieldName = $request->request->get('fieldname');
            if(!$fieldID || !$fieldName || empty($fieldName) || empty($fieldID) || !ctype_digit($fieldID)) {
                $message = "The field name must not be empty.";
            }
            else {

                if($request->request->has('fieldset-editfield')) {
                    $fieldsetName = $app['database']->editField($fieldID, $fieldName);
                    $message = "The Field \"".$fieldName."\" of fieldset \"".$fieldsetName."\" has been successfully updated.";
                }
                else if($request->request->has('fieldset-removefield')) {
                    $fieldsetName = $app['database']->removeField($fieldID, $fieldName);
                    $message = "The Field \"".$fieldName."\" of fieldset \"".$fieldsetName."\" has been successfully removed.";
                }
                else {
                    $message = "Action not found.";
                }
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize');

        })->bind('admin_edit_field');

        $controllers->post('/add-marker-group', function(Request $request) use($app) {

            $slug = $request->request->get('slug');
            $iconPrefix = $request->request->get('icon-prefix');
            $fieldsetID = $request->request->get('fieldset');
            if(!$fieldsetID || !$slug || empty($slug) || empty($fieldsetID)) {
                $message = "All the fields must be filled.";
            }
            else {
                $app['database']->addMarkerGroup($slug, $iconPrefix, $fieldsetID);
                $message = "The marker group \"".$slug."\" has been successfully created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize#'.$slug);

        })->bind('admin_add_marker_group');

        $controllers->post('add-marker-type', function(Request $request) use($app) {

            $slug = $request->request->get('slug');
            $filename = $request->request->get('filename');
            $displayInAreaSum = $request->request->get('displayInAreaSum');
            $mgID = $request->request->get('marker-group');
            $fieldsetID = $request->request->get('fieldset');

            if(!$fieldsetID || !$slug || !$mgID || empty($mgID) || empty($slug) || empty($fieldsetID)) {
                $message = "The slug field must be filled.";
            }
            else {
                $app['database']->addMarkerType($slug, $filename, $displayInAreaSum, $fieldsetID, $mgID);
                $message = "The marker type \"".$slug."\" has been successfully created.";
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize#'.$slug);

        })->bind('admin_add_marker_type');

        // handle edition AND deletion
        $controllers->post('/edit-marker-group', function(Request $request) use($app) {

            $slugReference = $request->request->get('slug-reference');
            $slug = $request->request->get('slug');
            $iconPrefix = $request->request->get('icon-prefix');
            $fieldsetID = $request->request->get('fieldset');

            if(!$fieldsetID || !$slug || !$slugReference || empty($slugReference) || empty($slug) || empty($fieldsetID)) {
                $message = "All the fields must be filled (except icon prefix).";
            }
            else {
                if($request->request->has('edit')) {
                    $app['database']->editMarkerGroup($slugReference, $slug, $iconPrefix, $fieldsetID);
                    $message = "The marker group \"".$slug."\" has been successfully updated.";
                }
                else if($request->request->has('remove')) {
                    $app['database']->removeMarkerGroup($slug);
                    $message = "The marker group \"".$slug."\" has been successfully removed.";
                }
                else {
                    $message = "Action not found.";
                }
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize#'.$slug);

        })->bind('admin_edit_marker_group');

        // handle edition AND deletion
        $controllers->post('/edit-marker-type', function(Request $request) use($app) {

            $slug = $request->request->get('slug');
            $filename = $request->request->get('filename');
            $displayInAreaSum = $request->request->get('displayInAreaSum');
            $slugReference = $request->request->get('slug-reference');
            $fieldsetID = $request->request->get('fieldset');

            if(!$fieldsetID || !$slug || !$slugReference || empty($slugReference) || empty($slug) || empty($fieldsetID)) {
                $message = "The slug field must be filled.";
            }
            else {
                if($request->request->has('edit')) {
                    $app['database']->editMarkerType($slugReference, $slug, $filename, $displayInAreaSum, $fieldsetID);
                    $message = "The marker type \"".$slug."\" has been successfully updated.";
                }
                else if($request->request->has('remove')) {
                    $app['database']->removeMarkerType($slug);
                    $message = "The marker type \"".$slug."\" has been successfully removed.";
                }
                else {
                    $message = "Action not found.";
                }
            }

            $app['session']->set('feedback', $message);

            return $app->redirect('/admin/organize#'.$slug);

        })->bind('admin_edit_marker_type');

        $controllers->post('/edit-translated-data', function(Request $request) use($app) {

           $fieldsetID = $request->request->get('id-fieldset');
           $tDataID = $request->request->get('id-translated-data');

           if(!$fieldsetID || !$tDataID || empty($fieldsetID) || empty($tDataID)) {
               $message = "Fieldset not found.";
           }
           else {

               $app['database']->editTranslatedData($tDataID, $request->request->all(), $fieldsetID);

               $message = "The fields have been successfully updated.";
           }

           $app['session']->set('feedback', $message);

           return $app->redirect('/admin/organize');

        })->bind('admin_edit_translated_data');

        return $controllers;
    }
}