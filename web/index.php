<?php

require_once __DIR__.'/../vendor/autoload.php';
//require_once __DIR__.'/../lib/Symfony/ClassLoader/UniversalClassLoader.php';
require_once __DIR__.'/../db-config.php';

$pdo = new GW2CBackend\DatabaseAdapter();
$app = new Silex\Application();

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello '.$app->escape($name);
});

$app->run();

/*





// we open a connection to the database
$pdo = new GW2CBackend\DatabaseAdapter();
$pdo->connect($host, $port, $database, $user, $pword);
$pdo->retrieveAll();

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