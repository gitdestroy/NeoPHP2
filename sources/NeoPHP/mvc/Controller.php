<?php

namespace NeoPHP\mvc;

use Exception;
use NeoPHP\core\Object;
use NeoPHP\util\FunctionUtils;

abstract class Controller extends Object
{
    protected static $instances = array();
    
    protected function __construct ()
    {
    }
    
    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (!isset(self::$instances[$calledClass]))
            self::$instances[$calledClass] = new $calledClass();
        return self::$instances[$calledClass];
    }
    
    protected static function getApplication ()
    {
        return MVCApplication::getInstance();
    }
    
    protected function getLogger ()
    {
        return $this->getApplication()->getLogger();
    }
    
    protected function getTranslator ()
    {
        return $this->getApplication()->getTranslator();
    }
    
    protected function getText ($key, $language=null)
    {
        return $this->getTranslator()->getText($key, $language);
    }
    
    public function executeAction ($action, array $parameters = array())
    {
        $response = false;
        try
        {
            if ($this->onBeforeActionExecution($action, $parameters) === true)
            {
                $response = FunctionUtils::call(array($this, $action), $parameters);
                $response = $this->onAfterActionExecution ($action, $parameters, $response);
            }
        }
        catch (Exception $ex)
        {
            if (method_exists($this, "onActionError"))
                $this->onActionError($action, $ex);
            else
                throw $ex;
        }
        return $response;
    }
    
    protected function onBeforeActionExecution ($action, $parameters)
    {   
        return true;
    }
    
    protected function onAfterActionExecution ($action, $parameters, $response)
    {
        if (!empty($response))
        {
            if ($response instanceof View)
            {
                $response->render();
            }
            else if (is_object($response))
            {
                print json_encode($response);
            }
            else
            {
                print $response;
            }
        }
    }
}

?>