<?php

namespace NeoPHP\web;

use NeoPHP\mvc\Controller;
use NeoPHP\util\TraitUtils;
use NeoPHP\web\http\Request;
use NeoPHP\web\http\Session;

abstract class WebController extends Controller
{
    /**
     * Obtiene la petición web efectuada
     * @return Request Petición web
     */
    protected final function getRequest ()
    {
        return Request::getInstance();
    }
    
    protected final function getSession ()
    {
        return Session::getInstance();
    }
    
    protected function onAfterActionExecution ($action, $parameters, $response)
    {
        if (!empty($response) && is_object($response) && TraitUtils::isUsingTrait($response, "NeoPHP\\web\\http\\ResponseTrait"))
        {
            $response->send();
        }
        else
        {
            parent::onAfterActionExecution($action, $parameters, $response);
        }
    }
}

?>