<?php

/**
 * Classe com fun��es padr�o do sistema
 *
 * @copyright Eramo Software
 */
class Util {
    
    /**
     * Caminho do arquivo padr�o de configura��o
     * @var String
     */
    const _CONFIG_FILE = "app/config/config.json";
    
    /**
     * Tipo de configura��o geral do sistema
     * @var String
     */
    const CONFIG_GENERAL = 'general';
    
    /**
     * Tipo de configura��o do banco de dados
     * @var String
     */
    const CONFIG_DATABASE = 'database';
    
    /**
     * Tipos de adaptadores de de bancos de dados
     * @var string
     */
    const PDO_SQLITE = 'PDO_SQLITE';
    const PDO_PGSQL = 'PDO_PGSQL';
    
    /**
     * Tipo de configura��o de autentica��o
     * @var String
     */
    const CONFIG_AUTH = 'auth';
    
    /**
     * Tipo de configura��o de perfis
     * @var String
     */
    const CONFIG_PROFILE = 'profile';
    
    /**
     * Tipo de configura��o de menu
     * @var String
     */
    const CONFIG_MENU = 'menu';
    
    /**
     * Tipo de configura��o de armazenamento de arquivos
     * @var String
     */
    const CONFIG_FILE = 'file';
    
    /**
     * Constante para configura��es que precisam ser definidas
     * @var String
     */
    const _CONFIG_MUST_BE_SET = 'configMustBeSet';
    
    /**
     * ID do perfil do administrador geral (onde n�o h� checagem de permiss�es)
     * @var int
     */
    const _ID_PERFIL_ADMINISTRADOR = 1; 
    
    /**
     * Cache das configura��es carregadas do arquivo
     * @var String[][]
     */
    protected static $configJson = null;
    protected static $configArray = null;
    
    protected static $_cacheTagPrefix = null;
    
    protected static $_cacheParseFieldSelectData = array();
    
    /**
     * Configura��es padr�o do sistema (n�o precisam estar no arquivo de configura��o)
     * @var String[][]
     */
    protected static $configDefaults = array(
            
            self::CONFIG_GENERAL => array(
            							'sessionLifetime' => 0,
            							'dev' => false,
                                        ),
            
            self::CONFIG_FILE => array( 'model' => 'file',
                                        'column_hash' => 'hash',
                                        'column_info' => 'info',
                                        'column_oid' => 'oid',
                                        'column_bzip2' => 'bzip2',
                                        'column_size' => 'size' ),
            
            self::CONFIG_AUTH => array( 'model' => '', // indica que a configura��o � obrigat�ria
                                        'tfa' => false,
                                        'column_profile' => 'id_profile',
                                        'column_name' => 'name',
                                        'column_user' => 'login',
                                        'column_password' => 'password',
                                        'column_tfa_secret' => 'tfa_secret' ),
            
            // a configura��o e o uso de menu � opcional no sistema
            self::CONFIG_MENU => array( 'model' => '' ) , 
            
            // essa configura��o e o uso de perfis � opcional no sistema
            self::CONFIG_PROFILE => array( 'model' => '',
                                           'section_permissions' => 'permissions' ) 
    );
    
    /**
     * Tipo de log para Erro
     * @var int
     */
    const FENIX_LOG_ERROR = 1;
    /**
     * Tipo de log para Warning
     * @var int
     */
    const FENIX_LOG_WARNING = 2;
    /**
     * Tipo de log para Notice
     * @var int
     */
    const FENIX_LOG_NOTICE = 3;
    /**
     * Tipo de log para Debug
     * @var int
     */
    const FENIX_LOG_DEBUG = 4;
    
    /**
     * Constante do cabe�alho HTTP padr�o para log do sistema
     */
    const HEADER_FENIX_LOG = 'Fenix-Log';
    
    /**
     * Constante do cabe�alho HTTP padr�o para erros do PHP
     */
    const HEADER_FENIX_ERROR = 'Fenix-Error';
    
    /**
     * Constante de cabe�alho HTTP padr�o para exce��es da aplica��o
     */
    const HEADER_FENIX_EXCEPTION = 'Fenix-Exception';
    
    public static $cache = null;
    
    public static $_cacheDisable = false;
    
    /**
     * Cria/reutilizao objeto de cache do sistema
     * @return Zend_Cache_Core
     */
    public static function getCache(){
        
        if(self::$cache == null && self::$_cacheDisable === false){
            require_once 'Zend/Cache.php';
            
            $frontendOptions = array('lifetime' => 7200, 'automatic_serialization' => true);
            
            if(extension_loaded("memcache") == true){
                $backendOptions = array();
                self::$cache = Zend_Cache::factory('Core', 'Memcached', $frontendOptions, $backendOptions);
                
                // Faz um teste para verificar se o memcached est� funcionando
                if( self::$cache->save('data', '__teste__') !== true ){
                    self::$cache = null;
                }
                
            }
            
            if( self::$cache === null ){
                $backendOptions = array('cache_dir' => 'tmp/cache/');
                self::$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
            }
            
        }
        
        if(self::$_cacheDisable === true){
            self::$cache = null;
        }
        
        return self::$cache;
    }
    
    /**
     * Desativa o cache do sistema
     */
    public static function cacheDisable(){
        self::$_cacheDisable = true;
    }
    
    /**
     * Ativa o cache do sistema
     */
    public static function cacheEnable(){
        self::$_cacheDisable = false;
    }    
    
    /**
     * Obt�m o status atual do cache do sistema
     */
    public static function cacheIsEnabled(){
        return self::$_cacheDisable === false ? true : false;
    }
    
    /**
     * Limpa o cache atual do sistema
     */
    public static function cacheClean(){
        if( self::cacheIsEnabled() ){
            Util::getCache()->clean();
            exec("echo flush_all | nc localhost 11211");
        }
        Model::cacheClean();
        Table::cacheClean();
    }
    
    /**
     * Prepara uma string para ser utilizada como tag
     * @param String $str
     */
    public static function getCacheTag($str){
        if(self::$_cacheTagPrefix == null){
            self::$_cacheTagPrefix = md5(json_encode(Util::getConfig(null, true)));
        }
        $str = self::$_cacheTagPrefix . "_" . $str;
        return md5(preg_replace("@([^a-zA-Z0-9_]+)@", "_", $str));
    }
    
    /**
     * Codifica uma vari�vel php em utf8 de forma recursiva, se necess�rio para arrays
     *
     * @param String $mixed
     * @param String $mixed Codificado em UTF8
     */
    public static function utf8_encode($mixed){
        if(!is_array($mixed)){
            if(is_bool($mixed)){
                return $mixed;
            }
            return utf8_encode($mixed);
        } else {
            foreach($mixed as $key => $value){
                $newKey = is_string($key) ? self::utf8_encode($key) : $key;
                $value = self::utf8_encode($value);
                if($newKey != $key){
                    unset($mixed[$key]);
                }
                $mixed[$newKey] = $value;
            }
            return $mixed;
        }
    }
    
    /**
     * Codifica uma vari�vel php em utf8 de forma recursiva, se necess�rio para arrays
     * 
     * @param String $mixed
     * @param String $mixed Codificado em UTF8
     */
    public static function utf8_decode($mixed){
        if(!is_array($mixed)){
            return utf8_decode($mixed);
        } else {
            foreach($mixed as $key => $value){
                $newKey = is_string($key) ? self::utf8_decode($key) : $key;
                $value = self::utf8_decode($value);
                if($newKey != $key){
                    unset($mixed[$key]);
                }
                $mixed[$newKey] = $value;
            }
            return $mixed;
        }
    }
    
    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    public static function json_indent($json) {
    
        $result      = '';
        $pos         = 0;
        $strLen      = strlen($json);
        $indentStr   = '  ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;
    
        for ($i=0; $i<=$strLen; $i++) {
    
            // Grab the next character in the string.
            $char = substr($json, $i, 1);
    
            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
    
                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if(($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }
    
            // Add the character to the result string.
            $result .= $char;
    
            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }
    
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
    
            $prevChar = $char;
        }
    
        return $result;
    }
    
    /**
     * Retira os acentos e caracteres especiais de uma string
     * 
     * @param String $string
     * 
     * @return String
     */
    public static function strip_accents($string){
        return strtr($string,
                     '��������������������������������������������������',
                     'aaaaaaaAAAAAAeeeeeEEEiiiiiIIIIIooooOOOOOuuuuUUUUCc');
        
    }
    
    /**
     * Normaliza uma string qualquer para usar em ids ou name
     * 
     * @param String $string
     * 
     * @return String
     */
    public static function normalize($string){
        $string = strtolower( Util::strip_accents( $string ) );
        $string = preg_replace("/\s/", "_", $string);
        return $string;
    }
    
    /**
     * Simplifica uma string para us�-la como path por exemplo.
     * 
     * @param String $string
     * 
     * @return String
     */
    public static function simplify_string($string){
        return strtolower(preg_replace("@([^a-zA-Z0-9]+)@", '_', self::strip_accents($string)));
        
    }
    
    /**
     * Retorna se a conex�o atual est� utilizando HTTPS
     * 
     * @return boolean
     */
    public static function getHttps(){
        $ret = false;
        if(isset($_SERVER['HTTPS']) && stripos($_SERVER['HTTPS'], 'on') !== false){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Define a URL base que o sisteme est� rodando para ser poss�vel no template
     * criar links fixos e evitar problemas com caminhos relativos
     *
     * @return String
     */
    public static function getBaseUrl($httpsForce = false){
    
        $path = dirname($_SERVER['SCRIPT_NAME']) . "/";
        $path = str_replace("//", "/", $path);
    
        $protocol = "http://";
    
        if(self::getHttps() === true || $httpsForce === true){
            $protocol = "https://";
        }
    
        $hostname = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "";
    
        return $protocol . $hostname . $path;
    }
    
    /**
     * Retorna se a vers�o � de desenvolvimento (true) ou produ��o (false)
     * para ser usado em otimiza��es e n�vel de relato de erros ao usu�rio
     */
    public static function isDev(){
        $config = Util::getConfig(Util::CONFIG_GENERAL);
        if(isset($config->dev) && $config->dev === true){
            return true;
        }
        return false;
    }
    
    /**
     * Retorna se o profiler geral do sistema est� ativo (XHPROF)
     */
    public static function isProfilerActive(){
        $config = Util::getConfig(Util::CONFIG_GENERAL);
        if(isset($config->profiler) && $config->profiler === true){
            return true;
        }
        return false;
    }
    
    /**
     * Retorna se a execu��o � pela linha de comando (true) ou n�o (false)
     */
    public static function isCli(){
        $ret = false;
        if(isset($_SERVER['argv']) && count($_SERVER['argv']) > 0){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Retorna se a execu��o est� sendo feita via XHR
     */
    public static function isXhr(){
        $ret = false;
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Log de erros gen�rico para ser usado em qualquer parte da aplica��o
     * 
     * @param String $msg
     * @param int $type (constantes de Util::FENIX_LOG_*)
     */
    public static function log($msg, $type = self::FENIX_LOG_DEBUG){
        
        $item = array();
        $item[] = date('H:i:s');
        $item[] = $msg;
        
        self::_fwritelog("log/log-fenix-" . date('Y-m-d') . ".log", implode(";", $item) . "\n");
        
        if( Util::isDev() != true){
            return;
        }
        
        header(self::HEADER_FENIX_LOG. ': '.utf8_encode($msg), false);
    }
    
    /**
     * Escreve uma linha de log num arquivo 
     * @param String $filename
     * @param String $line
     */
    protected static function _fwritelog($filename, $content){
        
        // Se n�o � caminho absoluto
        if( stripos($filename, "/") !== 0 ){
            $filename = dirname( $_SERVER['SCRIPT_FILENAME'] ) . "/".$filename;
        }
        
        // S� tenta abrir/criar arquivo caso seja poss�vel
        if((is_file($filename) && is_writable($filename))
        || (!is_file($filename) && is_writable(dirname($filename))) ){
            
            $fp = fopen($filename, "a+");
            
            if($fp){
                fwrite($fp, $content);
                fclose($fp);
            }
            
        }
        
    }
    
    /**
     * Error handler padr�o que armazena as mensagens de erro do PHP no header do HTTP 
     * 
     * @param int $errno
     * @param String $errstr
     * @param String $errfile
     * @param String $errline
     * @param String[][] $errcontext
     */
    public static function error_handler( $errno, $errstr, $errfile, $errline, $errcontext ){
                
        $item = array();
        $item[] = date('H:i:s');
        $item[] = $errno;
        $item[] = $errfile;
        $item[] = $errline;
        $item[] = $errstr;
        
        self::_fwritelog("log/log-php-" . date('Y-m-d') . ".log", preg_replace("([\n]+)", " ", implode(";", $item)) . "\n");
        
        if( Util::isDev() != true){
            return;
        }
        
        $header = self::HEADER_FENIX_ERROR . ': ' . self::error_handler_name($errno) . " - " . $errfile . ":" . $errline . ' => ' . preg_replace("([\n]+)", " ", $errstr);
        
        $alreadySent = false || headers_sent();
        
        foreach(headers_list() as $headerCurrent){
            if(stripos($headerCurrent, $header) !== false){
                $alreadySent = true;
            }
        }
        
        if($alreadySent === false){
            header($header, false);
        }
        
    }
    
    /**
     * Faz o tratamento de erros fatais em PHP (encerram o script)
     */
    public static function error_handler_shutdown(){
        $error = error_get_last();
        
        // Salva informa��es de profiling caso ele esteja ativo
        if( Util::isProfilerActive() && extension_loaded("xhprof") ){
            $xhprof_data = xhprof_disable();
            
            $XHPROF_ROOT = "/var/www/xhprof/";
            
            include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
            include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
            
            $xhprof_runs = new XHProfRuns_Default();
            $run_id = $xhprof_runs->save_run($xhprof_data, preg_replace("/([^A-Za-z]+)/", "_", $_SERVER['REDIRECT_URL']));
        }
        
        if($error != null){
            self::error_handler($error['type'], $error['message'], $error['file'], $error['line'], array());
            
            $message = self::error_handler_name($error['type']) . " - " . $error['file'] . ":" . $error['line'] . ' => ' . preg_replace("([\n]+)", " ", $error['message']);
            
            Util::exception( new Exception($message) );
        }
    }
    
    /**
     * Lista de nomes dos erros em PHP
     * 
     * @param int $errno
     * 
     * @return String
     */
    public static function error_handler_name($errno){
        switch($errno) {
            
            case E_ERROR: // 1 //
                return 'E_ERROR';
            case E_WARNING: // 2 //
                return 'E_WARNING';
            case E_PARSE: // 4 //
                return 'E_PARSE';
            case E_NOTICE: // 8 //
                return 'E_NOTICE';
            case E_CORE_ERROR: // 16 //
                return 'E_CORE_ERROR';
            case E_CORE_WARNING: // 32 //
                return 'E_CORE_WARNING';
            case E_CORE_ERROR: // 64 //
                return 'E_COMPILE_ERROR';
            case E_CORE_WARNING: // 128 //
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR: // 256 //
                return 'E_USER_ERROR';
            case E_USER_WARNING: // 512 //
                return 'E_USER_WARNING';
            case E_USER_NOTICE: // 1024 //
                return 'E_USER_NOTICE';
            case E_STRICT: // 2048 //
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR: // 4096 //
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED: // 8192 //
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED: // 16384 //
                return 'E_USER_DEPRECATED';
        }
    }
    
    /**
     * Salva o log de uma exec��o no sistema
     * @param Exception $e
     */
    public static function exceptionLog(Exception $e){
        
        $php = "PHP Errors: \n\n";
        foreach(headers_list() as $header){
            if(stripos($header, "Fenix-Error:") !== false){
                $php .= "# {$header} \n";
            }
        }
        
        $item = array();
        $item['message'] = $e->getMessage();
        $item['trace'] = $e->getTraceAsString();
        $item['php'] = $php;
        $item['post'] = $_POST;
        $item['get'] = $_GET;
        $item['cookie'] = $_COOKIE;
        $item['server'] = $_SERVER;
        
        self::_fwritelog("log/exception-" . date('Y-m-d-H-i-s') . ".json", Util::json_indent(json_encode(Util::utf8_encode($item))));
    }
    
    /**
     * Tratamento padr�o de uma exce��o no sistema (salva log e encerra a execu��o)
     * @param Exception $e
     */
    public static function exception(Exception $e){
        
        $errorCode = 500;
        
        if(Util::isXhr()){
            
            if($e instanceof Fenix_Exception){
                header($_SERVER['SERVER_PROTOCOL'].' 412 Precondition Failed', true, 412);
                $errorCode = 412;
            } else {
                header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
            }
                        
        } else {
            
            if(stripos($e->getMessage(), "failed opening required") !== false
            || stripos($e->getMessage(), "not found") !== false){
                header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
                $errorCode = 404;
            } else {
                header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
            }
            
        }
        
        
        // Exece��es do tipo Fenix_Exception devem ser enviadas � interface
        if($e instanceof Fenix_Exception){
            header('Fenix-Exception: ' . str_replace("\"", "'", $e->getMessage()));
            die($e->getMessage());
        }
        
        // Outras exce��es
        header('Fenix-Exception: Internal Server Error');
        
        // Salva um registro da exce��o no log do sistema
        Util::exceptionLog($e);
        
        // S� se for vers�o de desenvolvimento mostra stack trace do erro
        if(self::$configJson === null || Util::isDev() == true){
            print "<pre>";
            print_r("<a href='./?__clearSession'>Clear Session</a>\n\n");
            print_r("Exception: ".$e->getMessage()."\n\n");
            print_r($e->getTraceAsString());
            print_r("\n\nPHP Errors: \n\n");
            foreach(headers_list() as $header){
                if(stripos($header, "Fenix-Error:") !== false){
                    print_r("# {$header} \n");
                }
            }
            print "</pre>";
        }
        // Se n�o for vers�o de desenvolvimento verifica p�ginas padr�o de acordo
        // com o c�digo de erro nas configura��es do sistema
        else {
            $config = Util::getConfig(Util::CONFIG_GENERAL);
            $action = isset($config->{$errorCode."Action"}) ? $config->{$errorCode."Action"} : false;
            if( $action ){
                $urlRedir = Util::getBaseUrl().$action;
                if( stripos($urlRedir, $_SERVER['REQUEST_URI']) !== false ){
                    echo 'Internal Server Error';
                    die();
                }
                header("Location: ".$urlRedir);
                die();
            }
            else {
                echo 'Internal Server Error';
            }
        }

    }
    
    /**
     * Faz a inicializa��o da configura��o do sistema baseado (caso informado)
     * no array passado como par�metro
     * 
     * @param String[] $configArray Array opcional com as configura��es do sistema
     * @param boolean $overrideCurrent Faz com que as configura��es atuais sejam eliminadas (mudan�a de contexto de banco de dados, por exemplo)
     * 
     */
    public static function loadConfig($configArray = null, $overrideCurrent = false){
        
        // S� faz o carregamento caso as vari�veis de configura��o n�o estejam definidas
        // ou o argumento $overrideCurrent seja definido como true
        if($overrideCurrent === true || self::$configArray === null || self::$configJson === null){
            
            // Caso tenha sido passado o $configArray faz o carregamento baseado nele
            if($configArray !== null){
                
                self::$configArray = $configArray;
                self::$configJson = json_decode(json_encode($configArray));
                
            }
            // Caso contr�rio faz o carregamento padr�o pelo arquivo de configura��o do sistema
            else {
                 
                if(!is_file(self::_CONFIG_FILE)){
                    throw new Exception("Util::getConfig : N�o � poss�vel localizar o arquivo de configura��es\n\nem: " . dirname($_SERVER['SCRIPT_FILENAME'])."/".self::_CONFIG_FILE . "\n\nVerifique se ele existe e fa�a a configura��o baseada no exemplo nesse mesmo diret�rio.");
                }
            
                self::$configArray = json_decode(file_get_contents(self::_CONFIG_FILE), true);
                self::$configJson = json_decode(file_get_contents(self::_CONFIG_FILE));
                
            }
            
        }
        
        // Faz uma verifica��o final para ter certeza que as configura��es estejam numa situa��o v�lida
        if(!is_array(self::$configArray) || !(self::$configJson instanceof stdClass) ){
            if($configArray === null){
                throw new Exception("Util::getConfig : Conte�do inv�lido do arquivo " . dirname($_SERVER['SCRIPT_FILENAME'])."/".self::_CONFIG_FILE . " - verifique a forma��o do JSON.");
            } else {
                throw new Exception("Util::getConfig : Conte�do inv�lido das configura��es do sistema, verifique o configArray passado como par�metro.");
            }
        }
        
        // Caso $overrideCurrent esteja definido como true faz com que a conex�o ao banco de dados seja eliminada 
        if($overrideCurrent === true){
            if(Table::getDefaultAdapter()){
                Table::getDefaultAdapter()->closeConnection();
                Table::setDefaultAdapter(null);
            }
        }
    }
    
    /**
     * Obt�m as configura��es de execu��o do sistema
     * 
     * @return String[]
     */
    public static function getConfig($section = null, $asArray = false){
        
        // Faz o carregamento das configura��es do sistema baseado no arquivo de configura��o (padr�o)
        self::loadConfig();
        
        $ret = self::$configJson;
        
        if($asArray == true){
            $ret = self::$configArray;
        }
        
        if($section !== null){
            
            if(is_array($ret) && isset($ret[$section])){
                $ret = $ret[$section];
            } else if(is_object($ret) && isset($ret->$section)){
                $ret = $ret->$section;
            } else {
                if($asArray){
                    $ret = array();
                } else {
                    $ret = new stdClass();
                }
            }
            
            if(isset(self::$configDefaults[$section])){
                
                foreach(self::$configDefaults[$section] as $name => $defaultValue){
                    $isset = true;
                    if(is_array($ret)){
                        if(!isset($ret[$section])){
                            $isset = false;
                            $ret[$name] = $defaultValue;
                        }
                    } else if(is_object($ret)){
                        if(!isset($ret->$name)){
                            $isset = false;
                            $ret->$name = $defaultValue;
                        }
                    }
                    
                    // Quando o valor padr�o � _CONFIG_MUST_BE_SET obriga que esteja definida
                    if($defaultValue === self::_CONFIG_MUST_BE_SET && $isset === false){
                        throw new Exception("Util::getConfig : Valor da configura��o '{$name}' da se��o '{$section}' precisa ser definido.");
                    }
                }
            }
            
            if( ($asArray && count($ret) == 0) || (!$asArray && serialize($ret) == serialize(new stdClass())) ) {
                Util::log("Util::getConfig : Se��o {$section} n�o localizada no arquivo de configura��es.", self::FENIX_LOG_WARNING);
            }
        }
        
        return $ret;
    }
    
    /**
     * Faz o parser do conte�do JSON ou semelhante ao
     *
     * @param {} field Lista de atributos do sistema
     * @return {} data em formato de objeto
     */
    public static function parseFieldSelectData(ModelField $field){
    
        $data = null;
        
        $tag = md5($field->getData());
        
        if(isset(self::$_cacheParseFieldSelectData[$tag])){
            
            $data = self::$_cacheParseFieldSelectData[$tag];
            
        } else {
            
            $data = json_decode(utf8_encode($field->getData()), true);
            
            if($data == null){
                $data = utf8_encode($field->getData());
                $data = '"' . implode("\",\"", explode(",", $data)) . '"';
                $data = '{' . implode("\":\"", explode(":", $data)) . '}';
                $data = json_decode($data, true);
            }
            
            if($data){
                $data = Util::utf8_decode($data);
            }
            
            self::$_cacheParseFieldSelectData[$tag] = $data;
        }
        
        if(!is_array($data)){
            $data = null;
        }
        
        return $data;
    }
    
    /**
     * Fun��o recursiva para preenchimento da hierarquia de menus
     * 
     * @param mixed[][] $menu
     * @param mixed[] $item
     * @return mixed[][] $menu modificado
     */
    protected static function _getMenuSubmenu($menu, $item){
        
        foreach($menu as $key => $record){
            if($record['id'] == $item['id_parent']){
                if(!isset($menu[$key]['_submenu']) || !is_array($menu[$key]['_submenu'])){
                    $menu[$key]['_submenu'] = array();
                }
                $menu[$key]['_submenu'][] = $item;
            } elseif ( isset($record['_submenu']) && is_array($record['_submenu'])) {
                $menu[$key]['_submenu'] = self::_getMenuSubmenu($record['_submenu'], $item);
            }
            
        }
        
        return $menu;
    }
    
    /**
     * Cria um select para ser utilizado nas checagem de permiss�o de pelo menos visualiza��o
     *
     * @param String $column
     * @param int $idProfile
     * @return Zend_Db_Select
     */
    protected static function _getMenuProfileSelectPermissions($column, $idProfile){
    
        $modelProfile = Util::getModelProfile();
    
        // Cria um select para listar as permiss�es do usu�rio logado
        $selectPermissions = Model::factory( $modelProfile->getName(), Util::getConfig(Util::CONFIG_PROFILE)->section_permissions )->prepareSelect(array($column));
    
        $selectPermissions->where("{$column} IS NOT NULL");
        
        // Limita��o pelo perfil do usu�rio
        list($profileSchemaName, $profileTableName) = explode(".", $modelProfile->getTableName());
        $selectPermissions->where('id_'.$profileTableName.' = ?', $idProfile);
    
        // Permiss�o de pelo menos visualizar
        $selectPermissions->where('value & 2 > 0');
    
        return $selectPermissions;
    }
    
    /**
     * Faz as limita��es � consulta do menu de acordo com perfil do usu�rio
     * 
     * @param Zend_Db_Select $select
     */
    protected static function _getMenuProfile(Zend_Db_Select $select){
        
        $modelProfile = Util::getModelProfile();
        
        // Se houver modelo de perfis no sistema faz a limita��o dos
        // itens de menu de acordo com o perfil do usu�rio, caso haja
        // usu�rio logado no sistema
        if($modelProfile && Zend_Auth::getInstance()->hasIdentity()){

            // Usu�rio logado
            $user = Zend_Auth::getInstance()->getIdentity();
            
            // Perfil do usu�rio logado
            $idProfile = $user->{ Util::getConfig(Util::CONFIG_AUTH)->column_profile };
            
            // S� faz valida��o dos itens de menu para perfis de ID != do Administrador geral
            if($idProfile != self::_ID_PERFIL_ADMINISTRADOR){
                
                $menuTableName = Util::getModelMenu()->getTableName();
                
                // Caso o id_model na tabela do menu esteja definido e n�o haja
                // um registro de permiss�es do sistema com pelo menos permiss�o
                // de visualizar par ao perfil do usu�rio atual n�o exibe no menu
                $selectPermissions = self::_getMenuProfileSelectPermissions('id_model', $idProfile);
                $select->where("CASE WHEN {$menuTableName}.id_model IS NOT NULL THEN {$menuTableName}.id_model IN ({$selectPermissions->__toString()}) ELSE 1=1 END");
                
                // Caso n�o haja um id_model definodo na tabela do menu faz quest�o
                // que exista uma permiss�o com mesmo nome do item de menu para que
                // ele seja exibido ao usu�rio (quando o item de menu tem um link e
                // � diferente de #)
                $selectPermissions = self::_getMenuProfileSelectPermissions('name', $idProfile);
                $select->where("CASE WHEN {$menuTableName}.id_model IS NULL THEN (COALESCE({$menuTableName}.url, '#') = '#' OR {$menuTableName}.name IN ({$selectPermissions->__toString()})) ELSE 1=1 END");
            }
            
        }
        
    }
    
    /**
     * Obt�m os itens de menu para impress�o
     */
    public static function getMenu($showAll = false){
        
        $menu = array();
        
        $model = Util::getModelMenu();
        
        if($model !== null){
            
            $select = $model->prepareSelect()->order(array('COALESCE(menu.id_parent, -1) ASC', 'menu.position ASC', 'menu.name ASC'));
            
            // Vers�es que n�o s�o de desenvolvimento s� mostram posi��es maiores que 0
            if(!Util::isDev() && $showAll === false){
                $select->where('menu.position >= 0');
            }
            
            // Limita��es de permiss�o do sistema
            self::_getMenuProfile($select);
            
            // Executa a consulta
            $menu = $select->query()->fetchAll();
            
            // Cria a hierarquia de menus
            foreach($menu as $key => $item){
                if($item['id_parent']){
                   unset($menu[$key]);
                   $menu = self::_getMenuSubmenu($menu, $item);
                }
            }
            
        }
        
        // Remove itens de menu sem URL nem submenus
        if($showAll === false){
            $removeEmpty = function($menu) use (&$removeEmpty){
                foreach($menu as $key => $record){
                    if(isset($menu[$key]['_submenu']) && is_array($menu[$key]['_submenu'])){
                        $menu[$key]['_submenu'] = $removeEmpty($menu[$key]['_submenu']);
                    }
                    if((strlen(trim($menu[$key]['url'])) === 0 || trim($menu[$key]['url']) == '#')
                    && (!isset($menu[$key]['_submenu']) || count($menu[$key]['_submenu']) == 0)){
                        unset($menu[$key]);
                    }
                }
                return array_values($menu);
            };
            
            $menu = $removeEmpty($menu);
        }
        
        return $menu;
    }
    
    /**
     * Converts an integer into the alphabet base (A-Z).
     *
     * @author Theriault
     * 
     * @param int $n This is the number to convert.
     *
     * @return string The converted number.
     */
    public static function num2alpha($n) {
        $r = '';
        for ($i = 1; $n >= 0 && $i < 10; $i++) {
            $r = chr(0x41 + ($n % pow(26, $i) / pow(26, $i - 1))) . $r;
            $n -= pow(26, $i);
        }
        return strtolower($r);
    }
    
    /**
     * Converts an alphabetic string into an integer.
     *
     * @author Theriault
     * 
     * @param int $n This is the number to convert.
     *
     * @return string The converted number.
     */
    public static function alpha2num($a) {
        $r = 0;
        $l = strlen($a);
        for ($i = 0; $i < $l; $i++) {
            $r += pow(26, $i) * (ord($a[$l - $i - 1]) - 0x40);
        }
        return $r - 1;
    }
    
    /**
     * Validates a configured model name and throws a exception in case of an error
     * 
     * @return Model
     */
    protected static function _validateConfigModel($config, $configName = "model"){

        $ret = null;
        
        $configObj = Util::getConfig($config);
        
        if($configObj && isset($configObj->{$configName}) && !empty($configObj->{$configName})){
            try {
                $modelName = $configObj->{$configName};
                $ret = Model::factory( $modelName );
            } catch (Exception $e){
                Util::exceptionLog($e);
                throw new Exception(__CLASS__ . " - a configura��o '{$config}' no item '{$configName}' = '{$modelName}' n�o define um model do sistema, carregue o modelo correspondente ou ajuste a configura��o.");
            }
        } else {
            Util::exceptionLog($e);
            throw new Exception(__CLASS__ . " - a configura��o '{$config}' precisa ter o item '{$configName}' definido para utiliza��o no sistema.");
        }
        
        return $ret;
    }
    
    /**
     * Modelo padr�o de model do sistema
     * 
     * @return Model
     */
    public static function getModelModel(){
        return self::_validateConfigModel(  Util::CONFIG_GENERAL , "modelTable" );
    }
    
    /**
     * Modelo padr�o de arquivos do sistema
     * 
     * @return Fenix_File
     */
    public static function getModelFile(){
        return self::_validateConfigModel(  Util::CONFIG_FILE , "model" );
    }
    
    /**
     * Modelo padr�o de usu�rios do sistema
     * 
     * @return Fenix_User
     */
    public static function getModelUser(){
        return self::_validateConfigModel(  Util::CONFIG_AUTH , "model" );
    }
    
    /**
     * Modelo padr�o de menu do sistema
     * 
     * @return Fenix_Menu
     */
    public static function getModelMenu(){
        
        $ret = null;
        
        $config = Util::getConfig( Util::CONFIG_MENU );
        
        if($config && isset($config->model) && !empty($config->model)){
            $ret = Model::factory( $config->model );
        }
        
        return $ret;
    }
    
    /**
     * Modelo padr�o de perfil do sistema
     * 
     * @return Fenix_Profile
     */
    public static function getModelProfile(){
        
        $ret = null;
        
        $config = Util::getConfig( Util::CONFIG_PROFILE );
        
        if($config && isset($config->model) && !empty($config->model)){
            $ret = Model::factory( $config->model );
        }
        
        return $ret;
    }
    
    /**
     * Modelo padr�o de permiss�es do perfil do sistema
     * 
     * @return Fenix_Profile
     */
    public static function getModelProfilePermission(){
        
        $ret = null;
        
        $config = Util::getConfig( Util::CONFIG_PROFILE );
        
        if($config && isset($config->model)){
            $ret = Model::factory( $config->model, $config->section_permissions );
        }
        
        return $ret;
    }
    
    /**
     * Retorna o nome completo da classe do controller
     * 
     * @return String
     */
    public static function getControllerClassName(Model $model){
        list($module, $name) = explode(".", $model->getName());
        
        $className = str_replace("_", " ", $name);
        $className = str_replace(" ", "", ucwords($className));
        
        return $className."Controller";
    }
    
    /**
     * Retorna o nome completo do controller para uso em URL
     * 
     * @param String Filename (nome do arquivo)
     * @return String
     */
    public static function getControllerNameFromFile($filename){
        
        $name = str_replace(".php", "", $filename);
        $name = explode("/", $name);
        $name = end($name);
        $name = str_replace("Controller", "", $name);
        $name = trim(preg_replace("/([A-Z]{1})/", " $1", $name));
        $name = str_replace(" ", "-", strtolower($name));
        
        return $name;
    }
    
    /**
     * Retorna o nome completo do controller padrao (para uso em URL)
     * 
     * @param Model $model Model do sistema
     * @return String
     */
    public static function getControllerName(Model $model){
        list($module, $name) = explode(".", $model->getName());
        
        $controllerName = str_replace("_", "-", $name);
        
        return $controllerName;
    }
    
    /**
     * Retorna o nome completo do controller (para uso em URL)
     * 
     * @return String
     */
    public static function getControllerClassFilename(Model $model){
        
        list($module, $name) = explode(".", $model->getName());
        
        $className = self::getControllerClassName($model);
        
        return "app/modules/{$module}/controller/{$className}.php";
    }
    
    /**
     * Retorn o nome do m�s
     * @param int $month
     * @return String
     */
    public static function getMonthName($month){
        $ret = null;
        switch ($month){
            case 1: $ret = "Janeiro"; break;
            case 2: $ret = "Fevereiro"; break;
            case 3: $ret = "Mar�o"; break;
            case 4: $ret = "Abril"; break;
            case 5: $ret = "Maio"; break;
            case 6: $ret = "Junho"; break;
            case 7: $ret = "Julho"; break;
            case 8: $ret = "Agosto"; break;
            case 9: $ret = "Setembro"; break;
            case 10: $ret = "Outubro"; break;
            case 11: $ret = "Novembro"; break;
            case 12: $ret = "Dezembro"; break;
        }
        if($ret === null){
            throw new Exception(__METHOD__ . ' - argument is not a valid month [1-12].');
        }
        return $ret;
    }
    
    /**
     * Valida uma chave RSA (p�blica ou privada)
     * @param String $filenameOrString Conte�do da chave (PEM) ou caminho do arquivo que o cont�m
     * @throws Exception
     * @return String PEM da chave
     */
    public static function rsaCheckKey($filenameOrString){
    
        $ret = $filenameOrString;
    
        if( is_file($filenameOrString) ){
            $ret = file_get_contents( $filenameOrString );
        }
    
        if( stripos($ret, "---BEGIN") === false ){
            throw new Exception(__METHOD__ . ' - o conte�do '.var_export($filenameOrString, true) . ' n�o parece ser uma chave.');
        }
    
        return $ret;
    }
    
    /**
     * Criptografa um conte�do com chave p�blica
     * @param String $data Conte�do a ser criptografado
     * @param String $publicKey Chave p�blica (conte�do em PEM ou nome de arquivo)
     * @return String Conte�do criptografado
     */
    public static function rsaEncrypt($data, $publicKey){
    
        $encrypted = null;
    
        // Criptografa o conte�do da requisi��o
        openssl_public_encrypt($data, $encrypted, self::rsaCheckKey($publicKey) );
    
        return $encrypted;
    }
    
    /**
     * Decriptografa um conte�do com chave privada
     * @param String $data Conte�do a ser decriptografado
     * @param String $privateKey Chave privada (conte�do em PEM ou nome de arquivo)
     * @return String Conte�do criptografado
     */
    public static function rsaDecrypt($data, $privateKey){
    
        $decrypted = null;
    
        // Criptografa o conte�do da requisi��o
        openssl_private_decrypt($data, $decrypted, self::rsaCheckKey($privateKey));
    
        return $decrypted;
    }
    
    /**
     * Assina determinado conte�do com a chave p�blica
     * @param String $data Dados a serem assinados
     * @param String $privateKey Chave privada (conte�do ou nome de arquivo)
     * @throws Exception
     * @return String Assinatura realizada
     */
    public static function rsaSign($data, $privateKey){
    
        $privateKey = self::rsaCheckKey($privateKey);
    
        // Assina os dados
        $privateKey = openssl_pkey_get_private( $privateKey );
        $privateKeyDetails = openssl_pkey_get_details($privateKey);
    
        $signature = null;
    
        openssl_sign($data, $signature, $privateKey);
    
        // Faz a verifica��o da assinatura
        if( self::rsaVerify($data, $signature, $privateKeyDetails['key']) ){
            return $signature;
        }
    
        throw new Exception( __METHOD__ . " - imposs�vel assinar conte�do.");
    }
    
    /**
     * Verifica com a chave p�blica uma assinatura realizada com a chave privada
     * @param String $data Dados que foram assinados
     * @param String $signature Conte�do da assinatura
     * @param String $key Chave privada ou p�blica (conte�do ou nome de arquivo)
     * @return boolean
     */
    public static function rsaVerify($data, $signature, $key){
    
        $publicKey = self::rsaCheckKey($key);
        
        if(stripos($publicKey, "PRIVATE KEY") !== false){
            
            $privateKey = openssl_pkey_get_private($publicKey);
            $privateKeyDetails = openssl_pkey_get_details($privateKey);
            
            $publicKey = $privateKeyDetails['key'];
            
        }
        
        if( openssl_verify($data, $signature, $publicKey ) ){
            return true;
        }
    
        return false;
    }
    
    /**
     * Cria um documento XML a partir de uma estrutura de array
     * @param mixed[] $array Estrutura a ser criada
     * @param String $nodeName NodeName inicial do XML
     * @param String $namespaceURI URI do namespace sendo utilizado
     * @throws Exception
     * @return string XML Criado
     */
    public static function array2xml($array, $nodeName, $namespaceURI = null) {
    
        $array = Util::utf8_encode($array);
    
        $dom = new DOMDocument('1.0', 'UTF-8');
    
        $root = $dom->createElementNS($namespaceURI, $nodeName);
    
        $dom->appendChild($root);
    
        $array2xml = function ($node, $array) use ($dom, &$array2xml, $namespaceURI) {
            foreach($array as $key => $value){
                if ( is_array($value) ) {
                    $n = $dom->createElementNS($namespaceURI, $key);
                    $node->appendChild($n);
                    $array2xml($n, $value);
                } else  {
                    $node->appendChild( $dom->createElementNS($namespaceURI, $key, $value) );
                }
            }
        };
    
        $array2xml($root, $array);
    
        return $dom;
    }
    
    /**
     * Converte um XMl para um array
     *
     * @param string|DOMDocument|DOMNode $root
     * @return mixed[][]
     */
    public static function xml2array($root) {
    
        if(is_string($root)){
            $dom = new DOMDocument("1.0", "UTF-8");
            $dom->loadXML($root);
            $root = $dom;
        }
    
        $xml2array = function ($root) use ($dom, &$xml2array) {
            $result = array();
    
            if ($root->hasAttributes()) {
                $attrs = $root->attributes;
                foreach ($attrs as $attr) {
                    $result['@attributes'][$attr->name] = $attr->value;
                }
            }
    
            if ($root->hasChildNodes()) {
                $children = $root->childNodes;
                if ($children->length == 1) {
                    $child = $children->item(0);
                    if ($child->nodeType == XML_TEXT_NODE) {
                        $result['_value'] = $child->nodeValue;
                        return count($result) == 1
                        ? $result['_value']
                        : $result;
                    }
                }
                $groups = array();
                foreach ($children as $child) {
                    if (!isset($result[$child->nodeName])) {
                        $result[$child->nodeName] = $xml2array($child);
                    } else {
                        if (!isset($groups[$child->nodeName])) {
                            $result[$child->nodeName] = array($result[$child->nodeName]);
                            $groups[$child->nodeName] = 1;
                        }
                        $result[$child->nodeName][] = $xml2array($child);
                    }
                }
            }
            return $result;
        };
    
        $ret = Util::utf8_decode($xml2array($root));
    
        return $ret;
    }
    
}
























