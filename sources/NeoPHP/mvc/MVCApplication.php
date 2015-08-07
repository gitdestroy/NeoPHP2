<?php

namespace NeoPHP\mvc;

use Exception;
use NeoPHP\app\Application;
use NeoPHP\io\File;
use NeoPHP\util\StringUtils;

class MVCApplication extends Application 
{
    const ANNOTATION_ROUTE = "route";
    const ANNOTATION_ROUTEACTION = "routeAction";
    const ANNOTATIONPARAMETER_PATH = "path";
    const ANNOTATIONPARAMETER_ACTION = "action";
    
    private $routes;
        
    public function processAction ($action, $params=array())
    {
        $actionExecuted = false;
        try
        {
            $action = $this->normalizeAction($action);
            $this->executeAction ($action, $params);
            $actionExecuted = true;
        }
        catch (Exception $exception)
        {
            try 
            {
                $this->onActionError ($action, $exception);
            } 
            catch (Exception $error) {}
        }
        return $actionExecuted;
    }
    
    protected function onActionError ($action, Exception $ex)
    {
        $this->getLogger()->error($ex);
    }
    
    protected function normalizeAction ($action="")
    {
        $action = trim($action);
        if (!StringUtils::startsWith($action, "/"))
            $action = "/" . $action;
        return $action;
    }
    
    protected function executeAction ($action, $params=array())
    {
        $route = $this->getRouteForAction($action, $params);
        if ($route == null)
            throw new NoRouteException ("No route for action: \"" . $action . "\"");

        $controllerClassName = $route->getController();
        $controllerAction = $route->getControllerAction();
        return $controllerClassName::getInstance()->executeAction ($controllerAction, $params);
    }
    
    public function addRoute (Route $route)
    {
        if (empty($this->routes[$route->getAction()]))
            $this->routes[$route->getAction()] = array();
        $this->routes[$route->getAction()][] = $route;
    }
    
    protected function matchRouteForAction (Route $route, $action, $params=array())
    {
        return $route->getAction() == $action;
    }
    
    /**
     * Obtiene una ruta para una accion dada
     * @param type $action accion solicitada
     * @param type $params parámetros de la acción
     * @return Route ruta para la acción
     */
    protected function getRouteForAction ($action, $params=array())
    {
        $route = null;
        $routes = (!empty($this->routes) && !empty($this->routes[$action]))? $this->routes[$action] : array(); 
        foreach ($routes as $testRoute)
        {
            if ($this->matchRouteForAction($testRoute, $action, $params))
            {
                $route = $testRoute;
                break;
            }
        }
        return $route;
    }
    
    public function addRoutesFromAnnotations ($useCache=true)
    {
        $routesCacheFile = new File(getcwd() . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "routes.txt");
        if ($useCache && $routesCacheFile->exists())
        {
            $this->routes = unserialize($routesCacheFile->getContent());
        }
        else
        {
            $applicationDirFile = new File(dirname($this->getClass()->getFileName()));
            $applicationDirFile->iterateFiles(function($filename)
            {
                $fileContent = @file_get_contents($filename);
                if (preg_match("/@" . self::ANNOTATION_ROUTE . "\s*\(/", $fileContent, $routeMatches))
                {
                    //Obtención del nombre de la clase
                    if (!preg_match("/namespace\s*(.+)\s*;/", $fileContent, $nsMatches))
                        return;
                    if (!preg_match("/class\s*([\w]+)\s*/", $fileContent, $classMatches))
                        return;
                    $className = $nsMatches[1] . "\\" . $classMatches[1];

                    //Obtención de los metadatos de la clase
                    if (!is_subclass_of($className, Controller::getClassName()))
                        return;
                    $classData = $className::getClass ();

                    //Obtencion de las rutas
                    $classRoutes = array();
                    foreach ($classData->getAnnotations() as $classAnnotation)
                    {
                        if ($classAnnotation->getName() == self::ANNOTATION_ROUTE)
                        {
                            $routePath = $classAnnotation->getParameter(self::ANNOTATIONPARAMETER_PATH);
                            if (!StringUtils::startsWith($routePath, "/"))
                                $routePath = "/" . $routePath;
                            if (!StringUtils::endsWith($routePath, "/"))
                                $routePath .= "/";
                            $classRoutes[] = $routePath;
                        }
                    }
                    if (empty($classRoutes))
                        return;

                    //Iterar por los metodos de la clase para obtener las acciones
                    foreach ($classData->getMethods() as $classMethod)
                    {
                        foreach ($classMethod->getAnnotations() as $classMethodAnnotation)
                        {
                            if ($classMethodAnnotation->getName() == self::ANNOTATION_ROUTEACTION)
                            {
                                $actionParameters = $classMethodAnnotation->getParameters();
                                unset($actionParameters[self::ANNOTATIONPARAMETER_ACTION]);
                                foreach ($classRoutes as $route)
                                {
                                    $this->addRoute(new Route($route . $classMethodAnnotation->getParameter(self::ANNOTATIONPARAMETER_ACTION), $className, $classMethod->getName(), (!empty($actionParameters))? $actionParameters : array()));
                                }
                            }
                        }
                    }
                }
            }, true, '/^.+\.php$/i');
        
            $fileParent = $routesCacheFile->getParentFile();
            if (!$fileParent->exists())
                $fileParent->mkdirs();
            $routesCacheFile->setContent(serialize($this->routes));
        }
    }
}

?>