<?php

namespace NeoPHP\mvc;

class ModelManagerWrapper
{
    private $manager;
    
    function __construct(ModelManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function __call ($name, $arguments)
    {
        return call_user_func_array(array($this->manager, $name), $arguments);
    }
}

?>