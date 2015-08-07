<?php

namespace NeoPHP\mvc;

class ScriptView extends View
{
    protected $scriptName;
    protected $parameters;
    protected $contents;
    protected $contentKeys;
    
    public function __construct($viewName)
    {
        $this->scriptName = $viewName;
        $this->parameters = array();
        $this->contents = array();
        $this->contentKeys = array();
    }
    
    public final function set($name, $value)
    {
        $this->parameters[$name] = $value;   
    }

    public final function get($name)
    {
        return $this->parameters[$name];
    }
    
    public final function has($name)
    {
        return isset($this->parameters[$name]);
    }
   
    protected final function beginContent ($contentKey)
    {
        array_push($this->contentKeys, $contentKey);
        ob_start();
    }
    
    protected final function endContent ()
    {
        $contentKey = array_pop($this->contentKeys);
        $content = ob_get_clean();
        if (isset($this->contents[$contentKey]))
        {
            $this->contents[$contentKey] .= $content;
        }
        else
        {
            $this->contents[$contentKey] = $content;
        }
    }
    
    protected function loadScript ($scriptName, array $parameters=array())
    {
        @include (str_replace('\\', '/', $scriptName) . ".php");
    }
    
    protected function getScriptContents ($scriptName, array $parameters=array())
    {
        ob_start();
        $this->loadScript($scriptName, $parameters);
        $viewContents = ob_get_clean();
        $viewContents = preg_replace_callback('/<%(\w*)%>/', function ($match) { return isset($this->contents[$match[1]])? $this->contents[$match[1]] : ""; }, $viewContents);
        return $viewContents;
    }
    
    public function render ()
    {
        print ($this->getScriptContents($this->scriptName));
    }
}

?>