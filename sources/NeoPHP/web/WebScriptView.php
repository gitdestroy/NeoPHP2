<?php

namespace NeoPHP\web;

use NeoPHP\mvc\ScriptView;
use NeoPHP\web\http\Session;
use NeoPHP\web\http\ResponseTrait;

class WebScriptView extends ScriptView
{
    use ResponseTrait;
    
    protected final function getBaseUrl ()
    {
        return $this->getApplication()->getBaseUrl();
    }
    
    protected final function getUrl ($action="", $params=array())
    {
        return $this->getApplication()->getUrl($action, $params);
    }
    
    protected final function getRequest ()
    {
        return Request::getInstance();
    }
    
    protected final function getSession ()
    {
        return Session::getInstance();
    }
    
    public function send() 
    {
        $this->setContent($this->getScriptContents($this->scriptName));
        $this->sendHeaders();
        $this->sendContent();
    }
}

?>