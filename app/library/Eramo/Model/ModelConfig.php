<?php

class ModelConfig {

    /** Special constant to indicate that this field does not has a corresponding data type */
    const TYPE_VIEW = '__type_view';
    
    /** Field's Constants for easy reference */
    const PK = 'pk';
    const FK = 'fk';
    const TEXT = 'text';
    const PASSWORD = 'password';
    const BIGTEXT = 'bigtext';
    const RICHTEXT = 'richtext';
    const INT = 'int';
    const BIGINT = 'bigint';
    const DECIMAL = 'decimal';
    const CURRENCY = 'currency';
    const PERCENT = 'percent';
    const SELECT = 'select';
    const SELECT_TEXT = 'select_text';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const OPTION = 'option';
    const RADIO = 'radio';
    const FILE = 'file';
    const IMAGE = 'image';
    
    /** Brazilian data format specific types */
    const TELEPHONE = 'telephone';
    const CPF = 'cpf';
    const CEP = 'cep';
    const CNPJ = 'cnpj';
    
    /** Fenix Form UI types */
    const TAB = 'tab';
    const SEPARATOR = 'separator';
    
    /** Fenix system internals types */ 
    const GRID = '__grid';

    /**
     * Lists of the current supported types
     * @var ModelConfigType[]
     */
    protected static $_types = array();

    /**
     * Lists of the numeric types
     * @var String[]
     */
    protected static $_numericTypes = array('int', 'integer', 'bigint', 'numeric', 'bigserial', 'smallint');

    /**
     * Get a list of the current supported types
     *
     * @return ModelConfigType[]
     */
    public static function getTypes(){
        return self::$_types;
    }

    /**
     * Register a new type
     *
     * @param ModelConfigType $configField
     * @throws Exception
     */
    public static function register(ModelConfigType $configField){
        $name = $configField->getName();
        if(isset(self::$_types[$name])){
            throw new Exception(__CLASS__.': The type ' . $name . ' is already defined.');
        }
        self::$_types[$name] = $configField;
    }

    /**
     * Returns a registered type definition
     *
     * @param String $name
     * @throws Exception
     *
     * @return ModelConfigType
     */
    public static function getType($name){
        if($name instanceof ModelField){
            $name = $name->getType();
        }
        $name = strtolower($name);
        if(!isset(self::$_types[$name])){
            throw new Exception(__CLASS__.': The type ' . $name . ' is not registered.');
        }
        return self::$_types[$name];
    }

    /**
     * Returns if a field is of a certain type
     *
     * @param ModelField $field
     * @param String $type (one of this classe's constants)
     *
     * @return boolean
     */
    public static function checkType(ModelField $field, $type){
        if($type instanceof ModelField){
            $type = ModelConfig::getType($type->getType());
        }
        
        if($type instanceof ModelConfigType){
            $type = $type->getType();
        }
        
        $type = strtolower($type);
        $type2 = strtolower($field->getType());
        
        if($type === $type2){
            return true;
        }
        return false;
    }

    /**
     * Returns if a field is numeric
     *
     * @param ModelField|ModelConfigType|String $type
     *
     * @return boolean
     */
    public static function checkTypeNumeric($type){
        if($type instanceof ModelField){
            $type = ModelConfig::getType($type->getType());
        }
        
        if($type instanceof ModelConfigType){
            $type = $type->getType();
        }
        
        $type = preg_replace("@([^a-z]+)@", "", strtolower($type));
        foreach(self::$_numericTypes as $numericType){
            if($numericType === $type){
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a field name
     *
     * @param ModelField $field
     */
    public static function name(ModelField $field){
        $ret = $field->getName();
        if(ModelConfig::checkType($field, ModelConfig::FK)){
            if($field->getKey() == null){
                throw new Exception(__CLASS__.": A column defined as a FK must have the following attributes: 'table', 'key', 'field' in order to reference another field correctly.");
            }
            $ret = $field->getKey() . "_" . $ret;
        }
        return $ret;
    }

    /**
     * Change the type os the fields from key to values int the arg array
     * 
     * @param String[] $changes
     */
    public static function changeTypes($changes){
        
        // Change the types definitions
        foreach(self::$_types as $type){
            if( isset($changes[$type->getType()]) ){
                
                if( self::checkTypeNumeric($type->getType()) == true){
                    self::$_numericTypes[] = preg_replace("@([^a-z]+)@", "", strtolower($changes[$type->getType()]));
                }
                
                $type->setType( $changes[$type->getType()] );
            }
        }
        
    }
    
    /**
     * Executes the $sql statements in the connection represented by $db 
     * 
     * @param Zend_Db_Adapter_Abstract $db
     * @param String[] $sql
     */
    protected static function _sqlExecuteStatement(Zend_Db_Adapter_Abstract $db, $sql){
    
        if(!is_array($sql)){
            $sql = array($sql);
        }
    
        foreach ($sql as $s){
            if(is_array($s)){
                self::_sqlExecuteStatement($db, $s);
            } else {
                $s = trim($s);
                $s = trim($s, ";");
                $db->query($s);
            }
        }
    }
    
    /**
     * Logs SQL statements
     * 
     * @param String[] $sql
     */
    protected static function _sqlLog($sql){
    
        if(!is_array($sql)){
            if(is_dir("log/") && is_writable("log/")){
                $fp = fopen("log/model-" . date("Y-m-d").".sql", "a+");
                fwrite($fp, $sql . "\n");
                fclose($fp);
            }
        } else {
            foreach ($sql as $s){
                self::_sqlLog($s);
            }
        }
    
    }
    
    /**
     * Insert/updates a model into the database 
     * 
     * @param Model $model
     */
    public static function modelLoad(Model $model){
        
        Util::cacheClean();
        
        $currentModel = null;
        
        // Tenta criar o Model atual, caso não dê certo é porque
        // não existe e será uma criação de nova tabela
        try {
            $currentModel = Model::factory($model->getName());
        } catch (Exception $e){}
        
        $sql = ModelSql::factory()->generate($model, $currentModel);
        
        self::_sqlExecuteStatement( Table::getDefaultAdapter(), $sql);
        self::_sqlLog("\n-- Model update '{$model->getName()}' at " . date('H:i:s'));
        self::_sqlLog($sql);
        
        Util::cacheClean();
        
    }
    
    /**
     * Insert/updates a model into the database 
     * 
     * @param Model $model
     */
    public static function modelDelete(Model $model){
        
        Util::cacheClean();
        
        $sql = ModelSql::factory()->delete($model);
        
        self::_sqlExecuteStatement( Table::getDefaultAdapter(), $sql);
        self::_sqlLog("\n-- Model delete '{$model->getName()}' at " . date('H:i:s'));
        self::_sqlLog($sql);
        
        Util::cacheClean();
    }
    
    /**
     * Initialize systems types
     */
    public static function init(){
        
        ModelConfigType::factory(ModelConfig::PK, 'BIGSERIAL')
        ->setExtraDefinition(ModelSql::PK)
        ->register();
        
        ModelConfigType::factory(ModelConfig::FK, 'BIGINT')
        ->setExtraDefinition(ModelSql::FK)
        ->setFormatterForm('$.fn.form.formatter.fk')
        ->setFormatterFormView('$.fn.form.formatter.fkView')
        ->register();
        
        ModelConfigType::factory(ModelConfig::TEXT, 'VARCHAR')
        ->setFormatterForm('$.fn.form.formatter.text')
        ->setSizeRequired()
        ->register();
        
        ModelConfigType::factory(ModelConfig::PASSWORD, 'VARCHAR')
        ->setFormatterForm('$.fn.form.formatter.password')
        ->setSize(100)
        ->register();
        
        ModelConfigType::factory(ModelConfig::BIGTEXT, 'TEXT')
        ->setJs('fenix/jquery.autosize.js')
        ->setFormatterForm('$.fn.form.formatter.bigtext')
        ->setFormatterFormView('$.fn.form.formatter.bigtextView')
        ->setFormatterGrid('$.fn.grid.formatter.bigtext')
        ->register();
        
        ModelConfigType::factory(ModelConfig::RICHTEXT, 'TEXT')
        ->setJs( array('fenix/jquery.hotkeys.js','fenix/bootstrap-wysiwyg.js') )
        ->setFormatterForm('$.fn.form.formatter.richtext')
        ->setFormatterFormView('$.fn.form.formatter.richtextView')
        ->setFormatterGrid('$.fn.form.formatter.richtextView')
//         ->setFormatterGrid('$.fn.grid.formatter.richtextView')
        ->register();
        
        ModelConfigType::factory(ModelConfig::INT, 'INTEGER')
        ->setFormatterForm('$.fn.form.formatter.int')
        ->register();
        
        ModelConfigType::factory(ModelConfig::BIGINT, 'BIGINT')
        ->setFormatterForm('$.fn.form.formatter.bigint')
        ->register();
        
        ModelConfigType::factory(ModelConfig::DECIMAL, 'NUMERIC(12,2)')
        ->setFormatterForm('$.fn.form.formatter.currency')
        ->setFormatterFormView('$.fn.form.formatter.currencyView')
        ->setFormatterGrid('$.fn.grid.formatter.currency')
        ->register();
        
        ModelConfigType::factory(ModelConfig::CURRENCY, 'NUMERIC(12,2)')
        ->setFormatterForm('$.fn.form.formatter.currency')
        ->setFormatterFormView('$.fn.form.formatter.currencyView')
        ->setFormatterGrid('$.fn.grid.formatter.currency')
        ->register();
        
        ModelConfigType::factory(ModelConfig::PERCENT, 'NUMERIC(12,2)')
        ->setFormatterForm('$.fn.form.formatter.currency')
        ->setFormatterFormView('$.fn.form.formatter.currencyView')
        ->setFormatterGrid('$.fn.grid.formatter.currency')
        ->register();
        
        ModelConfigType::factory(ModelConfig::SELECT, 'SMALLINT')
        ->setFormatterForm('$.fn.form.formatter.select')
        ->setFormatterFormView('$.fn.form.formatter.selectView')
        ->setFormatterGrid('$.fn.grid.formatter.select')
        ->register();
        
        ModelConfigType::factory(ModelConfig::SELECT_TEXT, 'VARCHAR')
        ->setFormatterForm('$.fn.form.formatter.selectText')
        ->register();
        
        ModelConfigType::factory(ModelConfig::DATE, 'DATE')
        ->setJs(array('fenix/datepicker.js'))
        ->setCss(array('fenix/datepicker.css'))
        ->setFormatterForm('$.fn.form.formatter.date')
        ->setFormatterFormView('$.fn.form.formatter.dateView')
        ->setFormatterGrid('$.fn.grid.formatter.date')
        ->setModelFormatter( function($v, $field = null){ return (!empty($v) ? implode("-", array_reverse(explode("/", substr($v, 0, 10)))) : null); } )
        ->register();
        
        ModelConfigType::factory(ModelConfig::DATETIME, 'TIMESTAMP')
        ->setJs(array('fenix/datepicker.js', 'fenix/jquery.timePicker.js'))
        ->setCss(array('fenix/datepicker.css'))
        ->setFormatterForm('$.fn.form.formatter.datetime')
        ->setFormatterFormView('$.fn.form.formatter.datetimeView')
        ->setFormatterGrid('$.fn.grid.formatter.datetime')
        ->setModelFormatter( function($v, $field = null){ return (!empty($v) ? implode("-", array_reverse(explode("/", substr($v, 0, 10)))).substr($v, 10) : null); } )
        ->register();
        
        ModelConfigType::factory(ModelConfig::OPTION, 'SMALLINT')
        ->setFormatterForm('$.fn.form.formatter.option')
        ->setFormatterFormView('$.fn.form.formatter.optionView')
        ->setFormatterGrid('$.fn.grid.formatter.option')
        ->register();
        
        ModelConfigType::factory(ModelConfig::RADIO, 'SMALLINT')
        ->setFormatterForm('$.fn.form.formatter.radio')
        ->setFormatterFormView('$.fn.form.formatter.radioView')
        ->setFormatterGrid('$.fn.grid.formatter.radio')
        ->register();
        
        ModelConfigType::factory(ModelConfig::FILE, 'VARCHAR')
        ->setSize(512)
        ->setJs(array('fenix/sha1.js', 'fenix/jquery.upload.js'))
        ->setFormatterForm('$.fn.upload.formatterForm')
        ->setFormatterGrid('$.fn.upload.formatterGrid')
        ->setFormatterFormView('$.fn.upload.formatterGrid')
        ->register();
        
        ModelConfigType::factory(ModelConfig::IMAGE, 'VARCHAR')
        ->setSize(512)
        ->setJs(array('fenix/sha1.js', 'fenix/jquery.upload.js'))
        ->setFormatterForm('$.fn.upload.formatterForm')
        ->setFormatterGrid('$.fn.upload.formatterGrid')
        ->setFormatterFormView('$.fn.upload.formatterGrid')
        ->register();
        
        ModelConfigType::factory(ModelConfig::CPF, 'VARCHAR')
        ->setSize(14)
        ->setJs(array('fenix/jquery.maskedinput.js'))
        ->setFormatterForm('$.fn.form.formatter.cpf')
        ->register();
        
        ModelConfigType::factory(ModelConfig::CNPJ, 'VARCHAR')
        ->setSize(18)
        ->setJs(array('fenix/jquery.maskedinput.js'))
        ->setFormatterForm('$.fn.form.formatter.cnpj')
        ->register();
        
        ModelConfigType::factory(ModelConfig::CEP, 'VARCHAR')
        ->setSize(10)
        ->setFormatterForm('$.fn.form.formatter.cep')
        ->register();
        
        ModelConfigType::factory(ModelConfig::TELEPHONE, 'VARCHAR')
        ->setSize(15)
        ->setFormatterForm('$.fn.form.formatter.telephone')
        ->register();
        
        //
        ModelConfigType::factory(ModelConfig::GRID, ModelConfig::TYPE_VIEW)
        ->setJs(array('fenix/jquery.grid.js'))
        ->setFormatterForm('$.fn.form.formatter.grid')
        ->setFormatterFormView('$.fn.form.formatter.grid')
        ->register();
        
        // O campo do tipo ModelConfig::TAB serve somente para criação de abas
        ModelConfigType::factory(ModelConfig::TAB, ModelConfig::TYPE_VIEW)
        ->register();
        
        // O campo do tipo ModelConfig::SEPARATOR serve somente para criação de separadores de conteúdo num formulário
        ModelConfigType::factory(ModelConfig::SEPARATOR, ModelConfig::TYPE_VIEW)
        ->setFormatterForm('$.fn.form.formatter.separator')
        ->setFormatterFormView('$.fn.form.formatter.separator')
        ->register();
        
        // Inclusão de suporte a SQLITE fazendo com que os tipos de dados sejam trocados
        if(Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_SQLITE'){
            $changes = array();
            $changes['BIGSERIAL'] = 'INTEGER PRIMARY KEY';
            $changes['BIGINT'] = 'INTEGER';
            $changes['SMALLINT'] = 'INTEGER';
            $changes['VARCHAR'] = 'TEXT';
            $changes['NUMERIC(12,2)'] = 'REAL';
            ModelConfig::changeTypes($changes);
        }
        
        // Define o modelFormatter para todos os campos numéricos
        foreach(self::$_types as $type){
            
            if(self::checkTypeNumeric($type)){
                
                // Define a função anônima de formatter
                $type->setModelFormatter(
                    function($v, ModelField $field = null){
                    
                        if(stripos($v, ",") !== false && stripos($v, ".") !== false){
                    
                            $v = str_replace(".", "", $v);
                            $v = str_replace(",", ".", $v);
                    
                        } else if(stripos($v, ",") !== false){
                    
                            $v = str_replace(",", ".", $v);
                    
                        }
                    
                        $v = preg_replace("/([^\-0-9\.])/", "", $v);
                    
                        return $v;
                    }
                       
                );
            }
        }
    }
}

class ModelConfigType extends AttributeList {

    /**
     * Constante da classe
     * @var String
     */
    const NAME = 'name';
    
    /**
     * Constante da classe
     * @var unknown
     */
    const TYPE = 'type';
    
    
    /**
     * Define a lista de atributos obrigatórios
     */
    protected function _required(){
        return array(self::NAME, self::TYPE);
    }

    /**
     * Creates a new object
     *
     * @param String $name
     * @param String $type
     * @throws Exception
     *
     * @return ModelConfigType
     */
    public static function factory($name, $type){
        $instance = new self(array(self::NAME => $name, self::TYPE => $type));
        $instance->setModelFormatter( function($v, ModelField $field = null){ return $v; } );
        return $instance;
    }
    
    /**
     * Obtém o atributo
     * @return String
     */
    public function getName(){
        return $this->_attributes[self::NAME];
    }
    
    /**
     * Obtém o atributo
     * @return String
     */
    public function getType(){
        return $this->_attributes[self::TYPE];
    }

    /**
     * Sets a field size
     *
     * @param int $v
     */
    public function setSize($v){
        return $this->__set('size', $v);
    }

    /**
     *  Set if this type needs to have a defined size in the model
     *
     * @return ModelConfigType
     */
    public function setSizeRequired(){
        return $this->__set('sizeRequired', true);
    }

    /**
     * Returns if this type needs to have a defined size in the model
     *
     * @return boolean
     */
    public function isSizeRequired(){
        return $this->__get('sizeRequired') ? true : false;
    }

    /**
     * Set if the type requires extra definition (constants in ModelSql)
     *
     * @param $string Extra definition (constants in ModelSql)
     */
    public function setExtraDefinition($extraDefinition){
        return $this->__set('extraDefinition', $extraDefinition);
    }

    /**
     * Gets a lista of extra definition of this type
     *
     * @return String[]
     */
    public function getExtraDefinition(){
        return $this->__get('extraDefinition');
    }

    /**
     * Register a new ModelConfigType in the supported field's list
     */
    public function register(){
        ModelConfig::register($this);
    }
}

ModelConfig::init();




