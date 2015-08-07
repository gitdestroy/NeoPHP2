<?php

namespace NeoPHP\util;

use ReflectionFunction;
use ReflectionMethod;

abstract class FunctionUtils
{
    public static function call (callable $callable, array $parameters=array())
    {
        $parameterIndex = 0;
        $callableParameters = array();
        $callableData = is_array($callable)? (new ReflectionMethod($callable[0],$callable[1])) : (new ReflectionFunction($callable));
        foreach ($callableData->getParameters() as $parameter)
        {
            $parameterName = $parameter->getName();
            $parameterValue = null;
            if (array_key_exists($parameterName, $parameters))
                $parameterValue = $parameters[$parameterName];
            else if (array_key_exists($parameterIndex, $parameters))
                $parameterValue = $parameters[$parameterIndex];       
            if ($parameterValue == null && $parameter->isOptional())
                $parameterValue = $parameter->getDefaultValue();
            $callableParameters[] = $parameterValue;
            $parameterIndex++;
        }
        return call_user_func_array($callable, $callableParameters);
    }
}

?>