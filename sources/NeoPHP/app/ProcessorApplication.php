<?php

namespace NeoPHP\app;

use NeoPHP\util\eventhandling\EventDispatcher;

class ProcessorApplication extends Application
{
    private $processors;
    private $eventDispatcher;
    
    protected function configure ()
    {
        parent::configure();
        set_time_limit(0);
    }
    
    protected function initialize()
    {
        parent::initialize();
        $this->processors = array();
        $this->eventDispatcher = new EventDispatcher();
    }
    
    public function addProcessor (Processor $processor)
    {   
        $this->processors[] = $processor;
    }
    
    /**
     * Obtiene el despachador de eventos asociado a la aplicación
     * @return EventDispatcher
     */
    public function getEventDispatcher() 
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher) 
    {
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function start ()
    {
        $this->onStarted();
    }
    
    public function stop ()
    {
        $this->onStopped(); 
        exit(0);
    }
    
    protected function onStarted ()
    {
        if (!empty($this->processors))
        {
            foreach ($this->processors as $processor)
                $processor->start();
        }
    }
    
    protected function onStopped()
    {
        if (!empty($this->processors))
        {
            foreach ($this->processors as $processor)
                $processor->stop();
        }
    }
}

?>