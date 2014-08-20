<?php

require_once 'PageInternal.php';

/**
 * Classe para representar um formul�rio no sistema
 *
 * @copyright Eramo Software
 */
class Form extends PageInternal {
    
    /** JSON - Define um objeto JSON com o registro para que o formul�rio seja preenchido */
    const OPTION_RECORD = 'record';
    /** String - Define a URL que ser� chamada via AJAX para salvar dados do Form */
    const OPTION_ACTION = 'action';
    /** String - Define uma fun��o javascript do escopo que ser� chamada depois do salvamento (sempre em caso de sucesso)  */
    const OPTION_SUBMIT_CALLBACK = 'submitCallback';
    /** String - Define um container que armazenar� os bot�es adicionados ao objeto  */
    const OPTION_BUTTONS_PAGE_CONTAINER = 'buttonsPageContainer';
    /** int - Define a largura fixa da coluna das labels dos campos  */
    const OPTION_LABEL_COLUMN_WIDTH = 'labelColumnWidth';
    /** boolean - Determina se o foco do formul�rio ser� definido ou n�o ap�s seu carregamento */
    const OPTION_AUTOFOCUS = 'autofocus';
    /** boolean - Ativa o modo de visualiza��o do formul�rio */
    const OPTION_VIEW = 'view';
    /** String - Define par�metros extras do carregamento de Fk Dependency */
    const OPTION_FK_DEPENDENCY_PARAMS = 'fkDependencyParams';
    
    /**
     * Lista das colunas do grid
     * @var FormField
     */
    protected $_fields = array();
    
    /**
     * Lista dos campos ocultos desse formul�rio
     * @var String[]
     */
    protected $_fieldsHidden = array();
    
    /**
     * Lista de Options padr�o do componente
     * 
     * @var String[]
     */
    protected $_options = array(
                self::OPTION_ID => '#form',
                self::OPTION_TITLE => '',
                self::OPTION_CONTAINER => '#form',
                self::OPTION_ACTION => '',
                self::OPTION_AUTOFOCUS => true,
                self::OPTION_VIEW => false,
                self::OPTION_BASE_URI => ''
            );
    
    /**
     * Constr�i o objeto padr�o definindo uma lista de Options
     * 
     * @param String[] $options
     */
    public function __construct($options = array()){
        parent::__construct($options);
        
        $this->addJs('fenix/jquery.form.js');
        $this->addJs('fenix/parsley.js');
        $this->addJs('fenix/parsley.defaults.js');
        
        $this->addJs('fenix/jquery.maskedinput.js');
    }
    
    /**
     * Adiciona uma novo campo ao form
     * 
     * @param String $name Define o nome do campo
     * @param String $type Define o t�tulo do campo a ser exibido
     * @param String $title Define o t�tulo do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma fun��o javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addField($name, $type = null, $title = null, $formatter = null){
        $this->addFieldAt(null, $name, $type, $title, $formatter);
    }
    
    /**
     * Adiciona uma novo campo ao form
     *
     * @param int $pos Posi��o do campo no formul�rio
     * @param String $name Define o nome do campo
     * @param String $type Define o t�tulo do campo a ser exibido
     * @param String $title Define o t�tulo do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma fun��o javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addFieldAt($pos = null, $name, $type = null, $title = null, $formatter = null){
    
        if( !($name instanceof FormField) ){
            $name = FormField::factory($name, $type);
            
            $args = array('title', 'formatter');
            foreach($args as $arg){
                if($arg != null){
                    $method = "set" . $arg;
                    $name->$method($$arg);
                }
            }
        }
    
        if($pos === null){
            $pos = count($this->_fields);
        }
    
        $this->_fields = array_merge( array_slice($this->_fields, 0, $pos), array($name), array_slice($this->_fields, $pos) );
    
    }
    
    /**
     * Adiciona um novo campo ao form
     *
     * @param String $fieldName Define depois de qual campo a nova ser� inserida
     * @param String $name Define o nome do campo
     * @param String $type Define o t�tulo do campo a ser exibido
     * @param String $title Define o t�tulo do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma fun��o javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addFieldAfter($fieldName, $name, $type = null, $title = null, $formatter = null){
        $pos = $this->_getFieldPosition($fieldName) + 1;
        $this->addFieldAt($pos, $name, $type, $title, $formatter);
    }
    
    /**
     * Obt�m a posi��o de um campo no form
     *
     * @param String $name Nome do campo a ser localizado
     *
     * @return int
     */
    protected function _getFieldPosition($name){
        foreach($this->_fields as $key => $field){
            if($field->getName() == $name){
                return $key;
            }
        }
    
        return null;
    }
    
    /**
     * Remove um dos campos do formul�rio
     *
     * @param String|FormField $name
     * 
     * @return FormField
     */
    public function deleteField($name){
        
        $ret = null;
        
        if($name instanceof FormField){
            $name = $name->getName();
        }
        
        foreach($this->_fields as $key => $field){
            if($field->getName() == $name){
                $ret = $this->_fields[$key];
                unset($this->_fields[$key]);
            }
        }
        
        if($ret === null){
            throw new Exception("Field with name '".$name."' not found.");
        }
        
        $this->_fields = array_values($this->_fields);
        
        return $ret;
    }
    
    /**
     * Move um campo para determinada posi��o
     *
     * @param int|String $pos Posi��o ou nome do campo a inserir depois
     * @param FormField|String $field
     * 
     */
    public function moveField($pos, $field){
        
        if(is_string($pos)){
            foreach($this->_fields as $key => $f){
                if($f->getName() == $pos){
                    $pos = $key+1;
                }
            }
        }
        
        if($pos > count($this->_fields) || !is_numeric($pos)){
            $pos = count($this->_fields);
        }
        
        $this->addFieldAt($pos, $this->deleteField($field));
    }
    
    /**
     * Obt�m um campo do form
     *
     * @param String $name Nome da campo a ser localizada
     *
     * @return FormField
     */
    public function getField($name){
        foreach($this->_fields as $field){
            if($field->getName() == $name){
                return $field;
            }
        }
    
        return null;
    }
    
    /**
     * Retorna a lista de campos do formul�rio
     *
     * @return FormField[]
     */
    public function getFields(){
        return $this->_fields;
    }
    
    /**
     * Define um atributo de um campo
     *
     * @param String $name
     * @param String $attr
     * @param String $value
     */
    public function setFieldAttribute($name, $attr, $value){
        $field = $this->getField($name);
        if($field != null){
            $field->__set($attr, $value);
        } else {
            trigger_error(__METHOD__ . ": Imposs�vel localizar campo {$name}");
        }
    }
    
    /**
     * Define o valor de um campo do formul�rio
     *
     * @param String $name
     * @param String $value
     */
    public function setFieldValue($name, $value){
        if(!isset($this->_options['record']) || !is_array($this->_options['record'])){
            $this->_options['record'] = array();
        }
        $this->_options['record'][$name] = $value;
    }
    
    /**
     * Obt�m um atributo de um campo
     *
     * @param String $name
     * @param String $attr
     */
    public function getFieldAttribute($name, $attr){
        $field = $this->getField($name);
        if($field != null){
            return $field->getAttribute($attr);
        } else {
            trigger_error(__METHOD__ . ": Imposs�vel localizar campo {$name}");
        }
        return null;
    }
    
    /**
     * Adicionar um campo oculto na postagem do formul�rio
     * 
     * @param String $name
     * @param String $value
     * 
     */
    public function addHiddenField($name, $value){
       $this->_fieldsHidden[] = array('name' => $name, 'value' => $value);  
    }
    
    /**
     * Faz a prepara��o dos campos para a renderiza��o
     */
    protected function _prepareRenderFormField(){
        
        $fields = $this->_fields;
        
        foreach($fields as $field){
            
            // Para campos sem tipo (geralmente definidas posteriomente a cria��o do modelo)
            // ignora esse processamento
            if(!$field->getType()){
                continue;
            }
            
            $type = ModelConfig::getType($field->getType());
            
            // Caso n�o haja a defini��o expl�cita de um formatter
            // nesse campo utiliza a padr�o desse tipo no sistema
            if(!$field->getFormatter()){
                if($this->getOption(self::OPTION_VIEW) || $field->getForm() == 'v'){
                    if(!$type->getFormatterFormView()){
                        $field->setFormatter("$.fn.form.formatter.defaultView");                        
                    } else {
                        $field->setFormatter($type->getFormatterFormView());
                    }
                } else {
                    $field->setFormatter($type->getFormatterForm());                    
                }
                
            }
            
            // Caso haja a defini��o de um JS espec�fico para o tipo o inclui no carregamento da p�gina
            if( $type->getJs() ){
                
                $this->addJs( $type->getJs() );
            }
            
            // Caso haja a defini��o de um CSS espec�fico para o tipo o inclui no carregamento da p�gina
            if( $type->getCss() ){
                
                $this->addCss( $type->getCss() );
            }
            
            // Define o nome na base de dados como sendo um atributo a mais
            // para permitir que o javascript acesse esse valor
            $field->setNameDatabase ( $field->getNameDatabase() );
            
            // Num campo do tipo FK faz o carregamento dos dados diretamente pelo modelo referenciado
            if(ModelConfig::checkType($field, ModelConfig::FK)
            && $field->getData() === null // caso o atributo data n�o tenha sido definido 
            && $field->getRemote() === null // n�o carrega dados no caso de pesquisa remota
            && $this->getOption(Form::OPTION_VIEW) !== true // n�o carrega dados no caso de formul�rio de visualiza��o
            && $field->getDepends() === null){ // n�o carrega dados no caso do campo ser dependente
                $data = null;
                // Se for um form baseado num model
                if( $this->getOption(Form::OPTION_MODEL) ){
                    $modelSection = $this->getOption(Form::OPTION_SECTION) ? $this->getOption(Form::OPTION_SECTION) : null;  
                    $model = Model::factory($this->getOption(Form::OPTION_MODEL), $modelSection);
                    // Se o field existir no model
                    if($model->getField( $field->getName() )){
                        $select = $model->prepareSelectFk($field);
                        $data = $select->query()->fetchAll();
                    }
                }
                // Caso n�o seja um Form baseado num model tenta selecionar os dados conforme especificado no field
                else if($data == null){
                    if(!$field->getField(false) || !$field->getKey()){
                        throw new Exception("Um campo do tipo FK num formul�rio precisa que seus atributos Field e Key estejam definidos.");
                    }
                    $model = Model::factory( $field->getTable() );
                    $select = $model->prepareSelect(array('id' => $field->getKey(), 'text' => $field->getField(false) ), null, $field->getField(false), "ASC");
                    $data = $select->query()->fetchAll();
                }
                
                if($data !== null){
                    $data = array_merge(array(array('id' => '', 'text' => '&nbsp;' )), $data);
                    $field->setData( $data );
                }
            }
            
            // Campos do tipo GRID s�o preparados para renderiza��o
            if(ModelConfig::checkType($field, ModelConfig::GRID)){
                
                $grid = $field->getGrid();
                
                if(!$grid instanceof Grid){
                    throw new Exception("O atributo grid de um campo do tipo ModelConfig::GRID precisa ser uma inst�ncia da classe Grid.");
                }
                
                // Obt�m a lista de CSS e JS para renderiza��o inline do grid
                $gridRender = $field->getGrid()->prepareRenderInline();
                
                // Inclui os arquivos CSS e JS no formul�rio principal
                $this->addCss($gridRender['css']);
                $this->addJs($gridRender['js']);
                
                $field->setGrid($gridRender['options']);
            }
        }
        
        // Verifica se h� formatters JS, no padr�o de defini��o de javascript para
        // os modelos, que ainda n�o tiveram seus arquivos JS inseridos no escopo
        // caso encontre o arquivo JS correspondente faz a inser��o na lista de Javascript
        $cmd = array();
        foreach($fields as $field){
            $cmd[] = $field->getFormatter();
        }
        $this->_parseJsLines($cmd);
        
        // Retorna os campos preparados para o formul�rio
        return $this->_prepareRenderAttributeList($fields);
    }
    
    /**
     * Prepara a cria��o do component de grid no Javascript e faz outras defini��es de renderiza��o
     */
    protected function _prepareRenderOptions(){
        
        $options = parent::_prepareRenderOptions();

        // Prepara as colunas para serem repassadas ao javascript
        $options['fields'] = $this->_prepareRenderFormField();
        
        // Prepara as colunas para serem repassadas ao javascript
        $options['fieldsHidden'] = $this->_fieldsHidden;
        
        $options = json_encode(Util::utf8_encode($options));
        
        // Faz ajustes no funcionamento do onload quando a tela � carregada via um XHR
        $this->_prepareRenderOptionsXhr();
        
        // Javascript padr�o de inicializa��o do componente jquery de form
        // adiciona como sendo o primeiro item do onload da p�gina
        $this->addOnload( "$('".$this->_checkOption(self::OPTION_CONTAINER)."').form(".$options.");", 0 );
        
        // Caso seja um XHR faz com que seja feita a chamada do callback do Fenix_Model.page depois
        // da cria��o do elemento
        if( $this->getOption(self::OPTION_XHR) ){
            $this->addOnload( " Fenix_Model._pageCallback(); " );
        }
        
        return $options;
    }
    
    /**
     * (non-PHPdoc)
     * @see Page::_prepareJsTests()
     */
    protected function _prepareJsTests(){
        
        // Faz o procedimento normal de gera��o de testes
        parent::_prepareJsTests();
        
        // Determina a lista completa de requires
        $requires = array();
        foreach($this->_js as $js){
            if(!in_array($js, $this->_jsDefault)){
                $requires[] = $this->_prepareFilesJoinRealFilename($js);
            }
        }
        
        // Diret�rio padr�o de salvamento
        $dirname = "app_tests/fenix/view/js/jquery.form/";
        
        // Prepara os testes de todos os formatters sendo utilizados nesse objeto Form
        foreach($this->getFields() as $field){
            
            // S� considera os formatters padr�o (do fenix)
            if(stripos($field->getFormatter(), "$.fn.form") !== false){
                
                $functionList = array();
                $functionList[] = $field->getFormatter() . " = function(text, record, field, form, table, tr, td)";
                
                // Faz a gera��o das chamadas
                
                $variableValues = array();
                $variableValues["text"] = "'@TODO'";
                $variableValues["record"] = "{}";
                $variableValues["field"] = json_encode(Util::utf8_encode($field->getAttributes()));
                $variableValues["form"] = "{}";
                $variableValues["table"] = "$('<table>').appendTo( $('body') )";
                $variableValues["tr"] = "$('<tr>').appendTo( $('table:last') )";
                $variableValues["td"] = "$('<td>').appendTo( $('tr:last') )";
                
                $this->_prepareJsTestsGenerateStubFunctionCall($dirname, $functionList, $requires, $variableValues);
                
            }
            
        }
        
    }
    
}

/**
 * Classe para representar um campo de um Form 
 */
class FormField extends PageField {
    
    /**
     * (non-PHPdoc)
     * @see AttributeList::_required()
     */
    protected function _required(){
        return array('name', 'type');
    }
    
    /**
     * 
     * @param String $name
     * @param String $title
     * @throws Exception
     * 
     * @return FormField
     */
    public static function factory($name, $type){
        return new self( array('name' => $name, 'type' => $type) );
    } 
    
    /**
     * Atribui um evento javascript a um elemento de formul�rio mapeando
     * o m�todo on do jquery
     * 
     * @param String $eventType
     * @param String $handler
     */
    public function on($eventType, $handler){
        
        if(!isset($this->_attributes['on'])){
            $this->_attributes['on'] = array();
        }
        
        if(!isset($this->_attributes['on'][$eventType])){
            $this->_attributes['on'][$eventType] = array();
        }
        
        $this->_attributes['on'][$eventType][] = $handler;
        
        return $this;
    }
}










