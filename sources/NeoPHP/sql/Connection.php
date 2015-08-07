<?php

namespace NeoPHP\sql;

use Exception;
use NeoPHP\core\Object;
use NeoPHP\core\reflect\ReflectionAnnotatedClass;
use NeoPHP\util\logging\Logger;
use PDO;
use PDOStatement;

abstract class Connection extends Object
{
    protected static $instances = array();
    private $connection;
    private $logger;
    private $loggingEnabled;
    private $ignoreUpdates;
    
    public final function __construct ()
    {
        $this->connection = null;
        $this->logger = null;
        $this->loggingEnabled = false;
        $this->ignoreUpdates = false;
    }
    
    /**
     * Obtiene una instancia unica de la base de datos
     * @return Connection
     */
    public static function getInstance ()
    {
        $calledClass = get_called_class();
        if (!isset(self::$instances[$calledClass]))
            self::$instances[$calledClass] = new $calledClass();
        return self::$instances[$calledClass];
    }
      
    /**
     * Obtiene una conexión con la base de datos
     * @return PDO objeto pdo de base de datos
     */
    private final function getConnection ()
    {
        if (empty($this->connection))
        {
            $this->connection = new PDO ($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getDriverOptions());
            $this->connection->setAttribute (PDO::ATTR_CASE, PDO::CASE_LOWER);
            $this->connection->dbtype = $this->getDatabaseType();
        }
        return $this->connection;
    }
    
    protected function getDsn ()
    {
        $dsn = "{$this->getDatabaseType()}:host={$this->getHost()};dbname={$this->getDatabaseName()}";
        $port = $this->getPort();
        if (!empty($port))
            $dsn .= ";port=$port";
        return $dsn;
    }
    
    /**
     * Retorna el Logger asociado a la base de datos
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
    
    public function setLoggingEnabled ($logginEnabled)
    {
        $this->loggingEnabled = $logginEnabled;
    }
    
    public function isLoggingEnabled ()
    {
        return $this->loggingEnabled;
    }
    
    public function setIgnoreUpdates ($ignoreUpdates)
    {
        $this->ignoreUpdates = $ignoreUpdates;
    }
    
    public function isIgnoreUpdates ()
    {
        return $this->ignoreUpdates;
    }
    
    public final function beginTransaction ()
    {
        return $this->getConnection()->beginTransaction();
    }
    
    public final function commitTransaction ()
    {
        return $this->getConnection()->commit();
    }
    
    public final function rollbackTransaction ()
    {
        return $this->getConnection()->rollBack();
    }
    
    public final function getLastInsertedId ($sequenceName=null)
    {
        return $this->getConnection()->lastInsertId($sequenceName);
    }
    
    /**
     * Obtiene un Statement con los resultados de la búsqueda
     * @param type $sql
     * @param array $bindings
     * @return PDOStatement
     * @throws Exception
     */
    public final function query ($sql, array $bindings = array())
    {
        if ($this->loggingEnabled && $this->logger != null)
            $this->logger->info("SQL: " . $sql . (!empty($bindings)? "   [" . implode(",", $bindings) . "]" : ""));
        
        $resultStatement = false;
        if (empty($bindings))
        {
            $resultStatement = $this->getConnection()->query($sql);
        }
        else
        {
            $resultStatement = $this->getConnection()->prepare($sql);
        }
        
        if ($resultStatement === false)
        {
            $errorData = $this->getConnection()->errorInfo();
            throw new Exception ("Unable to execute sql \"" . $sql . "\" " . $errorData[2]);
        }
        
        if (!empty($bindings))
            $resultStatement->execute ($bindings);
        return $resultStatement;
    }
    
    public final function exec ($sql, array $bindings = [])
    {
        if ($this->loggingEnabled && $this->logger != null)
            $this->logger->info("SQL: " . $sql . (!empty($bindings)? "   [" . implode(",", $bindings) . "]" : ""));
        
        $affectedRows = false;
        if (!$this->ignoreUpdates)
        {    
            if (empty($bindings))
            {
                $affectedRows = $this->getConnection()->exec($sql);
            }
            else
            {
                $preparedStatement = $this->getConnection()->prepare($sql);
                if ($preparedStatement != false)
                {
                    $preparedStatement->execute($bindings);
                    $affectedRows = $preparedStatement->rowCount();
                }
            }
            
            if ($affectedRows === false)
            {
                $errorData = $this->getConnection()->errorInfo();
                throw new Exception ("Unable to execute sql \"" . $sql . "\" " . $errorData[2]);
            }
        }
        return $affectedRows;
    }
    
    public final function quote ($parameter, $parameterType = PDO::PARAM_STR)
    {
        return $this->getConnection()->quote($string, $parameterType);
    }
    
    public function getTable ($tableName)
    {
        return new SQLDataTable($this, $tableName);
    }
    
    public function insertEntity (Object $entity)
    {
        return ConnectionUtils::insertEntity($this, $entity);
    }
    
    public function updateEntity (Object $entity)
    {
        return ConnectionUtils::updateEntity($this, $entity);
    }
    
    public function deleteEntity (Object $entity)
    {
        return ConnectionUtils::deleteEntity($this, $entity);
    }
    
    public function completeEntity (Object $entity)
    {
        return ConnectionUtils::completeEntity($this, $entity);
    }
    
    public function getEntity (ReflectionAnnotatedClass $entityClass, $id)
    {
        return ConnectionUtils::getEntity($this, $entityClass, $id);
    }
    
    public function getEntities (ReflectionAnnotatedClass $entityClass)
    {
        return ConnectionUtils::getEntities($this, $entityClass);
    }
    
    public function getUsername ()
    {
        return null;
    }
    
    public function getPassword ()
    {
        return null;
    }
    
    public function getDriverOptions ()
    {
        return array();
    }
    
    public function getPort ()
    {
        return null;
    }
    
    public abstract function getDatabaseType ();
    public abstract function getHost ();
    public abstract function getDatabaseName ();
}

?>