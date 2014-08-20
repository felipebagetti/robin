<?php

/**
 * Classe que representa um Model no sistema.
 * 
 * Abstrai opera��es relativas aos dados.
 */
class Model {
    
    /**
     * Nome do modelo 
     * 
     * @var String
     */
    protected $_name = null;
    
    /**
     * Se��o atual 
     * 
     * @var String
     */
    protected $_section = null;
    
    /**
     * Lista de ModelField do Model
     * 
     * @var ModelField[]
     */
    protected $_fields = array();

    /**
     * Lista de Section do Model
     * 
     * @var ModelSection[]
     */
    protected $_sections = array();
    
    /**
     * XML em formato de string
     * 
     * @var String
     */
    protected $_xml = null;
    
    /**
     * Cache dos modelos
     * 
     * @var String[]
     */
    protected static $_cache = array();
    
    /**
     * Determina se uma transa��o foi iniciada
     * 
     * @var boolean
     */
    protected static $_transactionStarted = false;
    
    /**
     * Construtor padr�o do objeto Model (protected pois a cria��o de um modelo deve ser feita pelos m�todos factory*)
     * 
     * @param String $xml String xml
     * @param String $section String do nome da section
     */
    protected function __construct($xml, $section = null){
        
        // Faz o parser do XML (cria��o dos objetos filhos ModelSection e ModelField)
        $this->parseXml( $xml );
        
        // Faz a defini��o da Section atual do Model de acordo com o par�metro
        // ou define como sendo a primeira da lista de se��es do modelo
        if($section == null){
            $sectionObj = current($this->getSections());
            $this->_section = $sectionObj->getName();
        } else {
            $sectionObj = $this->getSection($section);
            if($sectionObj == null){
                throw new Exception(__CLASS__.": A ModelSection {$section} n�o existe no Model " . $this->getName());
            }
            $this->_section = $sectionObj->getName();
        }
    }
    
    /**
     * Limpa o cache do objeto
     */
    public static function cacheClean(){
        self::$_cache = array();
    }
    
    public static function getModelTableName(){
    
        $ret = "fenix.model";
    
        $config = Util::getConfig(Util::CONFIG_GENERAL);
    
        if(!empty($config->modelTable)){
            $ret = $config->modelTable;
        }
        
        if(stripos($ret, ".") === false){
            throw new Exception(__CLASS__ . ': n�o � poss�vel definir a configura��o general->modelTable sem o prefixo do schema. Deve-se definir no formato schema.name.');
        }
    
        return $ret;
    }
    
    protected static function _factorySearchModel($name, $schema = null){
        
        $tag = Util::getCacheTag( "_factorySearchModel-".$name."_s-".($schema != null ? $schema : $name) );
        
        $rows = false;
        
        if( Util::cacheIsEnabled() ){
            $rows = Util::getCache()->load($tag);
        }
        // Mesmo com o cache desativado para economizar consultas ao banco
        // � feito um cache na mem�ria local da execu��o do PHP (somente 
        // enquanto o script est� sendo executado)
        else {
            $rows = isset(self::$_cache[$tag]) ? self::$_cache[$tag] : false; 
        }
        
        if($rows === false){
            
            $select = Table::factory( Model::getModelTableName() )->select()->where('name = ?', $name);
            
            if($schema != null){
                $select->where('schema = ?', $schema);
            }
            
            $rows = $select->query()->fetchAll();
            
            // Cache geral da aplica��o (memcached ou arquivos)
            if(Util::cacheIsEnabled() ){
                Util::getCache()->save($rows, $tag);
            }
            // Cache local de execu��o
            else {
                // S� se existerem linhas que se salva um cache (para
                // evitar problemas no carregamento de novos modelos)
                if(count($rows) > 0){
                    self::$_cache[$tag] = $rows;
                }
            }
            
        }
        
        return $rows;
    }
    
    /**
     * Cria um novo objeto model
     * 
     * @param String $name
     * @param String $section
     * 
     * @return Model
     */
    public static function factory($name, $section = null){
        
        $schema = null;
        
        if(stripos($name, ".") !== false){
            list($schema, $name) = explode(".", $name);
        }
        
        if(!strlen($name)){
            throw new Exception(__CLASS__.": � necess�rio definir o nome do modelo a ser criado.");
        }
        
        $rows = self::_factorySearchModel($name, $schema);
        
        // Valida que somente um Model foi encontrado a partir da defini��o na cria��o do objeto
        if(count($rows) > 1){
            throw new Exception(__CLASS__.": Defini��o de Model amb�gua, mais de um Model foi identificado para os par�metros: {$schema}.{$name}");
        } else if(count($rows) == 0) {
            throw new Exception(__CLASS__.": N�o foi poss�vel identificar um Model para os par�metros: {$schema}.{$name}");        
        }
        
        // Determina qual a classe que deve ser criada
        
        $row = current($rows);
        
        $schema = $row['schema'];
        $name = $row['name'];
        
        $modelDir = "app/" . ($schema == "fenix" ? "fenix" : "modules/".$schema) . "/model/";
        
        // Se est� em testes desce um n�vel no caminho
        if(stripos(getcwd(), "/app_tests") !== false){
            $modelDir = "../".$modelDir;
        }
        
        // 1a op��o classe pr�pria do Model dentro do m�dulo, pode ter dois formatos: schema_name ou name
        // dentro do diret�rio padr�o app/modules/schema/model/ ou no caso da base do sistema em app/fenix/model/
        
        $classModulePrefixed = self::_factoryCheckClass($modelDir, $schema."-".$schema);
        $classModule = self::_factoryCheckClass($modelDir, $schema);
        $classModelPrefixed = self::_factoryCheckClass($modelDir, $schema." ".$name);
        $classModel = self::_factoryCheckClass($modelDir, $name);
        $classDefault = __CLASS__;
        
        // Caso a 'classe do m�dulo' seja final impede que ela seja utilizada
        // por outro models que n�o o padr�o (usa-se esse workaround caso o nome
        // de um model seja exatamente igual ao nome do m�dulo)
        if($classModule != null){
            $ref = new ReflectionClass($classModule);
            if($ref->isFinal() === true){
                $classModule = null;
            }
        }
        
        // Caso n�o haja uma classe do m�dulo definida
        if($classModule === null){
            // Define a partir desse momento a $classModulePrefixed como padr�o do m�dulo 
            if($classModulePrefixed !== null){
                $classModule = $classModulePrefixed;
            }
        }
        
        $className = $classModelPrefixed;
        
        // 1a op��o pr�pria classe declarada com o nome do model
        if($className == null){
            $className = $classModel;
        }
        
        // 2a op��o classe padr�o do m�dulo com o nome do m�dulo em Camel Case
        if($className == null){
            $className = $classModule;
        }
        
        // 3a op��o classe atual
        if($className == null){
            $className = $classDefault;
        }

        // Lista dos model padr�o do sistema
        $modelsDefault = array();
        $modelsDefault[Util::getConfig(Util::CONFIG_AUTH)->model] = "Fenix_User";
        $modelsDefault[Util::getConfig(Util::CONFIG_PROFILE)->model] = "Fenix_Profile";
        $modelsDefault[Util::getConfig(Util::CONFIG_MENU)->model] = "Fenix_Menu";
        $modelsDefault[Util::getConfig(Util::CONFIG_FILE)->model] = "Fenix_File";
        
        // Caso a className localizada seja a padr�o faz com que se crie 
        // o objeto padr�o desse m�dulo ao inv�s
        foreach($modelsDefault as $modelDefault => $classModelDefault){
            if(($row['schema'].".".$row['name'] == $modelDefault || (stripos($modelDefault, ".") === false && $row['name'] == $modelDefault))
            && ($className == $classDefault || $className == $classModule || $className == $classModulePrefixed)){
                $className = $classModelDefault;
            }
        }
        
        // Cria o novo objeto
        $model = new $className($rows[0]['xml'], $section);
        
        // Caso esteja sendo criado um model padr�o do sistema, configurado no config.json
        // obriga a inst�ncia criada herde as caracter�stias das classes padr�o para cada
        // uma dessas configura��es
        foreach($modelsDefault as $modelDefault => $classModelDefault){
            if($row['schema'].".".$row['name'] == $modelDefault){
                if( !is_subclass_of($model, $classModelDefault) && get_class($model) !== $classModelDefault ){
                    throw new Exception(__CLASS__.": 1 - A classe {$className} precisa ser uma subclasse de {$classModelDefault} j� que o modelo dela ({$model->getName()}) � configurado para um modelo b�sico do sistema no config.json.");
                }
            }
        }
        
        // Caso exista uma classe gen�rica para o m�dulo faz verifica��es extras
        if($classModule != null){
            
            // Caso seja uma classe especifica para esse model ela deve herdar da classe gen�rica do m�dulo
            if($className === $classModel || $className === $classModelPrefixed){
                if(!is_subclass_of($model, $classModule) && get_class($model) !== $classModule){
                    // A exce��o � com as classes de Usu�rio e Arquivo que precisam herdar dos m�dulos do fenix
                    if(stripos(get_parent_class($model), "Fenix_") === false && $className !== "Model"){
                        throw new Exception(__CLASS__.": 2 - A classe {$className} precisa ser uma subclasse de {$classModule}. Caso a classe {$classModule} tiver sido criada para um Model espec�fico declare-a como final.");
                    }
                }
            }
            
            // Caso a classe criada j� seja a gen�rica do model verifica se ela herda da classe padr�o __CLASS__
            if($className === $classModule){
                if(!is_subclass_of($model, $classDefault)){
                    throw new Exception(__CLASS__.": 3 - A classe {$className} precisa ser uma subclasse de {$classDefault}.");
                }
            }
        }
        
        // Prepara as classes de testes para o Model criado
        self::_factoryPrepareTests($model);
        
        return $model;
    }
    
    /**
     * Trava a cria��o de uma nova lista enquando uma � criada
     * @var boolean
     */
    protected static $_factoryPrepareTestsModelLoadDisabled = false;
    
    /**
     * Mant�m uma lista dos modelos j� visitados durante a cria��o da lista
     * @var String
     */
    protected static $_factoryPrepareTestsModelLoadList = array();
    
    /**
     * Prepara as classes de testes para o Model criado caso seja necess�rio
     * 
     * @param Model $model
     */
    protected static function _factoryPrepareTests(Model $model){
        
        if(Util::isDev() && !Util::isCli()){
            
            list($schema, $name) = explode(".", $model->getName());
            
            if(get_class($model) != "Model"){
                $ref = new ReflectionClass($model);
                foreach($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method){
                    if($method->getDeclaringClass()->getName() === get_class($model)){
                        
                        // Ignora o m�todo factory
                        if(stripos($method->getName(), "factory") !== false || stripos($method->getName(), "__destruct") === 0 || stripos($method->getName(), "__construct") === 0){
                            continue;
                        }
                        
                        $classname = get_class($model);
                        
                        $testClassname = $classname . "_" . ucfirst($method->getName());
                        
                        $testFilename = dirname($_SERVER['SCRIPT_FILENAME'])."/app_tests/modules/{$schema}/model/{$classname}/{$testClassname}.php";

                        if(!is_file($testFilename) && stripos($classname, "Fenix_") === false && self::$_factoryPrepareTestsModelLoadDisabled == false){
                            
                            self::$_factoryPrepareTestsModelLoadList = array();
                            self::$_factoryPrepareTestsModelLoadDisabled = true;
                            
                            Util::log("Arquivo de teste criado: " . $testFilename);
                            
                            $stubContents = file_get_contents(dirname($_SERVER['SCRIPT_FILENAME'])."/app_tests/PhpStubModel.php");
                            
                            $stubContents = str_replace("<CLASSNAME>", $testClassname."Test", $stubContents);
                            $stubContents = str_replace("<TEST_NAME>", $method->getName(), $stubContents);
                            $stubContents = str_replace("<MODEL_LOAD>", "        ".implode("\n        ", self::_factoryPrepareTestsModelLoad($model)), $stubContents);
                            
                            $dirname = dirname($testFilename);
                            
                            if(!is_dir($dirname)){
                                mkdir($dirname, 0777, true);
                            }
                            
                            file_put_contents($testFilename, $stubContents);
                            
                            self::$_factoryPrepareTestsModelLoadDisabled = false;
                        }
                    }
                }
            }
        }
        
    }
    
    /**
     * Cria as linhas de carregamento dos modelos necess�rios para o teste
     * 
     * return String
     */
    protected static function _factoryPrepareTestsModelLoad(Model $model){
        
        $ret = array();
        
        self::$_factoryPrepareTestsModelLoadList[] = $model->getName();
        
        // Para todos os campos FK faz chamadas recursivas ao m�todo
        foreach($model->getSections() as $section){
            
            foreach($model->getFields($section) as $field){
                if(ModelConfig::checkType($field, ModelConfig::FK)){
                    
                    $modelFk = null;
                    
                    try {
                        $modelFk = Model::factory( $field->getTable() );
                    } catch (Exception $e){
                        if(stripos($field->getTable(), '_') !== false){
                            list($m, $s) = explode("_", $field->getTable());
                            $modelFk = Model::factory( $m, $s );
                        }
                        if(!$modelFk){
                            throw $e;
                        }
                    }
                    
                    if(in_array($modelFk->getName(), self::$_factoryPrepareTestsModelLoadList) == false){
                        Util::log("recursive: " . $modelFk->getName());
                        self::$_factoryPrepareTestsModelLoadList[] = $modelFk->getName();
                        
                        $tmp = self::_factoryPrepareTestsModelLoad($modelFk);
                        
                        foreach($tmp as $key => $str){
                            $ret[$key] = $str;
                        }
                    }
                }
            }
        }
        
        list($schema, $name) = explode(".", $model->getName());
        
        // Retorna o c�digo de carregamento do model atual
        $xmlFilename = "../app/modules/{$schema}/model/xml/{$name}.xml";
        
        $classe = str_replace("Controller", "", Util::getControllerClassName($model));
        
        $ret[$model->getName()] = '$model'.$classe.' = $this->_modelLoad(file_get_contents("'.$xmlFilename.'"));';
        
        return $ret;
    }
    
    /**
     * Normaliza��o do nome da classe para o m�todo _factoryCheckClass
     * @param String $class
     * @return String
     */
    protected static function _factoryCheckClassName($class){
        $class = str_replace("_", " ", $class);
        $class = str_replace("-", "  ", $class);
        $class = ucwords($class);
        $class = str_replace("  ", "_", $class);
        $class = str_replace(" ", "", $class);
        return $class;
    }
    
    /**
     * Cria um nome de classe em camel case e verifica se o arquivo/classe existe
     * 
     * @param String $name Nome da classe dispon�vel ou NULL caso n�o esteja dispon�vel
     */
    protected static function _factoryCheckClass($modelDir, $class){
        
        $ret = null;
        
        $class = self::_factoryCheckClassName($class);
        
        if(!class_exists($class)){
            
            $filename = $modelDir . $class . ".php";
            
            if(is_file($filename)){
                require_once $filename;
            }
            
        }
        
        if(class_exists($class)){
            $ret = $class;
        }
        
        return $ret;
    }
    
    /**
     * Cria um novo objeto model diretamente a partir de uma defini��o de um arquivo XML
     * 
     * @param String $filename Nome do arquivo XML
     * @param String $section String do nome da section
     * 
     * @return Model
     */
    public static function factoryXml($filename, $section = null){
        
        if(!is_file($filename) || !is_readable($filename)){
            throw new Exception(__CLASS__.": N�o foi poss�vel localizar o arquivo '{$filename}' para criar o Model");
        }
        
        $xml = file_get_contents($filename);
        
        if(!strlen($xml)){
            throw new Exception(__CLASS__.": O arquivo '{$filename}' n�o possui conte�do.");
        }
        
        // Cria o novo objeto
        $model = new self($xml, $section);
        
        return $model;
    }
    
    /**
     * Cria um novo objeto model diretamente a partir de uma defini��o de uma string XML
     * 
     * @param String $xml Conte�do da String XML
     * @param String $section String do nome da section
     * 
     * @return Model
     */
    public static function factoryXmlString($xml, $section = null){
        
        // Cria o novo objeto
        $model = new self($xml, $section);
        
        return $model;
    }
    
    /**
     * Desativa o autocommit iniciando uma nova transa��o (o padr�o do sistema � autocommit ativo)
     */
    public static function beginTransaction(){
        self::$_transactionStarted = true;
        Table::getDefaultAdapter()->beginTransaction();
    }
    
    /**
     * D� o commando de rollback numa transa��o iniciada
     */
    public static function rollbackTransaction(){
        Table::getDefaultAdapter()->rollBack();
        self::$_transactionStarted = false;
    }
    
    /**
     * D� o commando de commit numa transa��o
     */
    public static function commitTransaction(){
        Table::getDefaultAdapter()->commit();
        self::$_transactionStarted = false;
    }
    
    /**
     * Retorna se h� uma transa��o em andamento
     * 
     * @return boolean
     */
    public static function transactionStarted(){
        return self::$_transactionStarted;
    }
    
    /**
     * Retorna se o modelo atual � de uma subse��o
     * 
     * @return boolean
     */
    public function isSubsection(){
        list($schema, $name) = explode(".", $this->getName());
        
        if($this->getSection()->getSection() !== null){
            return true;
        }
        
        return false;
    }
    
    /**
     * Gera a tabela correspondente � se��o desse modelo
     * 
     * @return Table
     */
    public function getTable(){
        
        list($schema, $name) = explode(".", $this->getSection()->getTableName());
        
        $table = Table::factory($name, $schema);
        
        return $table;
    }
    
    /**
     * Gera o nome tabela correspondente � se��o desse modelo
     * 
     * @return String
     */
    public function getTableName(){
        return $this->getTable()->getName();
    }
    
    /**
     * Retorna o conte�do XML do objeto atual
     *
     * @return String
     */
    public function getXml(){
        return $this->_xml;
    }
    
    /**
     * Retorna o conte�do XML do objeto atual
     *
     * @return String
     */
    public function getName(){
        return $this->_name;
    }
    
    /**
     * Retorna um objeto de representa��o da se��o
     * 
     * @param String $name
     * 
     * @return ModelSection
     */
    public function getSection($section = null){
        if($section == null){
            $section = $this->_section;
        }
        return $this->_sections[$section];
    }
    
    /**
     * Retorna a lista de se��es
     * 
     * @return ModelSection[]
     */
    public function getSections($excludeMainSection = false){
        reset($this->_sections);
        if($excludeMainSection === true){
            return array_slice($this->_sections, 1);
        }
        return $this->_sections;
    }
    
    /**
     * Retorna a lista de subse��es
     * 
     * @return ModelSection[]
     */
    public function getSubsections($excludeMainSection = false){
        return $this->getSections(true);
    }
    
    /**
     * Retorna um Field da se��o escolhida (ou a atual, caso $section == null)
     * 
     * @param String $name
     * @param String|ModelSection $section
     * 
     * @return ModelField
     */
    public function getField($name, $section = null){
        if($section == null){
            $section = $this->_section;
        }
        if($section instanceof ModelSection){
            $section = $section->getName();
        }
        $ret = null;
        if(isset($this->_fields[$section])){
            foreach($this->_fields[$section] as $field){
                if($field->getName() == $name){
                    $ret = $field;
                }
            }
        }
        return $ret;
    }
    
    /**
     * Retorna a lista de Fields de uma se��o
     * 
     * @param String|ModelSection $section
     * 
     * @return ModelField[]
     */
    public function getFields($section = null){
        if($section == null){
            $section = $this->_section;
        }
        if($section instanceof ModelSection){
            $section = $section->getName();
        }
        return $this->_fields[$section];
    }
    
    /**
     * Intepreta um arquivo XML
     * 
     * @param String $filename
     */
    public function parseXmlFile($filename){
        
        if(is_file($filename)){
            $content = file_get_contents($filename);
            $this->parseXml($content);
            return true;
        }
        
        return false;
    }
    
    /**
     * Interpreta o conte�do XML em formato de String
     * 
     * @param String $content
     */
    public function parseXml($content){
        
        $this->_xml = $content;
        
        // Cria��o do objeto DOM do xml recebido
        $doc = new DOMDocument();
        @$doc->loadXML( $content );
        
        // Verifica se a cria��o do elemento se deu com sucesso
        if($doc->documentElement == null){
            throw new Exception(__CLASS__.": O conte�do XML n�o � v�lido."); 
        }
        
        // Faz o processamento da primeira se��o do modelo o processamento
        // das outras ser� feito de forma recursiva pelo mesmo m�todo
        $this->_parseXmlSection( $doc->documentElement ); 
    }
    
    /**
     * Interepreta um modelo em XML e preenche os metadados do objeto atual de acordo
     * 
     * @param DOMElement $element
     */
    protected function _parseXmlSection(DOMElement $element, ModelSection $section = null){
        
        $xpath = new DOMXPath( $element->ownerDocument );
        
        // Faz uma valida��o para garantir que um Model s�
        // tenha uma Section na raiz do XML
        if($section == null){
            $nodeset = $xpath->query("/section", $element);
            if($nodeset->length > 1){
                throw new Exception(__CLASS__.": Mais de um elemento section foi encontrado no primeiro n�vel do XML.");
            }
            // Caso 
            else if($nodeset->length == 0) {
                throw new Exception(__CLASS__.": Nenhum elemento section foi encontrado no primeiro n�vel do XML.");
            }
        }
        
        // Cria o registro principal dessa se��o
        $attributes = $this->_parseXmlSectionListAttributes( $element );
        $newSection = new ModelSection($attributes);
        
        // Se for a primeira section define o schema.name como o nome do modelo
        if($section == null){
            $this->_name = $newSection->getSchema() . "." . $newSection->getName();
        }
        
        // Se for uma subse��o define a Section dela
        if($section != null && $section instanceof ModelSection){
            $newSection->setSection($section);
        }
        
        // Adiciona a se��o 
        $this->_parseXmlSectionAddSection($newSection);
        
        // Cria todos os campos dessa se��o
        $nodeset = $xpath->query("./field", $element);
        
        for($i = 0; $i < $nodeset->length; $i++){
            $attributes = $this->_parseXmlSectionListAttributes( $nodeset->item($i) );
            $this->_parseXmlSectionAddField(new ModelField($attributes), $newSection);
        }
        
        // Verifica se h� se��es filhas e faz a chamada recursiva do m�todo
        $nodeset = $xpath->query("./section", $element);
        for($i = 0; $i < $nodeset->length; $i++){
            $this->_parseXmlSection($nodeset->item($i), $newSection);
        }
    }
    
    /**
     * Retorna uma array com a lista de atributos de um elemento DOM qualquer
     *
     * @param DOMElement $element
     */
    protected function _parseXmlSectionListAttributes(DOMElement $element){
        $ret = array();
        foreach($element->attributes as $attr){
            $ret[$attr->name] = utf8_decode($attr->value);
        }
        return $ret;
    }
    
    /**
     * Adiciona uma se��o � lista das se��es do objeto atual
     * @param ModelSection $section
     * @throws Exception
     */
    protected function _parseXmlSectionAddSection(ModelSection $section){
        
        if(count($this->_sections) > 0){
            foreach($this->_sections as $s){
                if($section->getName() == $s->getName()){
                    throw new Exception("It is not possible to have two sections (".$s->getName().") with same name on same model.");
                }
            }
        }
        
        $this->_sections[$section->getName()] = $section;
        
        if(!isset($this->_fields[$section->getName()])){
            $this->_fields[$section->getName()] = array();
        }
    }
    
    /**
     * Adiciona um campo � determinada se��o
     * 
     * @param ModelField $field
     * @param ModelSection $section
     * @throws Exception
     */
    protected function _parseXmlSectionAddField(ModelField $field, ModelSection $section){
        
        foreach($this->_fields[ $section->getName() ] as $f){
            if($field->getName() == $f->getName()){
                throw new Exception("It is not possible to have two fields (".$f->getName().") with same name on same model.");
            }
        }
        
        $this->_fields[ $section->getName() ][] = $field;
    }
    
    /**
     * Verifica se um nome qualquer � um campo desse modelo
     *  
     * @param String $name Aceita-se o nome do campo no modelo ou o nome na base de dados, para o caso de FK
     * 
     * @return boolean true caso seja um dos campos desse modelo
     */
    public function isField($name){
        
        $ret = false;
        
        $name = trim($name);
        
        foreach($this->_fields[ $this->getSection()->getName() ] as $field){
            if($field->getName() == $name || $field->getNameDatabase() === $name || $name === "id"){
                $ret = true;
            }
        }
        
        // Coluna id sempre faz parte
        if($name === "id"){
            $ret = true;
        }
        
        // Caso seja uma subse��o a coluna com o nome da se��o superior tamb�m faz parte
        if($this->getSection()->getSection() !== null){
            if($name === "id_".$this->getSection()->getSection()->getName()){
                $ret = true;
            }
        }
        
        return $ret;
    }
    
    /**
     * Verifica se uma coluna � o nome de um campo FK desse modelo
     *
     * @param String $name Aceita-se o nome do campo no modelo ou o nome na base de dados, para o caso de FK
     *
     * @return boolean true caso seja um campo FK desse modelo
     */
    public function isFieldFkName($name){
        
        $ret = false;
        
        $name = trim($name);
        
        foreach($this->_fields[ $this->getSection()->getName() ] as $field){
            if(ModelConfig::checkType($field, ModelConfig::FK)){
                if($field->getName() == $name){
                    $ret = true;
                }
            }
                
        }
        
        return $ret;
    }
    
    /**
     * Faz o left join de todos os campos FK
     * 
     * @param Zend_Db_Select $select
     */
    protected function _prepareSelectFk(Zend_Db_Select $select, $colsSelect = array()){
        
        // Adiciona os JOIN LEFT dos campos que estejam em $cols e sejam FK
        foreach($this->getFields() as $field){
            
            if(ModelConfig::checkType($field, ModelConfig::FK)){
                
                // S� permite que o campo seja selecionado se estiver na lista
                // de $cols (caso essa esteja definida com itens)
                if(count($colsSelect) > 0 && !in_array($field->getName(), $colsSelect)){
                    continue;
                }
                
                $schema = null;
                $table = $field->getTable();
                
                // Separa o schema e table da defini��o do campo
                if(stripos($table, ".") !== false){
                    list($schema, $table) = explode(".", $table);
                }
                
                // Caso n�o exista schema na defini��o do campo considera o mesmo do modelo atual
                if($schema == null){
                    list($schema, $_tmp) = explode(".", $this->getName());
                }
                
                // Obt�m o from da consulta atual
                $from = $select->getPart(Zend_Db_Select::FROM);
                
                // Cria um alias para que o ON do JOIN n�o se confunda
                // no SQL com outros campos da mesma tabela
                $table = str_replace("\"", "", $table);
                $table = array($table."_".$field->getName() => $table);
                
                $cols = array($field->getName() => $field->getField());
                
                // Caso o nome tenha sido explicitamente colocado no $cols do prepareSelect
                // faz com que o ID da coluna sempre seja colocado na query
                if(count($colsSelect) > 0){
                    list($tableSchema, $tableName) = explode(".", $this->getTable()->getName()); 
                    $cols[] = $tableName . "." . $field->getNameDatabase();
                }
                
                $cond = "\"" . key($table) . "\"." . $field->getKey() . ' = ' . $this->getTable()->getName() . '.' . $field->getNameDatabase();
                
                $select->joinLeft($table, $cond, $cols, $schema);
            }
        }
        
    }
    
    /**
     * Prepara uma query no sistema
     * 
     * @param String[] $cols Lista das colunas a serem selecionadas
     * @param String[] $where Lista de par�metros where a serem aplicados 
     * @param String $sortCol
     * @param String $sortDir
     * @param int $count
     * @param int $offset
     * 
     * @return Zend_Db_Select
     */
    public function prepareSelect($cols = array(), $where = null, $sortCol = null, $sortDir = null, $count = null, $offset = null){
        
        if(!is_array($cols) && $cols !== null){
            $cols = array($cols);
        }
        
        $table = $this->getTable();
        
        $select = new Zend_Db_Select( $table->getAdapter() );
        
        $colsFrom = $cols;
        
        // Caso n�o haja uma defini��o de colunas faz com que todas as colunas do modelo sejam selecionadas
        if(count($cols) == 0){
            $colsFrom[] = 'id';
            // Caso seja uma subse��o faz com que a chave para a se��o
            // superior seja selecionada na consulta
            if($this->getSection()->getSection() !== null){
                $colsFrom[] = 'id_' . $this->getSection()->getSection()->getName();
            }
            // Adiciona � consulta todos os campos que tenham correspondente na
            // estrutura de dados, camposm do tipo tab e separator, por exemplo
            // n�o s�o adicionados
            foreach($this->getFields() as $field){
                if($field->getNameDatabase() !== false){
                    $colsFrom[] = $field->getNameDatabase();
                }
            }
        }
        
        // Verifica se alguma das colunas a serem selecionadas n�o fazem parte dos campos do modelo
        // nesse caso trata as mesmas como sendo express�es Zend_Db_Expr para permitir que se repasse
        // exatamente o que o desenvolvedor quer para o select
        foreach($colsFrom as $key => $col){
            if($this->isField($col) === false){
                $colsFrom[$key] = new Zend_Db_Expr("({$col})");
            }
        }
        
        // Impede que os nomes dos campos FK sejam colocados diretamenta na query SQL
        // essas colunas estar�o dispon�veis na query mas ser�o colocadas atrav�s do
        // m�todo _prepareSelectFk
        foreach($colsFrom as $key => $col){
            if($this->isFieldFkName($col) === true){
                unset($colsFrom[$key]);
            }
        }
        
        // M�todo padr�o do Zend Framework
        $select->from($table->getName(), $colsFrom);
        
        // Prepara os joins dos campos FK que est�o na consulta para que seus valores sejam obtidos
        $this->_prepareSelectFk($select, $cols);
        
        // Where
        if($where != null){
            if(!is_array($where)){
                $where = array($where);
            }
            
            $select->where(implode(" AND ", $where));
        }
        
        // Ordena��o da consulta
        if($sortCol){
            if(!$sortDir){
                $sortDir = 'ASC';
            }
            $sortColField = $this->getField($sortCol);
            if($sortColField){
                // Campos do tipo FK s�o ordenados pela coluna de nome e n�o pela chave
                if( ModelConfig::checkType($sortColField, ModelConfig::FK) ){
                    $sortCol = $sortColField->getField();
                }
                // Todos os outros s�o ordenados pelo pr�prio valor
                else {
                    $sortCol = $this->getTableName().".".$sortColField->getNameDatabase();
                }
            }
            $select->order(array($sortCol.' '.$sortDir));
        }
        
        // Defini��o do limit e ofsset se necess�rio
        if($count || $offset){
            $select->limit($count, $offset);
        }
        
        return $select;
    }
    
    /**
     * Seleciona um registro
     * 
     * @param int $id
     * @return String[]
     */
    public function select($id){
        
        $select = $this->prepareSelect(array(), array($this->getTable()->getName() . '.id = '. $id), null, null, 1, 0);
        
        $item = current($select->query()->fetchAll());
        
        return $item;
    }
    
    /**
     * Filtra um array associativo deixando passar somente aqueles valores
     * realmente s�o campos do modelo em quest�o 
     * 
     * @param String[][] $item
     * 
     * @return String[][] $item s� com campos v�lidos do modelo
     */
    protected function _itemFilter($item){
        
        $ret = array();
        
        $list = array('id');
        
        // Campos do modelo
        foreach($this->getFields() as $field){
            if($field->getNameDatabase() !== false){
                $list[] = $field->getNameDatabase();
            }
        }
        
        // Caso seja uma subse��o insere a chave para a se��o superior
        if($this->getSection()->getSection() !== null){
            $list[] = 'id_' . $this->getSection()->getSection()->getName();
        }
        
        // Filtra todos os campos do modelo
        foreach($list as $name){
            if(isset($item[$name])){
                $ret[$name] = $item[$name];
            }
        }
        
        // Faz modifica��es espec�ficas dos tipos de campos
        foreach($this->getFields() as $field){
            
            $name = $field->getNameDatabase();
            
            // S� prossegue caso o campo esteja definido e n�o seja um Zend_Db_Expr
            if($name !== false && isset($ret[$name]) && !($ret[$name] instanceof Zend_Db_Expr)){
                
                $type = ModelConfig::getType( $field->getType() );
                
                // Campo FK - Regra espec�fica com a implementa��o do salvamento de um registro
                // novo diretamente pelo formul�rio de refer�ncia (atributo insert do XML de defini��o)
                if(ModelConfig::checkType($field, ModelConfig::FK)){
                    // Caso seja um campo de inclus�o direto pelo select2 faz a inser��o do registro
                    // pelo m�todo padr�o do Model e insere a refer�ncia para o ID inserido
                    if( $field->getInsert() ){
                        if(isset($item[$field->getName()."__insert"])){
                            $itemFieldFk = array($field->getField(false) => $ret[$name]);
                            $ret[$field->getNameDatabase()] = Model::factory( $field->getTable() )->insert( $itemFieldFk );
                        }
                    }
                }
                
                // Caso haja um valor
                if(strlen(strval($ret[$name])) > 0){
                    // Executa a formata��o padr�o para o tipo definido
                    $modelFormatter = $type->getModelFormatter();
                    $ret[$name] = $modelFormatter($ret[$name]);
                }
                // Caso n�o haja valor definido redefine para NULL
                else {
                    $ret[$name] = new Zend_Db_Expr("NULL");
                }
            }

            // OPTION - Somente redefine o valor caso a flag de desmarca��o
            // esteja definida no $item quando o valor n�o est� definido com
            // o nome do campo esperado
            if(ModelConfig::checkType($field, ModelConfig::OPTION)){
                if(!isset($ret[$name]) && isset($item[$name."__unset"])){
                    $ret[$name] = 0;
                }
            }
        }
        
        return $ret;
    }
    
    /**
     * Define os valores padr�o dos campos caso eles j� n�o tenham
     * um valor definido e seja uma inser��o
     *
     * @param String[][] $item
     *
     * @return String[][] $item s� com campos v�lidos do modelo
     */
    protected function _itemDefaults($item){
        
        foreach($this->getFields() as $field){
            
            $name = $field->getNameDatabase();
            
            if($name !== false && ($field->getDefault() && (!isset($item[ $name ]) || strlen($item[ $name ]) === 0))){
                
                $default = $field->getDefault();
                
                if($default == "NOW()"){
                    $item[ $name ] = new Zend_Db_Expr( $default );
                } else if($default == "USER"){
                    $item[ $name ] = Zend_Auth::getInstance()->getIdentity()->id;
                } else {
                    $item[ $name ] = $field->getDefault();
                }
                
            }
        }
        
        return $item;
    }
    
    /**
     * Insere um novo registro
     * 
     * @param String[] $item Item a ser inserido
     * @return int Id do item inserido
     */
    public function insert($item){
        
        $item = $this->_itemFilter($item);
        
        $item = $this->_itemDefaults($item);
        
        $id = $this->getTable()->insert($item);
        
        return $id;
    }
    
    /**
     * Atualiza um registro
     * 
     * @param String[] $item
     * 
     * @return int Id do registro atualizado
     */
    public function update($item){
        
        $item = $this->_itemFilter($item);
        
        $rows = $this->getTable()->update($item, 'id = ' . $item['id']);
        
        if(!$rows){
            throw new Exception("Registro n�o atualizado.");
        }
        
        return $item['id'];
    }
    
    /**
     * Deleta registro com $id definido
     *  
     * @param int $id
     * 
     * @return boolean
     */
    public function delete($id){
        
        $rows = $this->getTable()->delete('id = ' . $id);
        
        if(!$rows){
            throw new Exception("Registro n�o exclu�do.");
        }
        
        return true;
    }
    
    /**
     * Valida que o argumento passado � uma referencia a um field desse model
     * @param String|ModelField $field Campo a ser validado
     * @return ModelField
     */
    protected function _validateField($field){
        
        $modelField = null;
        
        // Localiza o field correspondente
        if(is_string($field)){
            $modelField = $this->getField($field);
            
            // Tenta substituir o filter_ no in�cio do nome do campo e localiz�-lo
            // (a forma que componente de filtros da grid cria os nomes dos campos)
            if(!$modelField){
                $modelField = $this->getField(preg_replace("/^filter\_/", "", $field));
            }
        }
        
        // Caso o $field j� seja o ModelField
        if($field instanceof ModelField){
            $modelField = $field;
        }
        
        // Exce��o caso o ModelField n�o seja localizado
        if(!$modelField){
            throw new Exception(__METHOD__ . " - field '{$field}' n�o localizado no model.");
        }
        
        return $modelField;
    }
    
    /**
     * Prepara a busca por dados de um campo FK existente no modelo
     * @param String|ModelField $field Campo a ser buscado
     * @throws Exception
     * 
     * @return Zend_Db_Select
     */
    public function prepareSelectFk($field){
        
        // Valida o campo recebido
        $field = $this->_validateField($field);
        
        // Faz a cria��o do model ao qual a FK � relacionada
        $table = $field->getTable();
        
        $model = null;
        
        try {
            $model = Model::factory( $table );
        } catch (Exception $e){
            if(stripos($table, '.') !== false){
                list($m, $s) = explode("_", $table);
                $model = Model::factory( $m, $s );
            }
            if(!$model){
                throw $e;
            }
        }
        
        // Prepara as colunas do retorno no formato diretamente aceito pelo selec2
        $cols = array('id' => $field->getKey(), 'text' => $field->getField(false));
        
        // Prepara a consulta a ser executada
        $select = $model->prepareSelect($cols, null, "text", 'ASC');
        
        return $select;
    }
    
    /**
     * M�todo que realiza a busca por dados de um campo FK no sistema
     * 
     * @param String $field Nome do field
     * @param String $q Termo da busca
     * 
     * @return Zend_Db_Select
     */
    public function fkSearch($field, $q = null){
        
        $select = $this->prepareSelectFk($field);
        
        if($q !== null && strlen($q) > 0){
            $operator = "LIKE";
            
            // Usa o operador ILIKE no postgres
            if(Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_PGSQL'){
                $operator = "ILIKE";
            }
            
            $colText = null;
            foreach($select->getPart(Zend_Db_Select::COLUMNS) as $col){
                if($col[2] == 'text'){
                    $colText = $col[1];
                }
            }
            
            $select->where('strip_accents(' . $colText . ')' . " {$operator} strip_accents('%{$q}%') ");
        }
        
        return $select;
    }
    
    /**
     * M�todo que realiza a busca por dados de uma dependencia de FK no sistema
     * 
     * @param String $field Nome do field
     * @param int $value Valor do registro no qual h� a dependencia
     * 
     * @return Zend_Db_Select
     */
    public function fkDependency($field, $value){
        
        $select = $this->prepareSelectFk($field);
        
        // Obt�m o campo para usar os meta-dados do modelo
        $field = $this->_validateField($field);
        
        // Obt�m o campo da dependencia
        $fieldDepends = $this->_validateField( $field->getDepends() );
        
        // Obt�m a chave da dependencia
        $fieldDependsKey = $field->getDependsKey();
        
        // Caso n�o esteja explicitamente setada nos metadados do modelo
        // usa a defini��o padr�o 
        if(!$fieldDependsKey){
            if(ModelConfig::checkType($fieldDepends, ModelConfig::FK)){
                $fieldDependsKey = $fieldDepends->getKey()."_".$fieldDepends->getTable();
            } else {
                $fieldDependsKey = $fieldDepends->getNameDatabase();
            }
        }
        
        $select->where($fieldDependsKey." = ?", $value);
        
        return $select;
    }
    
}

/**
 * Class to represent a Section of a Model 
 */
class ModelSection extends AttributeList {
    
    /**
     * Constante do atributo
     * @var String
     */
    const NAME = 'name';
    
    protected $_section = null;
    
    /**
     * Define a lista de atributos obrigat�rios
     */
    protected function _required(){
        return array( self::NAME );
    }
    
    /**
     * (non-PHPdoc)
     * @see AttributeList::validate()
     */
    public function validate(){
        parent::validate();

        $regExp = "/^([A-Za-z0-\_]{1,})$/";
        
        if(!preg_match($regExp, $this->getName())){
            throw new Exception("The section name (".$this->getName().") must match this Regular Expression: ".$regExp);
        }
    }
    
    /**
     * Define a Section dessa Section (quando � uma subse��o)
     * 
     * @param ModelSection $section
     * @return ModelSection
     */
    public function setSection(ModelSection $section){
         $this->_section = $section;
         return $this;
    }
    
    /**
     * Obt�m a Section dessa Section (caso seja a primeira Section returna null)
     * @param ModelSection $section
     */
    public function getSection(){
         return $this->_section;
    }
    
    /**
     * Creates a new object
     * 
     * @param String $name
     * @param String $type
     * @throws Exception
     * 
     * @return ModelSection
     */
    public static function factory($name){
        return new self(array(self::NAME => $name));
    } 
    
    /**
     * Generates a table name of a ModelSection
     *
     * @param ModelSection $section
     * @return String
     */
    public function getTableName(){
    
        $schema = $this->getSchema();
        $name = $this->getName();
    
        $parentSection = $this->getSection();
    
        if(!$schema && $parentSection == null){
            throw new Exception(__CLASS__.": A main Section must have a schema definition.");
        }
    
        if($parentSection != null){
            $schema = $parentSection->getSchema();
            $name = $parentSection->getName() . "_" . $name;
        }
    
        $ret = $schema . "." . $name;
        
        return $ret;
    }
    
    /**
     * Returns the parent key of this section (if this one is a subsection)
     * 
     * @return ModelField|null
     */
    public function getParentKey(){
        $fk = null;
        
        if($this->getSection() != null){
            $section = $this->getSection();
            
            $fk = ModelField::factory( $section->getName() , 'fk');
            $fk->setTable( $section->getSchema().".".$section->getName() );
            $fk->setKey( 'id' );
            $fk->setField( $section->getField() ? $section->getField() : 'id' );
            $fk->setFkOnDelete('CASCADE');
            
        }
        
        return $fk;
    }
    
    /**
     * Obt�m o atributo
     * @return String
     */
    public function getName(){ return $this->__get( self::NAME ); }
}

/**
 * Class to represent a Field of a Model
 */
class ModelField extends AttributeList {
    
    /**
     * Constantes dos atributos
     * @var String
     */
    const TYPE = 'type';
    /**
     * Constantes dos atributos
     * @var String
     */
    const NAME = 'name';
    /**
     * Constantes dos atributos
     * @var String
     */
    const KEY = 'key';
    /**
     * Constantes dos atributos
     * @var String
     */
    const TABLE = 'table';
    
    /**
     * Regular Expression to validate the field's name
     * @var String
     */
    protected $_validateRegExp = "/^([A-Za-z0-\_]{1,})$/";
    
    /**
     * Define a lista de atributos obrigat�rios
     */
    protected function _required(){
        return array('type', 'name');
    }

    /**
     * Creates a new object
     * 
     * @param String $name
     * @param String $type
     * @throws Exception
     * 
     * @return ModelField
     */
    public static function factory($name, $type){
        return new self(array('name' => $name, 'type' => $type));
    }
    
    /**
     * (non-PHPdoc)
     * @see AttributeList::validate()
     */
    public function validate(){
        parent::validate();
    
        if($this->_validateRegExp !== null && !preg_match($this->_validateRegExp, $this->getName())){
            throw new Exception("The field name (".$this->getName().") must match this Regular Expression: ".$this->_validateRegExp);
        }
    }
    
    /**
     * Returns the field name in the storage system
     * (may be diffent from the field`s name in some cases)
     */
    public function getNameDatabase(){
        
        // Visualization types don`t have a database name
        $type = ModelConfig::getType($this->getType());
        if( $type->getType() === ModelConfig::TYPE_VIEW ){
            return false;
        }
        
        return ModelConfig::name($this);
    }
    
    /**
     * Returns the field attribute of this field that is used in FK fields
     * 
     * @param $tableAliasReplace Indicates if the table name in the attribute must be replaced by the default table alias 
     * 
     * @return Zend_Db_Expr
     */
    public function getField($tableAliasReplace = true){
        
        $ret = parent::getField();
        
        // Verifica se o atributo field � uma express�o e retorna um Zend_DB_Expr ao inv�s
        if(ModelConfig::checkType($this, ModelConfig::FK)){
            
            try {
                
                $table = $this->getTable();
                $schema = "";
                
                // Se h� a defini��o de schema no atributo table separa 
                if(stripos($table, ".") !== false){
                    list($schema, $table) = explode(".", $table);
                }
                
                $tableAlias = $table."_".$this->getName();
                
                $modelFk = Model::factory( ($schema ? $schema."." : "") . $table );
                
                if( $modelFk != null ){
                    
                    // Caso o atributo field do campo n�o seja um campo do modelo referenciado
                    $isField = $modelFk->isField( $ret );
                    
                    // Substitui poss�veis refer�ncias ao nome da tabela para referenciar
                    // a alias criada em situa��es de m�ltiplas refer�ncias para outra tabela
                    if($tableAliasReplace === true){
                        $ret = str_replace($modelFk->getName().".", $tableAlias.".", $ret);
                        $ret = str_replace($table.".", $tableAlias.".", $ret);
                        
                        // Se n�o existir uma defini��o de tabela no nome do campo faz com que seja
                        // colocada com o nome padr�o do alias da tabela
                        if(stripos($ret, ".") === false){
                            $ret = $tableAlias.".".$ret;
                        }
                    }
                    
                    // Substitui a defini��o original caso campo n�o seja uma refer�ncida direta
                    // a um campo do modelo referenciado pela FK, considerando que � uma express�o
                    if(!$isField){
                        $ret =  new Zend_Db_Expr("(".$ret.")");
                    }
                }
                
            }
            catch (Exception $e){
                // Ignora a exce��o para permitir que um campo referencie uma tabela n�o necessariamente do modelo atual
            }
            
        }
        
        return $ret;
    }

    /**
     * Obt�m o atributo
     * @return String
     */
    public function getName(){ return $this->_attributes[self::NAME]; }

    /**
     * Obt�m o atributo
     * @return String
     */
    public function getKey(){ return $this->__get( self::KEY ); }

    /**
     * Obt�m o atributo
     * @return String
     */
    public function getTable(){ return $this->__get( self::TABLE ); }

    /**
     * Obt�m o atributo
     * @return String
     */
    public function getType(){ return $this->__get( self::TYPE ); }
    
}





