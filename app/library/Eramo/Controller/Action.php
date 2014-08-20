<?php

class Eramo_Controller_Action {
    
    protected $_module = null;
    protected $_controller = null;
    protected $_action = null;
    
    /**
     * Cria o objeto
     * 
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    public function __construct($module, $controller, $action){
        $this->_module = $module;
        $this->_controller = $controller;
        $this->_action = $action;
    }
    
    /**
     * M�todo executado sempre antes de uma Action ser ativada, deve ser utilizado
     * para realizar defini��es padr�o do controller sendo criado
     */
    public function preDispatch(){
        
        
    }
    
}



















