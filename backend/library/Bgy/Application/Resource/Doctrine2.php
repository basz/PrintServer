<?php
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Configuration,
    Doctrine\ORM\Mapping\Driver\YamlDriver,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\PHPDriver,
    Doctrine\DBAL\Connection,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager;

require_once ('Zend/Application/Resource/ResourceAbstract.php');
/**
 * @link http://www.doctrine-project.org/docs/orm/2.0/en/reference/configuration.html
 */
class Bgy_Application_Resource_Doctrine2
    extends Zend_Application_Resource_ResourceAbstract
{
    /**
     *
     * @var \Doctrine\ORM\Configuration
     */
    protected $_configuration;

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $_entityManager;


    /**
     *
     * @return  mixed

     * @see Zend_Application_Resource_Resource::init()
     */
    public function init()
    {
        $this->_setupProxy();
        $this->_setupMetadata();
        $this->_setupCache();
        $this->_setupCustomTypes();
        $this->_setupCustomHydrators();
        $this->_setupOptions();

        return $this;
    }

    public function getConfiguration()
    {
        if (null === $this->_configuration) {
            $this->_configuration = new Configuration();
        }

        return $this->_configuration;

    }

    public function getEntityManager()
    {
        if (null === $this->_entityManager) {
            $options = $this->getOptions();
            $this->_entityManager = EntityManager::create(
                $options['params'],
                $this->getConfiguration()
            );
        }

        return $this->_entityManager;
    }

    public function getConnectionInformations()
    {
        $options = $this->getOptions();

        return $options['params'];
    }

    protected function _setupMetadata()
    {
        $options = $this->getOptions();

        if (!empty($options['metadata']['driver'])) {
            $driver = $options['metadata']['driver'];

            if (!empty($options['metadata']['paths'])) {
                $paths = (array)$options['metadata']['paths'];
            }

            switch (strtolower($driver)) {
                case 'yaml':
                    $driver = new YamlDriver($paths);
                    break;
                case 'php':
                    $driver = new PHPDriver($paths);
                    break;
                case 'xml':
                    $driver = new XmlDriver($paths);
                    break;
                case 'annotation':
                    $driver = $this->getConfiguration()
                        ->newDefaultAnnotationDriver($paths);
                    break;
                default:
                    $isCustomDriver = true;
                    break;
            }

            if (isset($isCustomDriver) && true === $isCustomDriver) {
                if (!class_exists($driver)) {
                    throw new Bgy_Application_Resource_Exception(
                        'Class "'.$driver.'" does not exist'
                    );
                }

                $driver = new $driver($paths);
            }


            $this->getConfiguration()->setMetadataDriverImpl($driver);
        }
    }

    protected function _setupCache()
    {
        $options = $this->getOptions();

        $cachable = array('metadata', 'query', 'result');

        foreach ($cachable as $thing) {
            // we trigger a E_USER_NOTICE if the APPLICATION_ENV === production
            // for "educational purpose only"
            if (!empty($options['cache'][$thing])) {
                $cache = $options['cache'][$thing];
                if (class_exists($cache)) {
                    $cache = new $cache;
                }

                $method = 'set' . ucfirst($thing) . 'CacheImpl';
                $this->_configuration->$method($cache);
            } elseif (('metadata' === $thing || 'query' === $thing)
                 && 'production' === APPLICATION_ENV) {
                trigger_error('You should really cache metadata in production environement');
            }
        }
    }

    // Fallback methods for other parameters

    protected function _setupOptions()
    {
        $options = $this->getOptions();

        if (!empty($options['options']['sqlLogger'])) {
            $sqlLogger = new $options['options']['sqlLogger'];

            $this->getConfiguration()->setSQLLogger($sqlLogger);
        }

        if (!empty($options['options']['useCExtension'])) {
            $this->getConfiguration()
                ->setUseCExtension((bool)$options['options']['useCExtension']);
        }
    }

    protected function _setupProxy()
    {
        $options = $this->getOptions();

        // Those are mandatory options.
        if (!empty($options['proxy']['dir'])) {
            $this->getConfiguration()->setProxyDir($options['proxy']['dir']);
        }

        if (!empty($options['proxy']['namespace'])) {
            $this->getConfiguration()->setProxyNamespace($options['proxy']['namespace']);
        }

        // this one is optionnal, but not recommended in production
        if (isset($options['proxy']['autoGenerateClasses'])) {
            $autoGenerateClasses = (bool) $options['proxy']['autoGenerateClasses'];
            $this->getConfiguration()
                ->setAutoGenerateProxyClasses($autoGenerateClasses);

            if (true === $autoGenerateClasses
                && 'production' === APPLICATION_ENV) {
                trigger_error('Generate proxies classes in production is not recommended.');
            }
        }
    }

    protected function _setupCustomTypes()
    {
        $options = $this->getOptions();

        if (!empty($options['types'])) {
            if (is_array($options['types'])) {
                foreach ($options['types'] as $name => $class) {
                    if (is_string($name)) {
                        if (class_exists($class)) {
                            //$this->getConfiguration()->setCustomTypes();
                            Type::addType($name, $class);
                        }
                    } else {
                        throw new Bgy_Application_Resource_Exception(
                            "Name must be a string, " . gettype($name) . " given"
                        );
                    }
                }

            } else {
                throw new Bgy_Application_Resource_Exception(
                    "Types option must be an array, you must provide the "
                    . "name and the corresponding class of the custom type."
                );
            }
        }
    }

    public function _setupCustomHydrators()
    {
        $options = $this->getOptions();

        if (!empty($options['hydrators'])) {
            if (is_array($options['hydrators'])) {
                foreach ($options['hydrators'] as $name => $class) {
                    if (is_string($name)) {
                        if (class_exists($class)) {
                            $this->getConfiguration()
                            ->addCustomHydrationMode($name, $class);
                        }
                    } else {
                        throw new Bgy_Application_Resource_Exception(
                            "Name must be a string, " . gettype($name) . " given"
                        );
                    }
                }

            } else {
                throw new Bgy_Application_Resource_Exception(
                    "Types option must be an array, you must provide the "
                    . "name and the corresponding class of the custom type."
                );
            }
        }
    }
}
