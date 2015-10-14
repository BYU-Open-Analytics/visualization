<?php

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Db\Adapter\Pdo\Postgresql as DbAdapter;

try {

    // Register an autoloader
    $loader = new Loader();
    $loader->registerDirs(array(
        '../app/controllers/',
        '../app/models/',
	'../app/helpers/'
    ))->register();

    $loader->registerClasses(
	array(
		"BLTI"	=> "../app/library/ims_lti/blti.php",
		"LTIContext" => "../app/library/LTIContext.php",
	)
    );

    // Create a DI
    $di = new FactoryDefault();
    
    // Load configuration file
    $config = new \Phalcon\Config\Adapter\Php("../app/config/config.php");
    // Store it in the Di container
    $di->setShared("config", $config);

    // Setup the LTI context (this takes care of starting the session, too)
    $context = LTIContext::getContext($config);
    $di->setShared("ltiContext",$context);
    // Now in views and controllers, all we have to do is check if this context is valid

	// Get in the right time zone
	date_default_timezone_set("America/Denver");

    // Setup the database service
    $di->set('db', function() use ($config) {
	    return new DbAdapter(array(
			"host" => $config->visualization_database->host,
			"dbname" => $config->visualization_database->dbname,
			"username" => $config->visualization_database->username,
			"password" => $config->visualization_database->password
	    ));
    });


    // Setup the view component
    $di->set('view', function(){
        $view = new View();
        $view->setViewsDir('../app/views/');
        return $view;
    });

    // Setup a base URI so that all generated URIs include the "tutorial" folder
    $di->set('url', function() use ($config) {
        $url = new UrlProvider();
        $url->setBaseUri($config['base_uri']);
        return $url;
    });

    // Handle the request
    $application = new Application($di);

    // TODO take out error reporting for production
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    echo $application->handle()->getContent();

} catch (\Exception $e) {
     echo "PhalconException: ", $e->getMessage();
}
