<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../db-config.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

// we register the services
$app->register(new GW2CBackend\DatabaseServiceProvider(), array(
    'database.host'     => $host,
    'database.port'     => $port,
    'database.dbname'   => $database,
    'database.user'     => $user,
    'database.password' => $pword,
));

$app['admin.username'] = $admin_user;
$app['admin.password'] = $admin_pword;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->get('/', function() use($app) {

    return "Welcome on the backend of Guild Wars 2 Cartographers.";

});

// dirty test form to submit JSON
$app->get('/test-form', function() {
   
   $jsonString = file_get_contents(__DIR__.'/../test.json');
   
   $display = '<html>
                    <head><title>Test form to submit JSON</title></head>
                    <body>
                        <form method="post" action="submit-modification">
                            <textarea cols="100" rows="50" name="json">'.$jsonString.'</textarea>
                            <input type="submit" value="Send" />
                        </form>
                    </body>
                </html>';
   
    return $display;
});

$app->post('/submit-modification', function(Request $request) use($app) {

    // we mock the submission
    $jsonString = stripslashes($request->request->get('json'));
    
    $app['database']->addModification($jsonString);
});

$app->get('/login', function() use($app) {
    $username = $app['request']->server->get('PHP_AUTH_USER', false);
    $password = $app['request']->server->get('PHP_AUTH_PW');

    if ($app['admin.username'] === $username && $app['admin.password'] === $password) {
        $app['session']->set('user', array('username' => $username));

        return $app->redirect('/');
    }

    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
    $response->setStatusCode(401, 'Please sign in.');
    return $response;
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


        // the second part of the script is executed when an administrator validates or not the modification. Let say he does validate.
        $options = $pdo->getData("options");
        $filepath = __DIR__.$options["output-filepath"];
        $minimized = (boolean) $options["output-minimization"];
        $generator = new GW2CBackend\ConfigGenerator($jsonReference, $changes, $pdo->getData("resources"), 
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