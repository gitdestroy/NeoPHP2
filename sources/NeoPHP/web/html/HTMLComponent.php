<?php

namespace NeoPHP\web\html;

abstract class HTMLComponent implements HTMLElement
{
    public function toHtml ($offset=0)
    {
        $this->build();
        return ($this->_content != null)? $this->_content->toHtml($offset) : "";
    }
    
    protected final function build ()
    {
        if (empty($this->builded))
        {
            $this->onBeforeBuild();
            $this->_content = $this->createContent();
            $this->builded = true;
            $this->onBuild();
        }
    }
    
    protected function createContent ()
    {
        return null;
    }
    
    public function onAdded ($parent)
    {
        $this->build();
    }
    
    protected function onBeforeBuild () {}
    protected function onBuild () {}
    
    public function __toString()
    {
        return $this->toHtml();
    }
}

?>