<?php

require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use GW2CBackend\UserProvider;

$app = new Silex\Application();

// must be done after $app has been initialized
require_once __DIR__.'/../db-config.php';

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

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/development.log',
));

if($app['debug'] === true) {
    $app['monolog']->pushHandler(new \Monolog\Handler\ChromePHPHandler());
    $app['monolog']->pushHandler(new \Monolog\Handler\FirePHPHandler());
}

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

$routeForDebugOnly = function() use($app) {

    if($app['debug'] === false) {
        return $app->redirect('/');
    }
};

$generateConfigFile = function() use($app) {
    $app['database']->retrieveCurrentReference();
    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    $currentReference = $app['database']->getData('current-reference');

    $reference = GW2CBackend\Util::decodeJSON($currentReference['value']);

    $builder = new GW2CBackend\MarkerBuilder($app['database']);
    $mapRef = $builder->build($currentReference['id'], $reference);
    
    $forAdmin = false;
    $generator = new GW2CBackend\ConfigGenerator($mapRef, $options["resources-path"]['value'], $areasList);
    $generator->setForAdmin($forAdmin);
    
    $output = $generator->generate();
    if($options['output-minimization']['value']) {
        $generator->minimize();
    }

    $generator->save(__DIR__.'/../'.$options['output-filepath']['value']);
    
    return null;
};

$closuresForControllers = array(
                            'generate_config' => $generateConfigFile,
                            'route_for_debug' => $routeForDebugOnly,
                          );

// The Utility class needs the $app to use monolog
GW2CBackend\Util::$app = $app;

$app->mount('/admin/organize', new GW2CBackend\Controller\OrganizeControllerProvider($closuresForControllers));
$app->mount('/admin/user', new GW2CBackend\Controller\UserControllerProvider($closuresForControllers));
$app->mount('/admin/options', new GW2CBackend\Controller\OptionsControllerProvider($closuresForControllers));
$app->mount('/admin/area', new GW2CBackend\Controller\AreaControllerProvider($closuresForControllers));

$app->get('/', function() use($app) {

    return $app['twig']->render('home.twig');

})->bind('home');

// dirty test form to submit JSON
$app->get('/test-form', function() use($app) {
   
   $jsonString = file_get_contents(__DIR__.'/../test.json');
   
   return $app['twig']->render('test-form.html.twig', array(
           'jsonString' => $jsonString
       ));
})->before($closuresForControllers['route_for_debug']);

$app->post('/submit-modification', function(Request $request) use($app) {

    // we mock the submission
    $jsonString = stripslashes($request->request->get('json'));
    $json = GW2CBackend\Util::decodeJSON($jsonString);

    $app['database']->retrieveAreasList();
    $validator = new GW2CBackend\InputValidator($json, $app['database']->getData("areas-list"));
    $isValid = $validator->validate();

    if($isValid === true) {

        $app['database']->retrieveCurrentReference();
        $currentReference = $app['database']->getData('current-reference');
 
        $reference = GW2CBackend\Util::decodeJSON($currentReference['value']);

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

                $stater = new GW2CBackend\StatsProcessor($changes);
                $stats = $stater->process();

                $tagger = new \GW2CBackend\TagProcessor($changes);
                $tags = $tagger->process();
                $app['database']->addModification($jsonString, $stats, $tags);
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
    
    $list = $app['database']->retrieveModificationList(5);
    foreach($list as $k => $item) {
        $json = GW2CBackend\Util::decodeJSON($item['value']);
        $list[$k]['reference_id'] = $json['version'];
        $list[$k]['stats'] = isset($item['stats']) ? unserialize($item['stats']) : "";
    }

    /*$mergedList = $app['database']->retrieveMergedModificationList();
    foreach($mergedList as $k => $item) {
        $json = GW2CBackend\Util::decodeJSON($item['value']);
        $mergedList[$k]['reference_id'] = $json['version'];
        $mergedList[$k]['stats'] = isset($item['stats']) ? unserialize($item['stats']) : "";
    }*/
    $mergedList = array();

    return $app['twig']->render('admin_home.twig', array(
            'modifList' => $list,
            'mergedModifList' => $mergedList,
        ));
})->bind('admin');

$app->get('/admin/revision/archive/{revID}', function($revID) use($app) {

   $app['database']->archiveModification($revID);
    
   return $app->redirect('/admin/');
})->bind('admin_revision_archive');

$app->get('/admin/revision/{revID}', function($revID) use($app) {

    $app['twig.path'] = __DIR__.'/../gw2cread/';

    $app['database']->retrieveCurrentReference();
    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    $currentReference = $app['database']->getData('current-reference');
    
    $lastRev = $app['database']->retrieveModification($revID);

    $modification = GW2CBackend\Util::decodeJSON($lastRev['value']);
    $reference = GW2CBackend\Util::decodeJSON($currentReference['value']);

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
                $prefixIcon = $mergedRevision->getMarkerGroup($gSlug)->getIconPrefix();
                $image = $prefixIcon.'/'.$mergedRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug)->getIcon();
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

$app->get('/admin/revision/{revID}/compare/{referenceID}', function($revID, $referenceID) use($app) {
    $app['twig.path'] = __DIR__.'/../gw2cread/';

    $app['database']->retrieveOptions();
    $app['database']->retrieveAreasList();
    $options = $app['database']->getData("options");
    $areasList = $app['database']->getData("areas-list");
    
    $lastRev = $app['database']->retrieveModification($revID);
    $modification = GW2CBackend\Util::decodeJSON($lastRev['value']);

    $currentReference = $app['database']->getReference($referenceID);
    $reference = GW2CBackend\Util::decodeJSON($currentReference['value']);

    $builder = new GW2CBackend\MarkerBuilder($app['database']);
    $mapModif = $builder->build($revID, $modification);
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
                $prefixIcon = $mergedRevision->getMarkerGroup($gSlug)->getIconPrefix();
                $image = $prefixIcon.'/'.$mergedRevision->getMarkerGroup($gSlug)->getMarkerType($tSlug)->getIcon();
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
})->bind('admin_revision_compare');

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
    
    if($lastRev['is_merged']) return $app->redirect('/admin/');
    
    $modification = GW2CBackend\Util::decodeJSON($lastRev['value']); 
    $reference = GW2CBackend\Util::decodeJSON($currentReference['value']);

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

    $app['database']->markAsMerged($revID, $changesToMerge, $newID);

    $generator->save(__DIR__.'/../'.$options['output-filepath']['value']);

    return $app->redirect('/admin/');

})->bind('admin_merge_changes');

$app->get('/format', function() use($app) {

    $json = file_get_contents(__DIR__.'/../test.json');

    $formatter = new GW2CBackend\FormatJSON($json);
    $json = $formatter->format();
    $je = json_encode($json['json']);
    $de = GW2CBackend\Util::decodeJSON($je);

    $builder = new GW2CBackend\MarkerBuilder($app['database']);
    $map = $builder->build(1, $de);
    var_dump(strlen(addslashes($je)));
    //$app['database']->saveNewRevisionAsReference($map, 1, $json['max_id']);

    $response = new Response($je, 200);
    $response->setCharset("UTF-8");

    return $response;
})->before($closuresForControllers['route_for_debug']);

$app->run();