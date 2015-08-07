<?php

namespace NeoPHP\mvc;

use NeoPHP\core\Object;

abstract class ModelManager extends Object
{
    protected static $instances = array();
    
    protected function __construct ()
    {
    }
    
    public static function getInstance()
    {
        $calledClass = get_called_class();
        if (!isset(self::$instances[$calledClass]))
            self::$instances[$calledClass] = new ModelManagerWrapper(new $calledClass());
        return self::$instances[$calledClass];
    }  
}

?>