<?php

namespace NeoPHP\mvc;

use NeoPHP\core\Object;

abstract class View extends Object
{ 
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
    
    public abstract function render();
}

?>