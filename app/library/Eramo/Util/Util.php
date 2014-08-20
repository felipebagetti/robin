<?php

/**
 * Classe com funções padrão do sistema
 *
 * @copyright Eramo Software
 */
class Util {
    
    /**
     * Caminho do arquivo padrão de configuração
     * @var String
     */
    const _CONFIG_FILE = "app/config/config.json";
    
    /**
     * Tipo de configuração geral do sistema
     * @var String
     */
    const CONFIG_GENERAL = 'general';
    
    /**
     * Tipo de configuração do banco de dados
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
     * Tipo de configuração de autenticação
     * @var String
     */
    const CONFIG_AUTH = 'auth';
    
    /**
     * Tipo de configuração de perfis
     * @var String
     */
    const CONFIG_PROFILE = 'profile';
    
    /**
     * Tipo de configuração de menu
     * @var String
     */
    const CONFIG_MENU = 'menu';
    
    /**
     * Tipo de configuração de armazenamento de arquivos
     * @var String
     */
    const CONFIG_FILE = 'file';
    
    /**
     * Constante para configurações que precisam ser definidas
     * @var String
     */
    const _CONFIG_MUST_BE_SET = 'configMustBeSet';
    
    /**
     * ID do perfil do administrador geral (onde não há checagem de permissões)
     * @var int
     */
    const _ID_PERFIL_ADMINISTRADOR = 1; 
    
    /**
     * Cache das configurações carregadas do arquivo
     * @var String[][]
     */
    protected static $configJson = null;
    protected static $configArray = null;
    
    protected static $_cacheTagPrefix = null;
    
    protected static $_cacheParseFieldSelectData = array();
    
    /**
     * Configurações padrão do sistema (não precisam estar no arquivo de configuração)
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
            
            self::CONFIG_AUTH => array( 'model' => '', // indica que a configuração é obrigatória
                                        'tfa' => false,
                                        'column_profile' => 'id_profile',
                                        'column_name' => 'name',
                                        'column_user' => 'login',
                                        'column_password' => 'password',
                                        'column_tfa_secret' => 'tfa_secret' ),
            
            // a configuração e o uso de menu é opcional no sistema
            self::CONFIG_MENU => array( 'model' => '' ) , 
            
            // essa configuração e o uso de perfis é opcional no sistema
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
     * Constante do cabeçalho HTTP padrão para log do sistema
     */
    const HEADER_FENIX_LOG = 'Fenix-Log';
    
    /**
     * Constante do cabeçalho HTTP padrão para erros do PHP
     */
    const HEADER_FENIX_ERROR = 'Fenix-Error';
    
    /**
     * Constante de cabeçalho HTTP padrão para exceções da aplicação
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
                
                // Faz um teste para verificar se o memcached está funcionando
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
     * Obtém o status atual do cache do sistema
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
     * Codifica uma variável php em utf8 de forma recursiva, se necessário para arrays
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
     * Codifica uma variável php em utf8 de forma recursiva, se necessário para arrays
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
                     'âãäåáâàÀÁÂÃÄÅèééêëÈÉÊìíîïìÌÍÎÏÌóôõöÒÓÔÕÖùúûüÙÚÛÜÇç',
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
     * Simplifica uma string para usá-la como path por exemplo.
     * 
     * @param String $string
     * 
     * @return String
     */
    public static function simplify_string($string){
        return strtolower(preg_replace("@([^a-zA-Z0-9]+)@", '_', self::strip_accents($string)));
        
    }
    
    /**
     * Retorna se a conexão atual está utilizando HTTPS
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
     * Define a URL base que o sisteme está rodando para ser possível no template
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
     * Retorna se a versão é de desenvolvimento (true) ou produção (false)
     * para ser usado em otimizações e nível de relato de erros ao usuário
     */
    public static function isDev(){
        $config = Util::getConfig(Util::CONFIG_GENERAL);
        if(isset($config->dev) && $config->dev === true){
            return true;
        }
        return false;
    }
    
    /**
     * Retorna se o profiler geral do sistema está ativo (XHPROF)
     */
    public static function isProfilerActive(){
        $config = Util::getConfig(Util::CONFIG_GENERAL);
        if(isset($config->profiler) && $config->profiler === true){
            return true;
        }
        return false;
    }
    
    /**
     * Retorna se a execução é pela linha de comando (true) ou não (false)
     */
    public static function isCli(){
        $ret = false;
        if(isset($_SERVER['argv']) && count($_SERVER['argv']) > 0){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Retorna se a execução está sendo feita via XHR
     */
    public static function isXhr(){
        $ret = false;
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Log de erros genérico para ser usado em qualquer parte da aplicação
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
        
        // Se não é caminho absoluto
        if( stripos($filename, "/") !== 0 ){
            $filename = dirname( $_SERVER['SCRIPT_FILENAME'] ) . "/".$filename;
        }
        
        // Só tenta abrir/criar arquivo caso seja possível
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
     * Error handler padrão que armazena as mensagens de erro do PHP no header do HTTP 
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
        
        // Salva informações de profiling caso ele esteja ativo
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
     * Salva o log de uma execção no sistema
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
     * Tratamento padrão de uma exceção no sistema (salva log e encerra a execução)
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
        
        
        // Execeções do tipo Fenix_Exception devem ser enviadas à interface
        if($e instanceof Fenix_Exception){
            header('Fenix-Exception: ' . str_replace("\"", "'", $e->getMessage()));
            die($e->getMessage());
        }
        
        // Outras exceções
        header('Fenix-Exception: Internal Server Error');
        
        // Salva um registro da exceção no log do sistema
        Util::exceptionLog($e);
        
        // Só se for versão de desenvolvimento mostra stack trace do erro
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
        // Se não for versão de desenvolvimento verifica páginas padrão de acordo
        // com o código de erro nas configurações do sistema
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
     * Faz a inicialização da configuração do sistema baseado (caso informado)
     * no array passado como parâmetro
     * 
     * @param String[] $configArray Array opcional com as configurações do sistema
     * @param boolean $overrideCurrent Faz com que as configurações atuais sejam eliminadas (mudança de contexto de banco de dados, por exemplo)
     * 
     */
    public static function loadConfig($configArray = null, $overrideCurrent = false){
        
        // Só faz o carregamento caso as variáveis de configuração não estejam definidas
        // ou o argumento $overrideCurrent seja definido como true
        if($overrideCurrent === true || self::$configArray === null || self::$configJson === null){
            
            // Caso tenha sido passado o $configArray faz o carregamento baseado nele
            if($configArray !== null){
                
                self::$configArray = $configArray;
                self::$configJson = json_decode(json_encode($configArray));
                
            }
            // Caso contrário faz o carregamento padrão pelo arquivo de configuração do sistema
            else {
                 
                if(!is_file(self::_CONFIG_FILE)){
                    throw new Exception("Util::getConfig : Não é possível localizar o arquivo de configurações\n\nem: " . dirname($_SERVER['SCRIPT_FILENAME'])."/".self::_CONFIG_FILE . "\n\nVerifique se ele existe e faça a configuração baseada no exemplo nesse mesmo diretório.");
                }
            
                self::$configArray = json_decode(file_get_contents(self::_CONFIG_FILE), true);
                self::$configJson = json_decode(file_get_contents(self::_CONFIG_FILE));
                
            }
            
        }
        
        // Faz uma verificação final para ter certeza que as configurações estejam numa situação válida
        if(!is_array(self::$configArray) || !(self::$configJson instanceof stdClass) ){
            if($configArray === null){
                throw new Exception("Util::getConfig : Conteúdo inválido do arquivo " . dirname($_SERVER['SCRIPT_FILENAME'])."/".self::_CONFIG_FILE . " - verifique a formação do JSON.");
            } else {
                throw new Exception("Util::getConfig : Conteúdo inválido das configurações do sistema, verifique o configArray passado como parâmetro.");
            }
        }
        
        // Caso $overrideCurrent esteja definido como true faz com que a conexão ao banco de dados seja eliminada 
        if($overrideCurrent === true){
            if(Table::getDefaultAdapter()){
                Table::getDefaultAdapter()->closeConnection();
                Table::setDefaultAdapter(null);
            }
        }
    }
    
    /**
     * Obtém as configurações de execução do sistema
     * 
     * @return String[]
     */
    public static function getConfig($section = null, $asArray = false){
        
        // Faz o carregamento das configurações do sistema baseado no arquivo de configuração (padrão)
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
                    
                    // Quando o valor padrão é _CONFIG_MUST_BE_SET obriga que esteja definida
                    if($defaultValue === self::_CONFIG_MUST_BE_SET && $isset === false){
                        throw new Exception("Util::getConfig : Valor da configuração '{$name}' da seção '{$section}' precisa ser definido.");
                    }
                }
            }
            
            if( ($asArray && count($ret) == 0) || (!$asArray && serialize($ret) == serialize(new stdClass())) ) {
                Util::log("Util::getConfig : Seção {$section} não localizada no arquivo de configurações.", self::FENIX_LOG_WARNING);
            }
        }
        
        return $ret;
    }
    
    /**
     * Faz o parser do conteúdo JSON ou semelhante ao
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
     * Função recursiva para preenchimento da hierarquia de menus
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
     * Cria um select para ser utilizado nas checagem de permissão de pelo menos visualização
     *
     * @param String $column
     * @param int $idProfile
     * @return Zend_Db_Select
     */
    protected static function _getMenuProfileSelectPermissions($column, $idProfile){
    
        $modelProfile = Util::getModelProfile();
    
        // Cria um select para listar as permissões do usuário logado
        $selectPermissions = Model::factory( $modelProfile->getName(), Util::getConfig(Util::CONFIG_PROFILE)->section_permissions )->prepareSelect(array($column));
    
        $selectPermissions->where("{$column} IS NOT NULL");
        
        // Limitação pelo perfil do usuário
        list($profileSchemaName, $profileTableName) = explode(".", $modelProfile->getTableName());
        $selectPermissions->where('id_'.$profileTableName.' = ?', $idProfile);
    
        // Permissão de pelo menos visualizar
        $selectPermissions->where('value & 2 > 0');
    
        return $selectPermissions;
    }
    
    /**
     * Faz as limitações à consulta do menu de acordo com perfil do usuário
     * 
     * @param Zend_Db_Select $select
     */
    protected static function _getMenuProfile(Zend_Db_Select $select){
        
        $modelProfile = Util::getModelProfile();
        
        // Se houver modelo de perfis no sistema faz a limitação dos
        // itens de menu de acordo com o perfil do usuário, caso haja
        // usuário logado no sistema
        if($modelProfile && Zend_Auth::getInstance()->hasIdentity()){

            // Usuário logado
            $user = Zend_Auth::getInstance()->getIdentity();
            
            // Perfil do usuário logado
            $idProfile = $user->{ Util::getConfig(Util::CONFIG_AUTH)->column_profile };
            
            // Só faz validação dos itens de menu para perfis de ID != do Administrador geral
            if($idProfile != self::_ID_PERFIL_ADMINISTRADOR){
                
                $menuTableName = Util::getModelMenu()->getTableName();
                
                // Caso o id_model na tabela do menu esteja definido e não haja
                // um registro de permissões do sistema com pelo menos permissão
                // de visualizar par ao perfil do usuário atual não exibe no menu
                $selectPermissions = self::_getMenuProfileSelectPermissions('id_model', $idProfile);
                $select->where("CASE WHEN {$menuTableName}.id_model IS NOT NULL THEN {$menuTableName}.id_model IN ({$selectPermissions->__toString()}) ELSE 1=1 END");
                
                // Caso não haja um id_model definodo na tabela do menu faz questão
                // que exista uma permissão com mesmo nome do item de menu para que
                // ele seja exibido ao usuário (quando o item de menu tem um link e
                // é diferente de #)
                $selectPermissions = self::_getMenuProfileSelectPermissions('name', $idProfile);
                $select->where("CASE WHEN {$menuTableName}.id_model IS NULL THEN (COALESCE({$menuTableName}.url, '#') = '#' OR {$menuTableName}.name IN ({$selectPermissions->__toString()})) ELSE 1=1 END");
            }
            
        }
        
    }
    
    /**
     * Obtém os itens de menu para impressão
     */
    public static function getMenu($showAll = false){
        
        $menu = array();
        
        $model = Util::getModelMenu();
        
        if($model !== null){
            
            $select = $model->prepareSelect()->order(array('COALESCE(menu.id_parent, -1) ASC', 'menu.position ASC', 'menu.name ASC'));
            
            // Versões que não são de desenvolvimento só mostram posições maiores que 0
            if(!Util::isDev() && $showAll === false){
                $select->where('menu.position >= 0');
            }
            
            // Limitações de permissão do sistema
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
                throw new Exception(__CLASS__ . " - a configuração '{$config}' no item '{$configName}' = '{$modelName}' não define um model do sistema, carregue o modelo correspondente ou ajuste a configuração.");
            }
        } else {
            Util::exceptionLog($e);
            throw new Exception(__CLASS__ . " - a configuração '{$config}' precisa ter o item '{$configName}' definido para utilização no sistema.");
        }
        
        return $ret;
    }
    
    /**
     * Modelo padrão de model do sistema
     * 
     * @return Model
     */
    public static function getModelModel(){
        return self::_validateConfigModel(  Util::CONFIG_GENERAL , "modelTable" );
    }
    
    /**
     * Modelo padrão de arquivos do sistema
     * 
     * @return Fenix_File
     */
    public static function getModelFile(){
        return self::_validateConfigModel(  Util::CONFIG_FILE , "model" );
    }
    
    /**
     * Modelo padrão de usuários do sistema
     * 
     * @return Fenix_User
     */
    public static function getModelUser(){
        return self::_validateConfigModel(  Util::CONFIG_AUTH , "model" );
    }
    
    /**
     * Modelo padrão de menu do sistema
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
     * Modelo padrão de perfil do sistema
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
     * Modelo padrão de permissões do perfil do sistema
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
     * Retorn o nome do mês
     * @param int $month
     * @return String
     */
    public static function getMonthName($month){
        $ret = null;
        switch ($month){
            case 1: $ret = "Janeiro"; break;
            case 2: $ret = "Fevereiro"; break;
            case 3: $ret = "Março"; break;
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
     * Valida uma chave RSA (pública ou privada)
     * @param String $filenameOrString Conteúdo da chave (PEM) ou caminho do arquivo que o contém
     * @throws Exception
     * @return String PEM da chave
     */
    public static function rsaCheckKey($filenameOrString){
    
        $ret = $filenameOrString;
    
        if( is_file($filenameOrString) ){
            $ret = file_get_contents( $filenameOrString );
        }
    
        if( stripos($ret, "---BEGIN") === false ){
            throw new Exception(__METHOD__ . ' - o conteúdo '.var_export($filenameOrString, true) . ' não parece ser uma chave.');
        }
    
        return $ret;
    }
    
    /**
     * Criptografa um conteúdo com chave pública
     * @param String $data Conteúdo a ser criptografado
     * @param String $publicKey Chave pública (conteúdo em PEM ou nome de arquivo)
     * @return String Conteúdo criptografado
     */
    public static function rsaEncrypt($data, $publicKey){
    
        $encrypted = null;
    
        // Criptografa o conteúdo da requisição
        openssl_public_encrypt($data, $encrypted, self::rsaCheckKey($publicKey) );
    
        return $encrypted;
    }
    
    /**
     * Decriptografa um conteúdo com chave privada
     * @param String $data Conteúdo a ser decriptografado
     * @param String $privateKey Chave privada (conteúdo em PEM ou nome de arquivo)
     * @return String Conteúdo criptografado
     */
    public static function rsaDecrypt($data, $privateKey){
    
        $decrypted = null;
    
        // Criptografa o conteúdo da requisição
        openssl_private_decrypt($data, $decrypted, self::rsaCheckKey($privateKey));
    
        return $decrypted;
    }
    
    /**
     * Assina determinado conteúdo com a chave pública
     * @param String $data Dados a serem assinados
     * @param String $privateKey Chave privada (conteúdo ou nome de arquivo)
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
    
        // Faz a verificação da assinatura
        if( self::rsaVerify($data, $signature, $privateKeyDetails['key']) ){
            return $signature;
        }
    
        throw new Exception( __METHOD__ . " - impossível assinar conteúdo.");
    }
    
    /**
     * Verifica com a chave pública uma assinatura realizada com a chave privada
     * @param String $data Dados que foram assinados
     * @param String $signature Conteúdo da assinatura
     * @param String $key Chave privada ou pública (conteúdo ou nome de arquivo)
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
























