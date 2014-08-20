<?php

// Inicializa o output buffering em GZIP
ob_start("ob_gzhandler");

// Definições de cache 
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
header("Expires: Wed, 13 Mar 2013 12:00:00 GMT");
header("Vary: Accept-Encoding");

// Muda sempre o cwd para a raiz da aplicação
chdir(dirname($_SERVER['SCRIPT_FILENAME']));
 
// Definições de include
require "autoload.php";
 
// Define o error handler padrão do sistema
error_reporting(0);
set_error_handler("Util::error_handler");
register_shutdown_function("Util::error_handler_shutdown");

try {
    
    // Definições de sessão
    $sessionPath = str_replace("//", "/", dirname($_SERVER['PHP_SELF']) . "/");
    $sessionSecure = isset($_SERVER['HTTPS']) && stripos($_SERVER['HTTPS'], 'on') !== false ? true : false;
    $sessionLifetime = Util::getConfig(Util::CONFIG_GENERAL)->sessionLifetime;
    session_set_cookie_params($sessionLifetime, $sessionPath, null, $sessionSecure, true);
    session_name("__fid");
    session_save_path(getcwd()."/tmp/session/");
    
    // Caso o cookie da sessão esteja indefinido ou fora do padrão remove a sessão e inciará outra
    if(isset($_COOKIE[session_name()]) && preg_match("/^([A-Za-z0-9\-]+)$/", $_COOKIE[session_name()]) === 0){
        setcookie(session_name(), "", time()-86400);
        header("Location: ".Util::getBaseUrl());
        die();
    }
    
    // Se receber o request de limpar sessão
    if(isset($_GET['__clearSession'])){
        session_regenerate_id(true);
        Zend_Auth::getInstance()->getStorage()->clear();
        header("Location: ".Util::getBaseUrl());
        die();
    }
    
    // Inicio do sistema
    if(Util::isDev() == true){
        header('Fenix-Dev: true');
    }
    
    // Se o profiler está ativo e a extensão xhprof carregada 
    if(Util::isProfilerActive() && extension_loaded("xhprof")){
        xhprof_enable();
    }
    
    // Requerimento de conexão HTTPS
    $config = Util::getConfig(Util::CONFIG_GENERAL);
    if(Util::isCli() === false && isset($config->https) && $config->https == 'force'){
        header("Strict-Transport-Security: max-age=15768000 ; includeSubDomains");
        if(Util::getHttps() === false){
            header("Location: " . Util::getBaseUrl(true));
            die();
        }
    }
    
    // Security Headers
//     header("Content-Security-Policy: script-src 'self'");
    header("X-Frame-Options: SAMEORIGIN");
    
    // Faz o despacho da execução da action
    $defaultModule = isset($config->defaultModule) ? $config->defaultModule : "fenix";
    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    
    $controllerFront = Eramo_Controller_Front::getInstance();
    $controllerFront->setDefaultModule( $defaultModule );
    $controllerFront->dispatch($_SERVER['SCRIPT_NAME'], $requestUri);
    
} catch (Exception $e){
    
    Util::exception($e);
    
}
