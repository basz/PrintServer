<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initAutoload() {
        $zendAutoloader = new \Zend_Application_Module_Autoloader(array(
                    'namespace' => 'Application_',
                    'basePath' => dirname(__FILE__),
                ));

        // Add resource type for Module Api
        $zendAutoloader->addResourceType('api', 'api/', 'Api');
        $zendAutoloader->addResourceType('models/entities', 'models/entities', 'Model_Entity');
        //$zendAutoloader->addResourceType('table', 'tables', 'Table');
        //$zendAutoloader->removeResourceType('model');
        //Zend_Debug::dump($zendAutoloader);die();

        $autoloader = \Zend_Loader_Autoloader::getInstance();
        $autoloader->pushAutoloader(array('Bootstrap', 'namespaceAutoload'), 'Application\\Model\\');

        // doctrine library
        require_once APPLICATION_PATH . '/../library/Doctrine/Common/ClassLoader.php';
        $autoloader->pushAutoloader(array(new \Doctrine\Common\ClassLoader('Bisna'), 'loadClass'), 'Bisna');

     //   Zend_Controller_Action_HelperBroker::addPrefix('Application_Api_Controller_Action_Helper');
     
    }
    /**
     * PHP5.3 namespace helper
     *
     * The problem is that Zend_Loader_Autoloader_Resource and, as a consequence, Zend_Application_Module_Autoloader does not support PHP5.3 namespaces for now.
     *
     * @see http://zend-framework-community.634137.n4.nabble.com/Doctrine-2-0-Entities-Models-Naming-and-Autoloading-td1678427.html
     * @see http://framework.zend.com/issues/browse/ZF-8205
     *
     * This method can load
     * @param <type> $className
     */
    static public function namespaceAutoload($className) {
        Zend_Loader_Autoloader::autoload(str_replace('\\', '_', $className));
    }

    function _initCache() {
        
        
        $cache = Zend_Cache::factory('Core', 'File',
                                    array('automatic_serialization'=>true),
                                    array('cache_dir'=>APPLICATION_PATH . '/../data/tmp'));
        
        // an APC backend is memory based and thus prefered, however it seems there are 
        // interoperability problems between cli and apache_mod, which when you think about it aren't
        // that strange. So for now I choose file based cause it works.
        //$cache = Zend_Cache::factory('Core', 'Apc',
        //                            array('automatic_serialization'=>true),
        //                            array());

        //if (APPLICATION_ENV != 'production')
        //    $cache->clean();

        return $cache;
    }


    protected function _initSetConstants() {
       if ($arrConstants = $this->getOption("constants"))
            foreach ($arrConstants as $strName => $strValue)
                if (!defined($strName))
                    define($strName, $strValue);
    }

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');
    }

    protected function _initRouters() {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        // Specifying the "api" module only as RESTful:
        $restRoute = new Zend_Rest_Route($front, array(), array(
                    'api',
                ));
        $router->addRoute('rest', $restRoute);
    }


    protected function _initApiUtilBootstrap() {
        Application_Api_Util_Bootstrap::setBootstrap($this);
    }


}

