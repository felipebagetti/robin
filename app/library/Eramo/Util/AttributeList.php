<?php

/**
 * Abstract class that holds a list of attributes
 */
abstract class AttributeList {
    
    /**
     * Attribute list
     * @var String[]
     */
    protected $_attributes = array();
    
    /**
     * Returns the required attributes of this object 
     */
    abstract protected function _required();
    
    /**
     * Create a class instance with the specified list of attributes
     */
    public function __construct($attributes = null){
        $this->_attributes = $attributes;
        
        // Validates
        $this->validate();
    }
    
    /**
     * Validates that this object has all the required attributes filled
     * 
     * @throws Exception
     */
    public function validate(){
        foreach($this->_required() as $attribute){
            if(!isset($this->_attributes[$attribute]) || !$this->_attributes[$attribute]){
                throw new Exception(get_class($this) . ": required object attribute $attribute is not set!");
            }
        }
    }
    
    /**
     * Unset a attribute
     * @param String $key
     * @param mixed $value
     */
    public function __unset($key){
        unset($this->_attributes[$key]);
        return $this;
    }
    
    /**
     * Magic method to set a attribute's value
     * @param String $key
     * @param mixed $value
     */
    public function __set($key, $value){
        $this->_attributes[$key] = $value;
        return $this;
    }

    /**
     * Magic method to get a attribute's value
     * 
     * @param String $key
     * @param mixed $value
     */
    public function __get($key){
        return isset($this->_attributes[$key]) ? $this->_attributes[$key] : null;
    }
    
    /**
     * Set a attribute's value
     * 
     * @param String $key
     * @param mixed $value
     */
    public function setAttribute($key, $value){
        return $this->__set($key, $value);
    }
    
    /**
     * Returns the selected attribute value
     * 
     * @param String $key
     * @param mixed $value
     */
    public function getAttribute($key){
        return $this->__get($key);
    }
    
    /**
     * Returns the attribute list as an array
     * 
     * @return String[][]
     */
    public function getAttributes(){
        return $this->_attributes;
    }
    
    /**
     * Magic method to allow setters to be called like setAttributeName where
     * AttributeName is the name of the attribute being set or get 
     * 
     * @param String $method
     * @param String $args
     * 
     * @return mixed
     */
    public function __call($method, $args){
        if(strncmp($method, "get", 3) === 0){
            return $this->__get( lcfirst(substr($method, 3)) );
        } else if(strncmp($method, "set", 3) === 0){
            return $this->__set( lcfirst(substr($method, 3)) , $args[0] );
        }
    }
} 















