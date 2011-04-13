<?php

error_reporting(E_ALL|E_STRICT);
ini_set('display_errors',true);

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap();

try {
    $opts = new Zend_Console_Getopt(
        array(
            'help|h' => 'Displays usage information.',
            'action|a=s' => 'Action to perform in format of module.controller.action',
            'verbose|v' => 'Verbose messages will be dumped to the default output.',
            'development|d' => 'Enables development mode.',
        )
    );
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    exit($e->getMessage() ."\n\n". $e->getUsageMessage());
}

if(isset($opts->h)) {
    echo $opts->getUsageMessage();
    exit;
}

if(isset($opts->a)) {
    $reqRoute = array_reverse(explode('.',$opts->a));
    @list($action,$controller,$module) = $reqRoute;
    $request = new Zend_Controller_Request_Simple($action,$controller,$module);
    $front = Zend_Controller_Front::getInstance();

    $front->setRequest($request);
    $front->setRouter(new Application_Api_Controller_Router_Cli());

    $front->setResponse(new Zend_Controller_Response_Cli());

    $front->throwExceptions(true);
    //$front->addModuleDirectory($pthRoot . '/application/modules/');

    $front->dispatch();
}
            