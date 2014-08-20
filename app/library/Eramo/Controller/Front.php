<?php

class Eramo_Controller_Front {
    
    protected static $_instance = null; 
    
    protected $_controllerDirectory = array();
    
    protected $_defaultModule = "fenix";
    
    protected $_dispatcher = array();
    
    protected function __construct(){
        // Define os diretórios padrão de controlers
        $controllerDir = array();
        $controllerDir['fenix'] = 'app/fenix/controller/';
        
        $this->setControllerDirectory( $controllerDir );
    }
    
    /**
     * @return Eramo_Controller_Front
     */
    public static function getInstance(){
        if(self::$_instance == null){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function setDefaultModule($module){
        $this->_defaultModule = $module;
    }
    
    public function setControllerDirectory($controllerDirectory){
        $this->_controllerDirectory = $controllerDirectory;
    }
    
    public function getControllerDirectory(){
        return $this->_controllerDirectory;
    }
    
    public function getDispatcher(){
        return $this->_dispatcher;
    }
    
    public function getModules(){
        $controllerDir = $this->_controllerDirectory;
        
        $modules = array_keys($controllerDir);
        
        foreach(scandir("app/modules") as $module){
            if(stripos($module, ".") === 0){
                continue;
            }
            $modules[] = $module;
        }
        
        return $modules;
    }
    
    protected function _parseUri($scriptName, $requestUri){
        
        $requestUri = preg_replace("|([\/]{2,})|", "/", $requestUri);
        $requestUri = current(explode("?", $requestUri));
        
        $ret = array();
        
        $path = dirname($scriptName) . "/";
        $path = str_replace("//", "/", $path);
        
        if($path == '/' || $path == "./"){
            $path = ''; 
        }
        
        $extra = $requestUri;
        
        if($path != ''){
            $extra = substr_replace($requestUri,"",strpos($requestUri,$path),strlen($path));
        }
        
        $parts = array();
        
        if($extra){
            $parts = explode("/", $extra);
        }
        
        if(isset($parts[0]) && !strlen($parts[0])){
            unset($parts[0]);
            $parts = array_values($parts);
        }
        
        $ret['module'] = $this->_defaultModule;
        $ret['controller'] = "index";
        $ret['action'] = "index";
        
        $parts = array_slice($parts, 0, 3);
        
        if(count($parts) >= 3){
            
            if(isset($parts[2])){
                $ret['module'] = strlen($parts[0]) > 0 ? $parts[0] : $ret['module'];
                $ret['controller'] = $parts[1];
                $ret['action'] = strlen($parts[2]) > 0 ? $parts[2] : $ret['action'];                
            } else {
                $ret['module'] = $parts[0];
                $ret['controller'] = $parts[1];
                $ret['action'] = $parts[2];                
            }
            
        } else if(count($parts) == 2){
            
            if(isset($parts[1])){
                $ret['controller'] = strlen($parts[0]) > 0 ? $parts[0] : $ret['controller'];                
                $ret['action'] = strlen($parts[1]) > 0 ? $parts[1] : $ret['action'];
            } else {
                $ret['action'] = $parts[0];
            }
            
            
        } else if(count($parts) == 1){
            $ret['action'] = $parts[0] ? $parts[0] : "index";
        }
        
        $ret['parts'] = $parts;
        
        return $ret;
    }
    
    protected function _dispatchModule($dispatcher){
        
    }
    
    protected function _dispatchController($dispatcher){
        
        $dir = "app/modules/" . $dispatcher['module'] . "/controller/";
        
        if(isset($this->_controllerDirectory[$dispatcher['module']])){
            $dir = $this->_controllerDirectory[$dispatcher['module']];
        }
        
        $className = ucwords(str_replace("-", " ", $dispatcher['controller']));
        $className .= "Controller";
        
        $className = str_replace(" ", "", $className);
        
        $controllerFilename = $dir . $className . ".php";
        
        if($className == "CgiBinController" && !file_exists($controllerFilename) && Util::isDev() === false){
            header("Not Found", true, 404);
            die("");
        }
        
        if(!file_exists($controllerFilename) || is_readable($controllerFilename)){
            require_once $controllerFilename;
        }
        
        // Tenta cria a classe no formato <name>Controller, caso não exista tenta criar no formato <module>_<name>Controller
        if( !class_exists($className) ){
            
            $moduleName = ucwords(str_replace("-", " ", $dispatcher['module']));
            
            if( class_exists($moduleName."_".$className) ){
                $className = $moduleName."_".$className;
            } else {
                throw new Exception("Class '{$className}' or '{$moduleName}_{$className}' not found in '".get_class($controller)."'.");
            }
        } 
        
        $controller = new $className($dispatcher['module'], $dispatcher['controller'], $dispatcher['action']);
        
        return $controller;
    }
    
    protected function _dispatchAction($dispatcher, Eramo_Controller_Action $controller){
        
        $actionNameParts = explode(" ", str_replace("-", " ", $dispatcher['action']));
        
        $actionName = $actionNameParts[0];
        
        if(count($actionNameParts) > 1){
            $actionName .= ucwords(implode(" ", array_slice($actionNameParts, 1)));
            $actionName = str_replace(" ", "", $actionName);
        }
        
        $actionName .= "Action";
        
        // Executa o preDispatch para que o controller possa se preparar
        $controller->preDispatch();
        
        // Executa a action localizada
        if(method_exists($controller, $actionName) || method_exists($controller, "__call")){
            $controller->$actionName(); 
        } else if($actionNameParts[0] == "robots.txt") {
            header('Content-Type: text');
            die("User-agent: *\nDisallow: ");
        } else if($actionName == "cgiBinAction" && Util::isDev() === false) {
            header("Not Found", true, 404);
            die("");
        } else {
            throw new Exception("Action '{$actionName}' in '".get_class($controller)."' not found.");
        }
    }
    
    public function dispatch($scriptName, $requestUri){
        
        if(isset($_SERVER['argv']) && count($_SERVER['argv'])){
            foreach($_SERVER['argv'] as $key => $arg){
                if($arg == "--action"){
                    if(isset($_SERVER['argv'][$key+1])){
                        $requestUri = $_SERVER['argv'][$key+1];
                    }
                    if(isset($_SERVER['argv'][$key+2])){
                        $request = explode("&", $_SERVER['argv'][$key+2]);
                        foreach($request as $var){
                            $var = explode("=", $var);
                            $_REQUEST[$var[0]] = $var[1];
                        }
                    }
                }
            }
        }
        
        $this->_dispatcher = $this->_parseUri($scriptName, $requestUri);
        
        $this->_dispatchModule( $this->_dispatcher );
        
        $controller = $this->_dispatchController( $this->_dispatcher );
        
        $this->_dispatchAction($this->_dispatcher, $controller);
    }
    
}



















