<?php

require_once 'lib/Symfony/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('GW2CBackend', __DIR__.'/src');
$loader->register();

// first we receive the JSON string and we transform it to a PHP array
$jsonString = file_get_contents('test.json');
$jsonStringReference = file_get_contents('test-reference.json');

// then we check the validity of the JSON object regarding the format we want
// ----- nothing for now

// the third step is getting the current version of the map
// for now it comes from a test file
$json = json_decode($jsonString, true);
$jsonReference = json_decode($jsonStringReference, true);


// we 'diff' the two versions
$diffProcessor = new GW2CBackend\DiffProcessor($json, $jsonReference);
$changes = $diffProcessor->process();

var_dump($changes); // test the changes

// we render the diff version thanks to the map
// ------ nothing for now

?>