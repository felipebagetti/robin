<?php

require_once 'Zend/Db/Table/Abstract.php';

/**
 * Classe de interface padrão entre o Model e as tabelas
 */
class Table extends Zend_Db_Table_Abstract {

    /**
     * Static metadata caching
     * @var mixed[][]
     */
    protected static $_cache = array();
    
    /**
     * Constrói um novo objeto Table
     * 
     * @param String $schema
     * @param String $name
     */
    public function __construct($name, $schema = null){
        
        $config = array(self::NAME => $name,
                        self::PRIMARY => 'id');
        
        if($schema != null){
            $config[self::SCHEMA] = $schema;
        }
        
        parent::__construct($config);
    }
    
    /**
     * Factory de classe Table para facilitar o uso
     * 
     * @param String $name
     * @param String $schema
     */
    public static function factory($name, $schema = null){
        return new self($name, $schema);
    }
    
    /**
     * Retorna o nome da tabela
     * 
     * @return string
     */
    public function getName(){
        $ret = $this->_name;
        if($this->_schema){
            $ret = $this->_schema . "." . $ret;
        }
        return $ret;
    }

    /**
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDefaultAdapter(){
        // Caso o adapter padrão do sistema não exista o cria
        if(parent::getDefaultAdapter() == null){
            self::setupDatabaseAdapter();
        }
        return parent::getDefaultAdapter();
    }
    
    /**
     * Método que cria nova conexão ao banco de dados
     */
    public static function setupDatabaseAdapter($defaultAdapter = true){
        
        $config = Util::getConfig(Util::CONFIG_DATABASE, true);
        
        if(Util::isDev() == true){
            $config['profiler'] = array('enabled' => true, 'class' => 'Db_Profiler');
        }
        
        $adapter = $config['dbtype'];
        unset($config['dbtype']);
        
        $db = Zend_Db::factory($adapter, $config);
        
        if($adapter == 'PDO_SQLITE'){
            
            $dbprefix = str_replace(".db", "", $config['dbname']);
            $dbdir = dirname($dbprefix);
            
            $dbprefix = ".".str_replace($dbdir, "", $dbprefix)."_";
            
            $cmd = "cd ".$dbdir ." ; find . | grep {$dbprefix} | grep db\$ ";
            
            exec($cmd, $output);
            
            foreach ($output as $database){
                $filename = str_replace("./", $dbdir."/", $database);
                $tmp = explode("_", str_replace(".db", "", $database));
                $database = end($tmp);
                
                // Caminho absoluto se já não for um
                if(stripos($filename, "/") !== 0){
                    $filename = dirname($_SERVER['SCRIPT_FILENAME'])."/".$filename;
                }
                
                $db->query("ATTACH DATABASE '{$filename}' AS {$database};");
            }
            
            // Checagem de FK é desativada por padrão no SQLite, aqui ativa
            $db->query("PRAGMA foreign_keys = on;");
            
            // Registra funções no sqlite
            $conn = $db->getConnection();
            $conn->sqliteCreateFunction('NOW', 'Table::sqlite_now', 0);
            $conn->sqliteCreateFunction('strip_accents', 'Table::sqlite_strip_accents', 1);
            $conn->sqliteCreateFunction('LPAD', 'Table::sqlite_lpad', 3);
            $conn->sqliteCreateFunction('RPAD', 'Table::sqlite_rpad', 3);
            $conn->sqliteCreateFunction('substring', 'Table::sqlite_substring', 2);
            $conn->sqliteCreateFunction('substring', 'Table::sqlite_substring', 3);
            
            $conn->setAttribute(PDO::ATTR_TIMEOUT, 30000);
        }
        
        if($defaultAdapter == true){
            self::setDefaultAdapter($db);
        }
        
        if( Util::cacheIsEnabled() ){
            self::setDefaultMetadataCache( Util::getCache() );
        }
        
        return $db;
    }
    
    /**
     * Função para uso no SQLITE
     * @return string
     */
    public static function sqlite_now(){
        return date("Y-m-d H:i:s");
    }
    
    /**
     * Função para uso no sqlite
     * @param string $s
     * @return string
     */
    public static function sqlite_strip_accents($s){
        return Util::strip_accents($s);
    }
    
    /**
     * Função para uso no sqlite
     * @param String $input
     * @param int $pad_length
     * @param String $pad_string
     * @return string
     */
    public static function sqlite_lpad($input, $pad_length, $pad_string){
        if($pad_length < strlen($input)){
            $input = substr($input, 0, $pad_length);
        } else {
            $input = str_pad($input, $pad_length, $pad_string, STR_PAD_LEFT);
        }
        return $input;
    }
    
    /**
     * Função para uso no sqlite
     * @param String $input
     * @param int $pad_length
     * @param String $pad_string
     * @return string
     */
    public static function sqlite_rpad($input, $pad_length, $pad_string){
        if($pad_length < strlen($input)){
            $input = substr($input, 0, $pad_length);
        } else {
            $input = str_pad($input, $pad_length, $pad_string, STR_PAD_RIGHT);
        }
        return $input;
    }
    
    /**
     * Função para uso no sqlite
     * @param String $string
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function sqlite_substring($string, $start, $length = null){
        if($start < 1){
            return $string;
        }
        if($length === null){
            $string = substr($string, $start-1 );
        } else {
            $string = substr($string, $start-1, $length );
        }
        return $string;
    }
    
    /**
     * Initialize database adapter.
     *
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    protected function _setupDatabaseAdapter()
    {
        if (! $this->_db) {
            $this->_db = self::getDefaultAdapter();
            if (!$this->_db instanceof Zend_Db_Adapter_Abstract) {
                require_once 'Zend/Db/Table/Exception.php';
                throw new Zend_Db_Table_Exception('No adapter found for ' . get_class($this));
            }
        }
    }
    
    /**
     * Generates a unique cache id for this table in the current database connection
     * 
     * @return string
     */
    protected function _getCacheId(){
        
        //get db configuration
        $dbConfig = $this->_db->getConfig();
        
        $port = isset($dbConfig['options']['port'])
        ? ':'.$dbConfig['options']['port']
        : (isset($dbConfig['port'])
                ? ':'.$dbConfig['port']
                : null);
        
        $host = isset($dbConfig['options']['host'])
        ? ':'.$dbConfig['options']['host']
        : (isset($dbConfig['host'])
                ? ':'.$dbConfig['host']
                : null);
        
        // Define the cache identifier where the metadata are saved
        $cacheId = md5( // port:host/dbname:schema.table (based on availabilty)
                $port . $host . '/'. $dbConfig['dbname'] . ':'
                . $this->_schema. '.' . $this->_name
        );
        
        return $cacheId;
    }
    
    /**
     * (non-PHPdoc)
     * @see Zend_Db_Table_Abstract::_setupMetadata()
     */
    protected function _setupMetadata(){
        
        $cacheId = $this->_getCacheId();
        
        // Adds a new level of metadataCache for when the default cache system is disabled
        if(Util::cacheIsEnabled() == false){
            // There is a static contained cache object
            if(isset(self::$_cache[$cacheId])){
                $this->_metadata = self::$_cache[$cacheId];
                $this->setMetadataCacheInClass(true);
            }
        }
        
        $ret = parent::_setupMetadata();
        
        // Throws a exception when there is no metadata
        if(count($this->_metadata) == 0){
            
            $adapter = Zend_Db_Table_Abstract::getDefaultAdapter();
            $database = $adapter->getConfig();
            if(isset($database['host']) && isset($database['username'])){
                $database = $database['username'].'@'.$database['host']. ':'.$database['dbname'].' using ' . get_class($adapter);
            } else {
                $database = $database['dbname']. ' using ' . get_class($adapter);                
            }
            
            require_once 'Zend/Db/Table/Exception.php';
            throw new Zend_Db_Table_Exception("Unable to find table metadata for '{$this->_schema}.{$this->_name}'. The table probably does not exist in this database: '{$database}'.");
        }
        
        // Sets the static contained cache object on the first load
        if(Util::cacheIsEnabled() == false){
            if(!isset(self::$_cache[$cacheId])){
                self::$_cache[$cacheId] = $this->_metadata;
            }
        }
        
        return $ret;
    }
    
    /**
     * Cleans the class static cache
     */
    public static function cacheClean(){
        self::$_cache = array();
    }
}








