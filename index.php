<?php

require_once 'lib/Symfony/ClassLoader/UniversalClassLoader.php';
require_once 'db-config.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('GW2CBackend', __DIR__.'/src');
$loader->register();

// first we receive the JSON string and we transform it to a PHP array
$jsonString = file_get_contents('test.json');
$json = json_decode($jsonString, true);

// we open a connection to the database
$pdo = new GW2CBackend\DatabaseAdapter();
$pdo->connect($host, $port, $database, $user, $pword);
$pdo->retrieveAll();

// then we check the validity of the JSON object regarding the format we want
$validator = new GW2CBackend\InputValidator($json, $pdo->getData("resources"));
$isValid = $validator->validate();

if($isValid === true) {

    // the third step is getting the current version of the map
    // for now it comes from a test file
    $jsonStringReference = file_get_contents('test-reference.json');
    $jsonReference = json_decode($jsonStringReference, true);

    // we 'diff' the two versions
    $differ = new GW2CBackend\DiffProcessor($json, $jsonReference);
    $changes = $differ->process();

    // we render the diff version thanks to the map
    // ------ nothing for now


    // the second part of the script is executed when an administrator validates or not the modification. Let say he does validate.
    $filepath = __DIR__.'/output/config.js';
    $minimized = false;
    $generator = new GW2CBackend\ConfigGenerator($jsonReference, $changes);
    $generator->generate();
    $generator->save($filepath, $minimized);
}
else {
    echo "The file format in invalid.";
}

?>