<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../db-config.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GW2CBackend\UserProvider;

$app = new Silex\Application();

$app['debug'] = true;

// we register the services
$app->register(new GW2CBackend\DatabaseServiceProvider(), array(
    'database.host'     => $host,
    'database.port'     => $port,
    'database.dbname'   => $database,
    'database.user'     => $user,
    'database.password' => $pword,
));

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
));

// firewall for admin area
$app['security.firewalls'] = array(
    'admin' => array(
        'pattern' => '^/admin/',
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/admin/logout'),
        'users' => $app->share(function () use ($app) {
            return new UserProvider($app['database']);
        }),
    ),
);

$app['security.role_hierarchy'] = array(
    'ROLE_SUPER_ADMIN' => array('ROLE_ADMIN'),
);

// Order is important
$app['security.access_rules'] = array(
    array('^/admin/users.*$', 'ROLE_SUPER_ADMIN'),
    array('^/admin', 'ROLE_ADMIN'),
);

$app->get('/', function() use($app) {

    return $app['twig']->render('home.twig');
})->bind('home');

// dirty test form to submit JSON
$app->get('/test-form', function() use($app) {
   
   $jsonString = file_get_contents(__DIR__.'/../test.json');
   
   return $app['twig']->render('test-form.html.twig', array(
           'jsonString' => $jsonString
       ));
});

$app->post('/submit-modification', function(Request $request) use($app) {

    // we mock the submission
    $jsonString = stripslashes($request->request->get('json'));
    $json = json_decode($jsonString, true);

    $app['database']->retrieveAreasList();
    $validator = new GW2CBackend\InputValidator($json, $app['database']->getData("areas-list"));
    $isValid = $validator->validate();

    if($isValid === true) {

        $app['database']->retrieveCurrentReference();
        $currentReference = $app['database']->getData('current-reference');
 
        $reference = json_decode($currentReference['value'], true);

        $builder = new GW2CBackend\MarkerBuilder($app['database']);
        $mapModif = $builder->build(-1, $json);
        $mapRef = $builder->build($currentReference['id'], $reference);

        $baseReference = $app['database']->getReference($json['version']);
        $diff = new GW2CBackend\DiffProcessor($mapModif, $mapRef, $baseReference['max_marker_id']);
        $changes = $diff->process();
        
        if(!$diff->hasChanges()) {
            $message = array('success' => false, 'message' => '<h1>Ooops...</h1><p>It seems that you haven\'t made any change !</p>');
        }
        else {
            $app['database']->retrieveOptions();
            $options = $app['database']->getData("options");
            if(!$options['maintenance-mode']['value']) {
                $app['database']->addModification($jsonString);
                $message = array('success' => true, 'message' => '<h1>Thank you !</h1><p>A team of dedicated grawls will sort that out.</p>');
            }
            else {
                $message = array('success' => false, 'message' => '<h1>Ooops...</h1><p>The crowdsourcing server is currently down for maintenance.</p>');
            }
        }
    }
    else {
        $message = array('success' => false, 'message' => '<h1>Ooops...</h1><p>The JSON is invalid: '.$isValid.'. This can be a bug,do not hesitate to let us know !</p>');
    }

    $headers = array(
        'Content-Type' => 'application/json',
        'Access-Control-Allow-Origin' => '*',
    );
    return new Response(json_encode($message), 200, $headers);
});

$app->get('/login', function(Request $request) use ($app) {

    return $app['twig']->render('login.twig', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin/', function() use($app) {
    
    $list = $app['database']->retrieveModificationList();
    
    return $app['twig']->render('admin_home.twig', array(
            'modifList' => $list
        ));
})->bind('admin');

$app->get('/admin/revision/{revID}', function($revID) use($app) {

    $app['twig.path'] = __DIR__.'/../gw2cread/';

    $app['database']->retrieveCurrentReference();
    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    $currentReference = $app['database']->getData('current-reference');
    
    $lastRev = $app['database']->retrieveModification($revID);
    $modification = json_decode($lastRev['value'], true); 
    $reference = json_decode($currentReference['value'], true);

    $builder = new GW2CBackend\MarkerBuilder($app['database']);
    $mapModif = $builder->build(-1, $modification);
    $mapRef = $builder->build($currentReference['id'], $reference);

    $baseReference = $app['database']->getReference($modification['version']);
    $diff = new GW2CBackend\DiffProcessor($mapModif, $mapRef, $baseReference['max_marker_id']);
    $changes = $diff->process();
    
    $forAdmin = true;
    $merger = new GW2CBackend\ChangeMerger($mapRef, $changes);
    $merger->setForAdmin($forAdmin);
    $mergedRevision = $merger->merge();
    
    $generator = new GW2CBackend\ConfigGenerator($mergedRevision, $options["resources-path"]['value'], $areasList);
    $generator->setForAdmin($forAdmin);
    
    $output = $generator->generate();
    
    /*
        We make an array twig-friendly to easily display the changes in a list
    */
    $flatChanges = array();
    $changeID = 1;
    foreach($changes as $gSlug => $mGroup) {
        foreach($mGroup as $tSlug => $mType) {
            foreach($mType as $change) {
                
                $id = $change['marker'] != null ? $change['marker']->getID() : $change['marker-reference']->getID();
                $name = $mergedRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug)->getData()->getData('en', 'name');
                $image = $mergedRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug)->getIcon();
                $flatChanges[] = array('id' => $changeID, 'markerID' => $id, 'name' => $name, 'image' => $image, 'change' => $change);
                $changeID++;
            }
        }
    }
    
    //$generator->save(__DIR__.'/config.js'); // for debug purpose

    $params = array("js_generated" => $output, 
                    'revID' => $revID, 
                    'imagePath' => $options["resources-path"]['value'],
                    'changes' => $flatChanges);

    return $app['twig']->render('index.html', $params);

})->bind('admin_revision');

$app->get('/admin/organize', function() use($app) {
    
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

$app->post('admin/organize/add-fieldset', function(Request $request) use($app) {
    
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

$app->post('/admin/organize/remove-fieldset', function(Request $request) use($app) {
    
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

$app->post('/admin/organize/add-field', function(Request $request) use($app) {

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
$app->post('/admin/organize/edit-field', function(Request $request) use($app) {

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

$app->post('/admin/organize/add-marker-group', function(Request $request) use($app) {

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

$app->post('/admin/organize/add-marker-type', function(Request $request) use($app) {

    $slug = $request->request->get('slug');
    $filename = $request->request->get('filename');
    $mgID = $request->request->get('marker-group');
    $fieldsetID = $request->request->get('fieldset');

    if(!$fieldsetID || !$slug || !$mgID || empty($mgID) || empty($slug) || empty($fieldsetID)) {
        $message = "The slug field must be filled.";
    }
    else {
        $app['database']->addMarkerType($slug, $filename, $fieldsetID, $mgID);
        $message = "The marker type \"".$slug."\" has been successfully created.";
    }
    
    $app['session']->set('feedback', $message);

    return $app->redirect('/admin/organize#'.$slug);

})->bind('admin_add_marker_type');

// handle edition AND deletion
$app->post('/admin/organize/edit-marker-group', function(Request $request) use($app) {
    
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
$app->post('/admin/organize/edit-marker-type', function(Request $request) use($app) {
    
    $slug = $request->request->get('slug');
    $filename = $request->request->get('filename');
    $slugReference = $request->request->get('slug-reference');
    $fieldsetID = $request->request->get('fieldset');

    if(!$fieldsetID || !$slug || !$slugReference || empty($slugReference) || empty($slug) || empty($fieldsetID)) {
        $message = "The slug field must be filled.";
    }
    else {
        if($request->request->has('edit')) {
            $app['database']->editMarkerType($slugReference, $slug, $filename, $fieldsetID);
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

$app->post('/admin/organize/edit-translated-data', function(Request $request) use($app) {

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

$app->post('/admin/merge-changes', function(Request $request) use($app) {

    $revID = $request->request->get('revID');
    $changesToMerge = array();
    foreach($request->request as $changeToMerge => $v) {
        if(is_numeric($changeToMerge)) {
            $changesToMerge[] = $changeToMerge;
        }
    }

    $app['database']->retrieveCurrentReference();
    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    $currentReference = $app['database']->getData('current-reference');
    
    $lastRev = $app['database']->retrieveModification($revID);
    $modification = json_decode($lastRev['value'], true); 
    $reference = json_decode($currentReference['value'], true);

    $builder = new GW2CBackend\MarkerBuilder($app['database']);
    $mapModif = $builder->build(-1, $modification);
    $mapRef = $builder->build($currentReference['id'], $reference);

    $baseReference = $app['database']->getReference($modification['version']);
    $diff = new GW2CBackend\DiffProcessor($mapModif, $mapRef, $baseReference['max_marker_id']);
    $changes = $diff->process();
    
    $forAdmin = false;
    $merger = new GW2CBackend\ChangeMerger($mapRef, $changes);
    $merger->setForAdmin($forAdmin);
    $mergedRevision = $merger->merge($changesToMerge);
    
    $generator = new GW2CBackend\ConfigGenerator($mergedRevision, $options["resources-path"]['value'], $areasList);
    $generator->setForAdmin($forAdmin);
    
    // This must be done before generation so the config.js file has the right ID.
    $newID = $app['database']->saveNewRevisionAsReference($mergedRevision, $currentReference['id'], $merger->getMaximumID());
    $mergedRevision->setID($newID);
    
    $output = $generator->generate();
    if($options['output-minimization']['value']) {
        $generator->minimize();
    }
    
    $app['database']->markAsMerged($revID);
    
    $generator->save(__DIR__.'/../'.$options['output-filepath']['value']);
    
    return $app->redirect('/admin/');
    
})->bind('admin_merge_changes');

$app->get('/admin/options', function() use ($app) {
    
    $app['database']->retrieveOptions();
    $options = $app['database']->getData("options");
    
    return $app['twig']->render('admin_options.twig', array('options' => $options));

})->bind('admin_options');

$app->get('/admin/options/dump', function() use($app) {
    $content = $app['database']->dumpDatabase();
    
    $date = date('Y-m-d-H:i:s');
    
    $headers = array(
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'attachment; filename="'.$date.'-dump.sql',
    );
    
    return new Response($content, 200, $headers);

})->bind('admin_options_dumpdb');

$app->post('/admin/options/edit', function(Request $request) use($app) {

    $app['database']->editOptions($request->request->all());

    return $app->redirect('/admin/options');
    
})->bind('admin_options_edit');

$app->get('/admin/users', function() use($app) {
   
   $users = $app['database']->getAllUsers();
   
   return $app['twig']->render('admin_users.twig', array('users' => $users));

})->bind('admin_users');

$app->post('/admin/user/add', function(Request $request) use($app) {
    
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
    
    return $app->redirect('/admin/users');
    
})->bind('admin_user_add');


$app->get('/admin/user/remove/{username}', function($username) use($app) {
    
    $username = strtolower($username);
    
    $app['database']->removeUser($username);
    
    if($app['security']->getToken()->getUser()->getUsername() == $username) {
        return $app->redirect('/admin/logout');
    }
    else {
        return $app->redirect('/admin/users');
    }
    
})->bind('admin_user_remove');

$app->get('/format', function() use($app) {
   
   $json = file_get_contents(__DIR__.'/../test.json');
   
   $formatter = new GW2CBackend\FormatJSON($json);
   $json = $formatter->format();
   $je = json_encode($json['json']);
   $de = json_decode($je, true);
   
   $builder = new GW2CBackend\MarkerBuilder($app['database']);
   $map = $builder->build(1, $de);
   var_dump(strlen(addslashes($je)));
   //$app['database']->saveNewRevisionAsReference($map, 1, $json['max_id']);

   $response = new Response($je, 200);
   $response->setCharset("UTF-8");
   
   return $response;
});

$app->run();