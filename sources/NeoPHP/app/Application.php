<?php

namespace NeoPHP\app;

use ErrorException;
use Exception;
use NeoPHP\core\Object;
use NeoPHP\util\logging\handler\FileHandler;
use NeoPHP\util\logging\Logger;
use NeoPHP\util\memory\MemCache;
use NeoPHP\util\translation\Translator;
use ReflectionClass;
use stdClass;

abstract class Application extends Object
{
    protected static $instances = array();
    protected $name;
    protected $settings;
    
    private final function __construct ()
    {
        try
        {
            if (empty($settingsFilename))
                $settingsFilename = realpath("") . DIRECTORY_SEPARATOR . "settings.json";
            if (file_exists($settingsFilename))
            {
                $settingsFileContent = file_get_contents($settingsFilename);
                $this->settings = json_decode($settingsFileContent);
            }
        }
        catch (Exception $ex)
        {
            $this->settings = new stdClass();
        }
        $this->configure();
        $this->initialize();
    }
    
    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (!isset(self::$instances[$calledClass]))
        {
            $newInstance = new $calledClass();
            $class = new ReflectionClass($calledClass);
            while ($class !== false)
            {
                $className = $class->getName();
                self::$instances[$className] = $newInstance;       
                $class = $class->getParentClass(); 
                if ($className == get_class())
                    break;
            }
        }
        return self::$instances[$calledClass];
    }
    
    protected function configure ()
    {
        set_error_handler(array($this, "errorHandler"), E_ALL);  
    }
    
    protected function initialize ()
    {
    }
    
    public function errorHandler ($errno, $errstr, $errfile, $errline)
    {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
    
    public function setName ($name)
    {
        $this->name = $name;
    }
    
    public function getName ()
    {
        return $this->name;
    }
    
    public function getSettings ()
    {
        return $this->settings;
    }
    
    public function getLogger ()
    {
        if (!isset($this->logger))
        {
            $this->logger = new Logger();
            $this->logger->addHandler(new FileHandler(getcwd() . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "{Y}-{m}-{d}.txt"));
        }
        return $this->logger;
    }
    
    public function getCacheManager ()
    {
        if (!isset($this->cacheManager))
            $this->cacheManager = new MemCache();
        return $this->cacheManager;
    }
    
    public function getTranslator ()
    {
        if (!isset($this->translator))
        {
            $this->translator = new Translator();
            $this->translator->setResourcesPath(isset($this->settings->resourcesPath)? $this->settings->resourcesPath : (realpath("") . DIRECTORY_SEPARATOR . "resources"));
        }
        return $this->translator;
    }
}

?>