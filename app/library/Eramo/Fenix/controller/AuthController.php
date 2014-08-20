<?php

abstract class Fenix_AuthController extends Eramo_Controller_Action {
    
    /**
     * Action de logon do usuário no sistema
     */
    public function logonAction(){
        
        $location = "-1";
        
        if($_REQUEST['login'] && $_REQUEST['password']){
            
            $tfa = isset($_REQUEST['tfa']) ? $_REQUEST['tfa'] : null;
            
            $remember = isset($_REQUEST['remember']) ? true : false;
            
            $rememberTfa = isset($_REQUEST['remember_tfa']) ? true : false;
            
            $logon = Util::getModelUser()->logon($_REQUEST['login'], $_REQUEST['password'], $tfa, $remember, $rememberTfa);
            
            // Se a autenticação teve sucesso
            if ( $logon === true ) {
                
                // Se a autenticação teve sucesso mas ainda não há identidade solicita o código de autenticação
                if(!Zend_Auth::getInstance()->hasIdentity()){
                    
                    $location = "tfa";
                    
                }
                // Caso já tenha identidade (sucesso completo) faz o redirecionamento para a action padrão ou a solicitada pela URL
                else {
                    
                    $location = Util::getBaseUrl() . Util::getConfig( Util::CONFIG_GENERAL )->defaultAction;
                    
                    if(isset($_REQUEST['redir']) && !empty($_REQUEST['redir'])){
                        $location = $_REQUEST['redir'];
                    }
                    
                }
            
            }
            
        }
        
        $isXhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest";
        
        if(!$isXhr){
            $else = "window.parent.location = '{$location}'";
            if($location === "-1"){
                $else = "window.parent.$('#fail').css('display', '');";
            }
            $location = "<script type='text/javascript'>if(window.parent.logonCallback) window.parent.logonCallback('{$location}'); else {$else}</script>";
        }
        
        die($location);
    }
    
    /**
     * Action de logoff no sistema
     */
    public function logoffAction(){
        Zend_Auth::getInstance()->clearIdentity();
        header("Location: " . Util::getBaseUrl());
        die();
    }

}


