<?php

class Application_Api_Util_Bootstrap {

    private static $bootstrap;

    /**
     *
     * @param Zend_Application_Bootstrap_Bootstrap
     */
    public static function setBootstrap($bootstrap) {
        self::$bootstrap = $bootstrap;
    }

    /**
     * Resource ArrayObject contains all bootstrap classes
     *
     * @return Zend_Application_Bootstrap_Bootstrap
     */
    public static function getBootstrap() {
        return self::$bootstrap;
    }

    /**
     * Get an item from the bootstrap container (Registry)
     * @param string $item
     * @return mixed
     */
    public static function getItem($item) {
        $bootstrap = self::getBootstrap();
        return $bootstrap->getContainer()->$item;
    }

    /**
     * Get a resource
     * @param string $resource
     * @return mixed
     */
    public static function getResource($resource) {
        $bootstrap = self::getBootstrap();
        return $bootstrap->getResource($resource);
    }

    /**
     * Get current options from bootstrap
     *
     * @return array
     */
    public static function getOptions($zendConfig = false) {
        $bootstrap = self::getBootstrap();
        $options = $bootstrap->getOptions();

        if ($zendConfig)
            $options = new Zend_Config($options, false);

        return $options;
    }

    /**
     * Get a bootstrap option
     *
     * @param string $option
     * @param boolean $zendConfig return conf as Zend_Config
     * @return mixed
     */
    public static function getOption($option, $zendConfig = false) {
        $bootstrap = self::getBootstrap();
        $options = $bootstrap->getOption($option);

        if ($zendConfig)
            $options = new Zend_Config($options, false);

        return $options;
    }

}