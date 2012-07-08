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
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    $changes = array();
    
    $lastRev = $app['database']->retrieveModification($revID);
    $jsonReference = json_decode($lastRev['value'], true);

    $markerGroups = $app['database']->getMarkersStructure();

    // the second part of the script is executed when an administrator validates or not the modification. Let say he does validate.
    $generator = new GW2CBackend\ConfigGenerator($jsonReference, $changes, $markerGroups, 
                                                 $options["resources-path"], $areasList);
    $generator->generate();
    //$generator->minimize();
    $output = $generator->getOutput();
    $generator->save(__DIR__.'/config.js', false); // for debug purpose

    return $app['twig']->render('index.html', array("js_generated" => $output));
 
})->bind('admin_revision');

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