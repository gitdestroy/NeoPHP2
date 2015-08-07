<?php

namespace NeoPHP\sql;

class SQLConditionGroup
{
    private $conditions;
    
    public function __construct()
    {
        $this->conditions = [];
    }
    
    public function clear ()
    {
        $this->conditions = [];
        return $this;
    }
    
    public function isEmpty()
    {
        return empty($this->conditions);
    }
    
    public function getConditions()
    {
        return $this->conditions;
    }
    
    public function addCondition ($operand1, $operator, $operand2, $connector = SQL::OPERATOR_AND)
    {
        $this->conditions[] = ["operand1"=>$operand1, "operator"=>$operator, "operand2"=>$operand2, "connector"=>$connector];
        return $this;
    }
    
    public function addRawCondition ($expression, array $bindings = [], $connector = SQL::OPERATOR_AND)
    {
        $this->conditions[] = ["expression"=>$expression, "bindings"=>$bindings, "connector"=>$connector];
        return $this;
    }
    
    public function addConditionGroup (SQLConditionGroup $conditionGroup, $connector = SQL::OPERATOR_AND)
    {
        $this->conditions[] = ["group"=>$conditionGroup, "connector"=>$connector];
        return $this;
    }
}

?>