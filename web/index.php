<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../db-config.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
//$app->register(new Silex\Provider\SecurityServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/views',
    /*'twig.path' => __DIR__.'/../gw2cread',*/
));

// firewall for admin area
/*$app['security.firewalls'] = array(
    'admin' => array(
        'pattern' => '^/admin/',
        'form' => array('login_path' => '/login', 'check_path' => '/admin/login_check'),
        'logout' => array('logout_path' => '/logout'),
        'users' => array(
            'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
        ),
    ),
);*/

$app->get('/', function() use($app) {

    return "Welcome on the backend of Guild Wars 2 Cartographers.";

});

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

    if($isValid == true) {
        $app['database']->addModification($jsonString);

        $message = array('success' => true, 'message' => 'The modification has been submitted.');        
    }
    else {
        $message = array('success' => false, 'message' => 'The JSON is invalid.');
    }
    
    return $app->json($message , 200);
});

$app->get('/login', function(Request $request) use ($app) {

    return $app['twig']->render('login.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
});

$app->get('/admin/', function() use($app) {
    
    $list = $app['database']->retrieveModificationList();
    
    return $app['twig']->render('admin.html.twig', array(
            'modifList' => $list
        ));
});

$app->get('/admin/revision/{revID}', function($revID) use($app) {
    
    $app['twig.path'] = __DIR__.'/../gw2cread/';

    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $app['database']->retrieveCurrentReference();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    
    // the third step is getting the current version of the map
    $currentReference = $app['database']->getData("current-reference");
    $jsonReference = json_decode($currentReference["value"], true);
    
    $lastRev = $app['database']->retrieveModification($revID);
    $jsonLastRev = json_decode($lastRev['value'], true);

    $app['database']->retrieveReferenceAtSubmission($lastRev['id_reference_at_submission']);
    $referenceAtSubmission = $app['database']->getData('reference-at-submission');
    $maxReferenceID = $referenceAtSubmission['max_marker_id'];

    // we 'diff' the two versions
    $differ = new GW2CBackend\DiffProcessor($jsonLastRev, $jsonReference, $maxReferenceID);
    $changes = $differ->process();

    $markerGroups = $app['database']->getMarkersStructure();

    // the second part of the script is executed when an administrator validates or not the modification. Let say he does validate.
    $generator = new GW2CBackend\ConfigGenerator($jsonReference, $changes, $markerGroups, 
                                                 $options["resources-path"], $areasList);

    $mergeForAdmin = true;
    $generator->generate($mergeForAdmin);
    $generator->minimize();
    $output = $generator->getOutput();
    
    /*
        We make an array twig-friendly to easily display the changes in a list
    */
    $flatChanges = array();
    $changeID = 1;
    foreach($generator->getChanges() as $markerGroupID => $markerGroup) {
        foreach($markerGroup as $markerTypeID => $markerType) {
            foreach($markerType as $change) {
                
                $id = $change['marker'] != null ? $change['marker']['id'] : $change['marker-reference']['id'];
                $name = $markerGroups[$markerGroupID]['markerTypes'][$markerTypeID]['name'];

                $image = $markerGroups[$markerGroupID]['markerTypes'][$markerTypeID]['filename'];
                $flatChanges[] = array('id' => $changeID, 'markerID' => $id, 'name' => $name, 'image' => $image, 'change' => $change);
                $changeID++;
            }
        }
    }

    //$generator->save(__DIR__.'/config.js', false); // for debug purpose

    $params = array("js_generated" => $output, 'revID' => $revID, 'imagePath' => $options["resources-path"], 'changes' => $flatChanges);

    return $app['twig']->render('index.html', $params);
 
})->bind('admin_revision');

$app->get('/refactoring/{revID}', function($revID) use($app) {

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
    
    // the max id will have to come from the JSON
    $diff = new GW2CBackend\DiffProcessor($mapModif, $mapRef, $currentReference['max_marker_id']);
    $changes = $diff->process();
    
    $merger = new GW2CBackend\ChangeMerger($mapRef, $changes);
    
    $forAdmin = true;
    $mergedRevision = $merger->merge();
    
    $generator = new GW2CBackend\ConfigGenerator($mergedRevision, $options["resources-path"], $areasList);
    $generator->setForAdmin($forAdmin);
    
    $output = $generator->generate();
    //$generator->minimize();
    //$output = $generator->getOutput();
    $generator->save(__DIR__.'/config.js');
    //echo nl2br(str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $output));
    
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
    
    $params = array("js_generated" => $output, 
                    'revID' => $revID, 
                    'imagePath' => $options["resources-path"], 
                    'changes' => $flatChanges);

    return $app['twig']->render('index.html', $params);
});

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

    return $app['twig']->render('organize.html', $params);
});

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
    $fieldsetID = $request->request->get('fieldset');
    if(!$fieldsetID || !$slug || empty($slug) || empty($fieldsetID)) {
        $message = "All the fields must be filled.";
    }
    else {
        $app['database']->addMarkerGroup($slug, $fieldsetID);
        $message = "The marker group \"".$slug."\" has been successfully created.";
    }
    
    $app['session']->set('feedback', $message);

    return $app->redirect('/admin/organize');

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

    return $app->redirect('/admin/organize');

})->bind('admin_add_marker_type');

// handle edition AND deletion
$app->post('/admin/organize/edit-marker-group', function(Request $request) use($app) {
    
    $slug = $request->request->get('slug');
    $slugReference = $request->request->get('slug-reference');
    $fieldsetID = $request->request->get('fieldset');

    if(!$fieldsetID || !$slug || !$slugReference || empty($slugReference) || empty($slug) || empty($fieldsetID)) {
        $message = "All the fields must be filled.";
    }
    else {
        if($request->request->has('edit')) {
            $app['database']->editMarkerGroup($slugReference, $slug, $fieldsetID);
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

    return $app->redirect('/admin/organize');

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

    return $app->redirect('/admin/organize');

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
    
    // the max id will have to come from the JSON
    $diff = new GW2CBackend\DiffProcessor($mapModif, $mapRef, $currentReference['max_marker_id']);
    $changes = $diff->process();
    
    $merger = new GW2CBackend\ChangeMerger($mapRef, $changes);
    
    $forAdmin = true;
    $mergedRevision = $merger->merge($changesToMerge);
    
    $generator = new GW2CBackend\ConfigGenerator($mergedRevision, $options["resources-path"], $areasList);
    $generator->setForAdmin($forAdmin);
    
    $output = $generator->generate();
    //$generator->minimize();
    $generator->save(__DIR__.'/config.js');
    
})->bind('admin_merge_changes');

$app->get('/format', function() use($app) {
   
   $json = file_get_contents(__DIR__.'/../test.json');
   
   $formatter = new GW2CBackend\FormatJSON($json);
   $json = $formatter->format();

   echo json_encode($json);
});

$app->run();

/*
// first we receive the JSON string and we transform it to a PHP array
// we mock the database
$jsonString = file_get_contents('test.json');
$modification = $pdo->getData('first-modification');
$modification['reference-at-submission'] = 1;
$modification['value'] = $jsonString;

$json = json_decode($modification["value"], true);

// then we check the validity of the JSON object regarding the format we want
$validator = new GW2CBackend\InputValidator($json, $pdo->getData("resources"), $pdo->getData("areas-list"));
$isValid = $validator->validate();

if($isValid === true) {

    // the third step is getting the current version of the map
    $currentReference = $pdo->getData("current-reference");
    $jsonReference = json_decode($currentReference["value"], true);

    // when we replace the mock, put it in DatabaseAdapter::retrieveAll()
    $pdo->retrieveReferenceAtSubmission($modification['reference-at-submission']);
    $referenceAtSubmission = $pdo->getData('reference-at-submission');
    $maxReferenceID = $referenceAtSubmission['max_marker_id'];

    // we 'diff' the two versions
    $differ = new GW2CBackend\DiffProcessor($json, $jsonReference, $maxReferenceID);
    $changes = $differ->process();

    if($differ->hasNoChange()) {
        echo "No change detected from this modification.";
    }
    else {
        
        echo "Changes have been detected.";
        
        var_dump($changes['hearts']);
        
        // we render the diff version thanks to the map
        // ------ nothing for now

        $markerGroups = $pdo->getMarkerGroups();
        $markerTypes = $pdo->getMarkerTypes();

        foreach($markerTypes as $markerType) {
    
            $markerGroups[$markerType['id_marker_group']]['markerTypes'][] = $markerType;
        }

        // the second part of the script is executed when an administrator validates or not the modification. Let say he does validate.
        $options = $pdo->getData("options");
        $filepath = __DIR__.$options["output-filepath"];
        $minimized = (boolean) $options["output-minimization"];
        $generator = new GW2CBackend\ConfigGenerator($jsonReference, $changes, $markerGroups, 
                                                     $options["resources-path"], $pdo->getData("areas-list"));
        $generator->setIDToNewMarkers();
        $generator->generate();
        $generator->save($filepath, $minimized);

        $pdo->addReference($generator->getReference(), $generator->getMaxMarkerID(), $modification["id"]);
    }
}
else {
    echo "The file format in invalid.";
}
*/
?>