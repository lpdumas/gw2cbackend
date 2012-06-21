<?php

require_once 'lib/Symfony/ClassLoader/UniversalClassLoader.php';

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespace('GW2CBackend', __DIR__.'/src');
$loader->register();

?>