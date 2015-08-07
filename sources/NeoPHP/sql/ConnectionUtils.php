<?php

namespace NeoPHP\sql;

use NeoPHP\core\Object;
use NeoPHP\core\reflect\ReflectionAnnotatedClass;
use NeoPHP\util\IntrospectionUtils;
use stdClass;

abstract class ConnectionUtils
{
    const ANNOTATION_TABLE = "table";
    const ANNOTATION_COLUMN = "column";
    const ANNOTATION_EXTRACOLUMN = "extraColumn";
    const ANNOTATION_PARAMETER_TABLENAME = "tableName";
    const ANNOTATION_PARAMETER_COLUMNNAME = "columnName";
    const ANNOTATION_PARAMETER_RELATEDTABLENAME = "relatedTableName";
    const ANNOTATION_PARAMETER_DATAINDEX = "dataIndex";
    const ANNOTATION_PARAMETER_ID = "id";
    
    public static function insertEntity (Connection $connection, Object $entity)
    {
        return $connection->getTable(self::getEntityTableName($entity->getClass()))->insert(self::getEntityValues($entity));
    }
    
    public static function updateEntity (Connection $connection, Object $entity)
    {
        $idProperty = self::getEntityIdProperty($entity->getClass());
        $idColumn = $idProperty->getAnnotation(self::ANNOTATION_COLUMN)->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME);
        $idColumnValue = IntrospectionUtils::getPropertyValue($entity, $idProperty->getName());
        $entityValues = self::getEntityValues($entity);
        unset($entityValues[$idColumn]);
        return $connection->getTable(self::getEntityTableName($entity->getClass()))->addWhere($idColumn, SQL::OPERATOR_EQUAL, $idColumnValue)->update($entityValues);
    }
    
    public static function deleteEntity (Connection $connection, Object $entity)
    {
        $idProperty = self::getEntityIdProperty($entity->getClass());
        $idColumn = $idProperty->getAnnotation(self::ANNOTATION_COLUMN)->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME);
        $idColumnValue = IntrospectionUtils::getPropertyValue($entity, $idProperty->getName());
        return $connection->getTable(self::getEntityTableName($entity->getClass()))->addWhere($idColumn, SQL::OPERATOR_EQUAL, $idColumnValue)->delete();
    }
    
    public static function getEntity (Connection $connection, ReflectionAnnotatedClass $entityClass, $id)
    {
        $idProperty = self::getEntityIdProperty($entityClass);
        $idColumn = $idProperty->getAnnotation(self::ANNOTATION_COLUMN)->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME);
        return $connection->getTable(self::getEntityTableName($entityClass))->addWhere($idColumn, SQL::OPERATOR_EQUAL, $id)->getFirst($entityClass);
    }
    
    public static function getEntities (Connection $connection, ReflectionAnnotatedClass $entityClass)
    {
        return $connection->getTable(self::getEntityTableName($entityClass))->get($entityClass);
    }
    
    public static function completeEntity (Connection $connection, Object $entity)
    {
        $idProperty = self::getEntityIdProperty($entity->getClass());
        $idColumn = $idProperty->getAnnotation(self::ANNOTATION_COLUMN)->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME);
        $idColumnValue = IntrospectionUtils::getPropertyValue($entity, $idProperty->getName());
        $entityValues = $connection->getTable(self::getEntityTableName($entity->getClass()))->addWhere($idColumn, SQL::OPERATOR_EQUAL, $idColumnValue)->getFirst();
        self::setEntityValues($entity, $entityValues);
    }
    
    public static function createEntity (ReflectionAnnotatedClass $entityClass, array $entityFields)
    {
        $entityClassName = $entityClass->getName();
        $entity = new $entityClassName;
        self::setEntityValues($entity, $entityFields);
        return $entity;
    }
    
    private static function setEntityValues (Object $entity, $entityValues, $entitySeparator = "_")
    {
        foreach ($entityValues as $key => $value)
            self::setEntityValue($entity, $key, $value, $entitySeparator);
    }
    
    private static function setEntityValue (Object $entity, $key, $value, $entitySeparator = "_")
    {
        $modelData = $entity->getClass();
        $recursiveEntityPosition = strpos($key, $entitySeparator);
        if ($recursiveEntityPosition != false)
        {
            $subentityTableName = substr($key, 0, $recursiveEntityPosition);
            $subentityColumnName = substr($key, $recursiveEntityPosition+1);
            $properties = $modelData->getProperties();
            foreach ($properties as $property)
            {
                $columnAnnotation = $property->getAnnotation(self::ANNOTATION_COLUMN);
                if ($columnAnnotation == null)
                    $columnAnnotation = $property->getAnnotation(self::ANNOTATION_EXTRACOLUMN);
                if ($columnAnnotation != null && $columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_RELATEDTABLENAME) == $subentityTableName)
                {
                    $subEntity = self::getSubEntity($entity, $property->getName());
                    if ($subEntity != null && $subEntity instanceof DatabaseModel)
                        $subEntity->setFieldValue($subentityColumnName, $value, $entitySeparator);
                    break;
                }
            }
        }
        else
        {
            $processedFieldValue = null;
            $properties = $modelData->getProperties();
            foreach ($properties as $property)
            {
                $columnAnnotation = $property->getAnnotation(self::ANNOTATION_COLUMN);
                if ($columnAnnotation == null)
                    $columnAnnotation = $property->getAnnotation(self::ANNOTATION_EXTRACOLUMN);
                if ($columnAnnotation != null)
                {
                    if ($columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME) == $key)
                    {
                        if ($columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_RELATEDTABLENAME) == null)
                        {
                            $propertyValue = null;
                            $columnDataIndex = $columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_DATAINDEX);
                            $columnDataIndex = isset($columnDataIndex)? intval($columnDataIndex) : -1;
                            if ($columnDataIndex < 0)
                            {
                                IntrospectionUtils::setPropertyValue($entity, $property->getName(), $value);
                                break;
                            }
                            else
                            {
                                $propertyValue = null;
                                if (is_array($value))
                                {
                                    $propertyValue = $value[$columnDataIndex];
                                }
                                else
                                {
                                    if ($processedFieldValue == null)
                                        $processedFieldValue = str_getcsv(substr($value, 1, strlen($value) - 2));
                                    if (isset($processedFieldValue[$columnDataIndex]))
                                    {
                                        $propertyValue = $processedFieldValue[$columnDataIndex];
                                        $propertyValue = trim($propertyValue, '"');
                                    }
                                }
                                IntrospectionUtils::setPropertyValue($entity, $property->getName(), $propertyValue);
                            }
                        }
                        else
                        {   
                            $subEntity = self::getSubEntity($entity, $property->getName());
                            if ($subEntity != null && $subEntity instanceof DatabaseModel)
                            {
                                $subEntityIdProperty = $subEntity->getIdProperty();
                                if ($subEntityIdProperty != null)
                                {
                                    $subEntityColumnAnnotation = $subEntityIdProperty->getAnnotation(self::ANNOTATION_COLUMN);
                                    $subEntity->setFieldValue($subEntityColumnAnnotation->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME), $value, $entitySeparator);
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
    }
    
    private static function getEntityValues (Object $entity)
    {
        $fieldValues = array();
        $metadataProperties = $entity->getClass()->getProperties();
        foreach ($metadataProperties as $property)
        {
            $columnAnnotation = $property->getAnnotation(self::ANNOTATION_COLUMN);
            if ($columnAnnotation != null) 
            {
                $columnName = $columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_COLUMNNAME);
                $columnValue = IntrospectionUtils::getPropertyValue($entity, $property->getName());
                if (isset($columnValue))
                {
                    if ($columnValue instanceof DatabaseModel)
                    {
                        $subEntityIdProperty = $columnValue->getIdProperty();
                        if ($subEntityIdProperty != null)
                        {
                            $columnValue = $columnValue->getPropertyValue($subEntityIdProperty->getName());
                            if (!isset($columnValue))
                                continue;
                        }
                        else
                        {
                            continue;
                        }
                    }
                    $columnDataIndex = $columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_DATAINDEX);
                    $columnDataIndex = isset($columnDataIndex)? intval($columnDataIndex) : -1;
                    if ($columnDataIndex < 0)
                    {
                        $fieldValues[$columnName] = $columnValue;
                    }
                    else
                    {
                        if (!isset($fieldValues[$columnName]) || !is_array($fieldValues[$columnName]))
                            $fieldValues[$columnName] = array();               
                        for ($i = 0; $i < $columnDataIndex; $i++)
                            if (!isset($fieldValues[$columnName][$i]))
                                $fieldValues[$columnName][$i] = "";
                        $fieldValues[$columnName][$columnDataIndex] = $columnValue;
                    }
                }
            }
        }
        return $fieldValues;
    }
    
    private static function getSubEntity (Object $entity, $propertyName)
    {
        $entityClass = $entity->getClass();
        $subentityInstance = IntrospectionUtils::getPropertyValue ($entity, $propertyName, $entityClass);
        if ($subentityInstance == null)
        {
            $setSubentityMethodName = 'set' . ucfirst($propertyName);
            if ($entityClass->hasMethod($setSubentityMethodName))
            {
                $setSubentityMethod = $entityClass->getMethod ($setSubentityMethodName);
                $setSubentityMethodParameters = $setSubentityMethod->getParameters();
                $setSubentityMethodParameter = $setSubentityMethodParameters[0];
                $subentityClass = $setSubentityMethodParameter->getClass();
                if ($subentityClass != null)
                {
                    $subentityClassName = $subentityClass->getName();
                    $subentityInstance = new $subentityClassName();
                }
                else
                {
                    $subentityInstance = new stdClass();
                }
                $setSubentityMethod->invoke($entity, $subentityInstance);
            }
        }
        return $subentityInstance;
    }
    
    private static function getEntityIdProperty (ReflectionAnnotatedClass $entityClass)
    {
        $property = null;
        $properties = $entityClass->getProperties();
        foreach ($properties as $searchProperty)
        {
            $columnAnnotation = $searchProperty->getAnnotation(self::ANNOTATION_COLUMN);
            if ($columnAnnotation != null && $columnAnnotation->getParameter(self::ANNOTATION_PARAMETER_ID) === true)
            {
                $property = $searchProperty;
                break;
            }
        }
        return $property;
    }
    
    private static function getEntityTableName (ReflectionAnnotatedClass $entityClass)
    {
        $entityAnnotation = $entityClass->getAnnotation(self::ANNOTATION_TABLE);
        if ($entityAnnotation == null)
        {    
            $annotationClass = self::getClass();
            while ($entityAnnotation == null)
            {
                $annotationClass = $annotationClass->getParentClass();
                if ($annotationClass != null)
                    $entityAnnotation = $annotationClass->getAnnotation(self::ANNOTATION_TABLE);
                else
                    break;
            }
        }
        return $entityAnnotation != null? $entityAnnotation->getParameter(self::ANNOTATION_PARAMETER_TABLENAME) : false;
    }
}

?>