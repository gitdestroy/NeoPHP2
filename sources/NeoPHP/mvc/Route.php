<?php

namespace NeoPHP\mvc;

class Route
{
    private $action;
    private $controller;
    private $controllerAction;
    private $parameters;
    
    public function __construct($action, $controller, $controllerAction, array $parameters = array())
    {
        $this->action = $action;
        $this->controller = $controller;
        $this->controllerAction = $controllerAction;
        $this->parameters = $parameters;
    }
    
    public function getAction()
    {
        return $this->action;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getControllerAction()
    {
        return $this->controllerAction;
    }

    public function getParameters()
    {
        return $this->parameters;
    }
}