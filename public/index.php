<?php

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;

try {

    // Register an autoloader
    $loader = new Loader();
    $loader->registerDirs(array(
        '../app/controllers/',
        '../app/models/'
    ))->register();

    $loader->registerClasses(
	array(
		"BLTI"	=> "../app/library/ims_lti/blti.php",
		"LTIContext" => "../app/library/LTIContext.php",
	)
    );

    // Create a DI
    $di = new FactoryDefault();
    
    // Setup the database service
    $di->set('db', function(){
	    return new DbAdapter(array(
	    	"host"		=> "localhost",
		"username"	=> "lti",
		"password"	=> "ltitest",
		"dbname"	=> "lti_development"
	    ));
    });

    // Load configuration file
    $configFile = "../app/config/config.ini";
    $config = new \Phalcon\Config\Adapter\Ini($configFile);
    // Store it in the Di container
    $di->setShared("config", $config);

    // Setup the view component
    $di->set('view', function(){
        $view = new View();
        $view->setViewsDir('../app/views/');
        return $view;
    });

    // Setup a base URI so that all generated URIs include the "tutorial" folder
    $di->set('url', function(){
        $url = new UrlProvider();
        $url->setBaseUri('/');
        return $url;
    });

    // Handle the request
    $application = new Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
     echo "PhalconException: ", $e->getMessage();
}
