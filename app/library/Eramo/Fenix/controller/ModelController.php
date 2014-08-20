<?php

/**
 * Controlador padr�o dos modelos do sistema
 *
 * @copyright Eramo Software
 */
abstract class Fenix_ModelController extends Eramo_Controller_Action {
    
    /**
     * Inst�ncia da classe Model desse controller
     * 
     * @var Model
     */
    protected $_model = null;
    
    /**
     * Armazena a lista de options do grid
     * 
     * @var String[]
     */
    protected $_gridOption = array();
    
    /**
     * Armazena a lista de options do form
     * 
     * @var String[]
     */
    protected $_formOption = array();
    
    /**
     * Define se a pesquisa r�pida est� ativada
     * 
     * @var boolean
     */
    protected $_search = false;
    
    /**
     * Define uma lista de campos que ser�o pesquis�veis no modelo
     * 
     * @var String[]
     */
    protected $_searchFields = array();
    
    /**
     * Define uma lista de campos num�ricos que ser�o pesquis�veis no modelo
     * 
     * @var String[]
     */
    protected $_searchFieldsNumeric = array();
    
    /**
     * Constantes de operadores SQL
     * @var string
     */
    const DATA_OPERATOR_EQ = 'eq';
    const DATA_OPERATOR_NEQ = 'neq';
    const DATA_OPERATOR_GT = 'gt';
    const DATA_OPERATOR_LT = 'lt';
    const DATA_OPERATOR_BETWEEN = 'between';
    const DATA_OPERATOR_ISNULL = 'isnull';
    const DATA_OPERATOR_ISNOTNULL = 'isnotnull';
    const DATA_OPERATOR_CONTAINS = 'contains';
    const DATA_OPERATOR_NOTCONTAINS = 'notcontains';
    
    /**
     * Defini��o dos operadores SQL
     * @var mixed[]
     */
    protected $_dataOperator = array(
            self::DATA_OPERATOR_EQ => array('='),
            self::DATA_OPERATOR_NEQ => array('<>'),
            self::DATA_OPERATOR_GT => array('>='),
            self::DATA_OPERATOR_LT => array('<='),
            self::DATA_OPERATOR_ISNULL => array('IS NULL', false),
            self::DATA_OPERATOR_ISNOTNULL => array('IS NOT NULL', false),
            self::DATA_OPERATOR_BETWEEN => array('BETWEEN'),
            self::DATA_OPERATOR_CONTAINS => array('LIKE', "_dataOperatorLike"),
            self::DATA_OPERATOR_NOTCONTAINS => array('NOT LIKE', "_dataOperatorLike")
    );
    
    /**
     * Cria o objeto
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    public function __construct($module, $controller, $action){
        parent::__construct($module, $controller, $action);
    
        // Caso exista a defini��o de um model cria o objeto correspondente
        if($this->_model()){
            $this->_model = Model::factory( $this->_model(), $this->_section() );
    
            // Verifica se j� uma classe controller para esse modelo e redireciona
            $this->_checkControllerDefined();
    
            // Verifica as permiss�es de acesso
            if($this->_checkPermission() === false){
                if(Util::isXhr()){
                    throw new Fenix_Exception("Acesso negado.");
                } else {
                    require_once 'app/fenix/view/html/redir.html.php';
                    die();
                }
            }
        }
    
    }
    
    /**
     * Define o nome do Model correspondente a esse controller.
     * S� precisa ser definido num controller herdado caso ele 
     * n�o esteja no padr�o do sistema
     * 
     * @return String
     */
    protected function _model(){
        // Espera que o nome venha no cabe�alho (quando � criado diretamente em fenix/model/
        $model = isset($_REQUEST['_model']) ? $_REQUEST['_model'] : null;
        // Se n�o houver model definido verifica se a classe atual � uma espec�fica
        // e tenta localizar o model atrav�s do padr�o de nomenclatura da classe
        if($model === null){
            if(get_class($this) !== "Model_Controller"){
                $className = get_class($this);
                $className = str_replace("Controller", "", $className);
                $className = trim(preg_replace("/([A-Z]{1})/", " $1", $className));
                $model = strtolower(str_replace(" ", "_", $className));
            }
        }
        return $model;
    }
    
    /**
     * Define a Section do model a ser utilizada
     * 
     * @return String
     */
    protected function _section(){
        return isset($_REQUEST['_section']) ? $_REQUEST['_section'] : null;
    }
    
    /**
     * Retorna o objeto Model do controlador
     * 
     * @return Model
     */
    protected function _getModel(){
        return $this->_model;
    }
    
    /**
     * Obt�m a URI base do controller atual
     * 
     * @return String
     */
    protected function _getBaseUri(){
        return Util::getBaseUrl().$this->_module."/".$this->_controller."/";
    }
    
    /**
     * Checa permiss�es de acesso
     * @param int $level Uma das constantes de Fenix_Profile::PERMISSION_*
     */
    protected function _checkPermission($level = Fenix_Profile::PERMISSION_VIEW, Model $model = null){
        $ret = false;
        if($model === null){
            $model = $this->_getModel();
        }
        if(Zend_Auth::getInstance()->hasIdentity() === true && Fenix_Profile::checkPermission($model, $level) === true){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * Se for a classe gen�rica sendo instanciada verifica se h� um controller
     * espec�fico para o modelo em quest�o e faz o redirecionament para ele
     */
    protected function _checkControllerDefined(){
        
        if(get_class($this) == "ModelController"){
            list($module, $name) = explode(".", $this->_getModel()->getName());
            
            $controllerClassFilename = Util::getControllerClassFilename( $this->_getModel() );
            
            // Se n�o existir o arquivo da classe verifica se � um dos models padr�o do sistema
            if(!is_file($controllerClassFilename)){
                
                $name = null;
                
                // Model padr�o de arquivos
                if( $this->_getModel()->getName() == Util::getConfig(Util::CONFIG_FILE)->model ){
                    $name = "File";
                }
                
                // Model padr�o de perfis de usu�rios
                if( $this->_getModel()->getName() == Util::getConfig(Util::CONFIG_PROFILE)->model ){
                    $name = "Profile";
                }
                
                // Model padr�o de perfis de itens de menu
                if( $this->_getModel()->getName() == Util::getConfig(Util::CONFIG_AUTH)->model ){
                    $name = "User";
                }
                
                // Model padr�o de perfis de itens de menu
                if( $this->_getModel()->getName() == Util::getConfig(Util::CONFIG_MENU)->model ){
                    $name = "Menu";
                }
                
                // Se localizou um model
                if($name !== null){
                    $module = "fenix";
                    $controllerClassFilename = "app/fenix/controller/{$name}Controller.php";
                }
                
            }
            
            // Se existe o arquivo no padr�o esperado
            if(is_file($controllerClassFilename)){
        
                // Determina os par�metros do request
                $queryStringNew = array();
        
                $queryString = explode("&", $_SERVER['REDIRECT_QUERY_STRING']);
                foreach ($queryString as $item){
                    $itemParts = explode("=", $item);
                    if($itemParts[0] == '_model'){
                        continue;
                    }
                    $queryStringNew[] = $item;
                }
        
                $queryStringNew = implode("&", $queryStringNew);
        
                if(strlen($queryStringNew) > 0){
                    $queryStringNew = "?" . $queryStringNew;
                }
                
                // Define qual action est� sendo executada
                
                $requestUri = explode("?", $_SERVER['REQUEST_URI']);
                $requestUri = explode("/", $requestUri[0]);
                $action = end($requestUri);
                
                // Cria a nova URI
                $controllerName = Util::getControllerNameFromFile($controllerClassFilename);
                
                $uri = Util::getBaseUrl() ."{$module}/{$controllerName}/{$action}" . $queryStringNew;
                
                // Redireciona
                header("Location: " . $uri);
                die();
            }
        }
    }
    
    /**
     * Define a lista de campos do modelo que ser�o pesquis�veis no grid
     * @param String[] $fields
     * @param String $section
     */
    protected function _searchFields($fields, $section = '_default'){
        if(!isset($this->_searchFields[$section])){
            $this->_searchFields[$section] = array();
        }
        $this->_searchFields[$section] = array_merge($this->_searchFields[$section], $fields);
    }
    
    /**
     * Define a lista de campos do modelo que ser�o pesquis�veis no grid
     * @param String[] $fields
     * @param String $section
     */
    protected function _searchFieldsNumeric($fields, $section = '_default'){
        if(!isset($this->_searchFieldsNumeric[$section])){
            $this->_searchFieldsNumeric[$section] = array();
        }
        $this->_searchFieldsNumeric[$section] = array_merge($this->_searchFieldsNumeric[$section], $fields);
    }
    
    /**
     * Retorna se a pesquisa est� ativada para a se��o
     * 
     * @param string $section
     * @return boolean
     */
    protected function _searchActivated($section = '_default'){
        
        if($this->_getModel()->isSubsection() === false){
            $section = '_default';
        }
        
        // Caso haja campos na se��o marcados como busca adiciona-os �s listas de busca
        foreach($this->_getModel()->getFields() as $field){
            if($field->getSearchable() && $field->getNameDatabase()){
                
                if(ModelConfig::checkType($field, ModelConfig::FK) !== false){
                    
                    $this->_searchFields(array($field->getField()), $section);
                    
                } else if(ModelConfig::checkType($field, ModelConfig::DATE) !== false
                       || ModelConfig::checkType($field, ModelConfig::DATETIME) !== false){
                    
                    // @TODO Implementar pesquisa por data: T0842 - Grid - Busca - Permitir a busca por campos de data
                    
                } else if(ModelConfig::checkTypeNumeric($field) === false){
                    
                    $this->_searchFields(array($this->_getModel()->getTableName().".".$field->getNameDatabase()), $section);
                    
                } else {
                    
                    $this->_searchFieldsNumeric(array($this->_getModel()->getTableName().".".$field->getNameDatabase()), $section);
                    
                }
            }
        }
        
        // Faz a verifica��o para listagem dos campos que tem busca ativada
        $ret = isset($this->_searchFields[$section]) || isset($this->_searchFieldsNumeric[$section]);
         
        return $ret;
    }
    
    /**
     * Define uma Option do grid
     * 
     * @param String $option Uma das constantes em Grid::OPTION_*
     * @param mixed $value
     * @param boolena $overwrite O padr�o � sobrescrever uma defini��o j� feita, define-se com false para dar a op��o de n�o faz�-lo 
     */
    protected function _setGridOption($option, $value, $overwrite = false){
        if(!isset($this->_gridOption[$option]) || $overwrite == true){
            $this->_gridOption[$option] = $value;
        }
    }
    
    /**
     * Obt�m uma defini��o feita por $this->_setGridOption
     * 
     * @param String $option Uma das constantes em Grid::OPTION_*
     * @return mixed $value
     */
    protected function _getGridOption($option){
        return isset($this->_gridOption[$option]) ? $this->_gridOption[$option] : null;
    }
    
    /**
     * Define uma Option do form
     * 
     * @param String $option Uma das constantes em Form::OPTION_*
     * @param mixed $value
     * @param boolen $overwrite O padr�o � sobrescrever uma defini��o j� feita, define-se com false para dar a op��o de n�o faz�-lo 
     */
    protected function _setFormOption($option, $value, $overwrite = false){
        if(!isset($this->_formOption[$option]) || $overwrite == true){
            $this->_formOption[$option] = $value;
        }
    }
    
    /**
     * Obt�m uma defini��o feita por $this->_setFormOption
     * 
     * @param String $option Uma das constantes em Form::OPTION_*
     * @return mixed $value
     */
    protected function _getFormOption($option){
        return isset($this->_formOption[$option]) ? $this->_formOption[$option] : null;
    }
    
    // para ser o mesmo do modelo + section
    
    /**
     * Define o nome padr�o do container de um grid
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected function _getPageContainer(Model $model = null){
        if($model === null){
            $model = $this->_getModel();
        }
        
        $ret = "#".str_replace(".", "_", $model->getName());
        
        if($model->getSection()->getParentKey() !== null){
            $ret .= "_" . $model->getSection()->getName();
        }
        
        return $ret;
    }
    
    /**
     * M�todo que faz a prepara��o completa do objeto grid utilizando as defini��es
     * padr�es realizadas atrav�s dos atributos do Model em XML 
     * 
     * @return Grid
     */
    protected function _getGrid(){
        
        // Modelo configurado nesse controller
        $model = $this->_getModel();

        // Define a URL padr�o de carregamento de dados
        $url = 'data?';
        $params = array();
        
        // S� inclui o _model caso esteja no controller gen�rico do sistema
        if(get_class($this) == "ModelController"){
            $params[] = '_model=' . $model->getName();
        }
        
        // S� inclui o _section caso n�o seja a se��o padr�o do controller
        if($this->_getModel()->getSection()->getSection() !== null){
            $params[] = '_section=' . $model->getSection()->getName();
        }
        
        // Define a URL padr�o
        $this->_setGridOption(Grid::OPTION_DATA_URL, $url.implode("&", $params));
        
        // Define o t�tulo do grid para ser igual ao t�tulo do modelo
        $this->_setGridOption(Grid::OPTION_TITLE, $model->getSection()->getTitle());
        
        // Define o nome do container (por onde ser� poss�vel manipular o grid via JS)
        $this->_setGridOption(Grid::OPTION_CONTAINER, $this->_getPageContainer());
        
        // Define o model/section do grid no sistema
        $this->_setGridOption(Grid::OPTION_MODEL, $model->getName());
        $this->_setGridOption(Grid::OPTION_SECTION, $model->getSection()->getName());
        
        // Define o ID do grid no sistema
        $this->_setGridOption(Grid::OPTION_ID, $this->_getGridOption(Grid::OPTION_CONTAINER));
        
        // Se houver uma lista de campos pesquis�veis adiciona a caixa de busca r�pida na grid
        if($this->_searchActivated($model->getSection()->getName())){
            $this->_setGridOption(Grid::OPTION_SEARCH, true);
        }
        
        // Define a ordena��o padr�o da grid caso o atributo Field esteja definido no model
        if($this->_getModel()->getSection()->getField()){
            
            $field = $this->_getModel()->getSection()->getField();
            
            // Se o campo realmente existir no modelo or for o id
            if($this->_getModel()->getField( $field ) || $field == "id"){
                $this->_setGridOption(Grid::OPTION_SORT_COL, $field);
                $this->_setGridOption(Grid::OPTION_SORT_DIR, "ASC");
            }
            
        } 
        
        // Verifica se � um carregamento din�mico via XHR e faz as prepara��es necess�rias
        $isXhr = isset($_REQUEST['_container']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest";
        
        if($isXhr){
        
            // Define o carregamento via XHR
            $this->_setGridOption(Grid::OPTION_XHR, true, true);
        
            // O t�tulo do formul�rio ser� definido pelo layer
            $this->_setGridOption(Grid::OPTION_TITLE, "", true);
        
            // Define o container onde ser� criado o formul�rio
            $this->_setGridOption(Grid::OPTION_ID, "#".$_REQUEST['_container'], true);
            $this->_setGridOption(Grid::OPTION_CONTAINER, "#".$_REQUEST['_container'], true);
        }
        
        // Define a base_uri para permitir que as pr�ximas requisi��es referenciem o controller correto
        $this->_setGridOption(Grid::OPTION_BASE_URI, $this->_getBaseUri(), true);
        
        // Cria��o do componente de grid
        $grid = new Grid( $this->_gridOption );
        
        // Javascript padr�o do controller
        $grid->addJs('fenix/Fenix_Model.js');
        
        // Se � um grid de uma subse��o adiciona aos par�metros do model
        // uma referencia ao id da se��o superior se ele vier no request
        if($model->getSection()->getSection() != null){
            $parentKey = $model->getSection()->getParentKey()->getNameDatabase();
            
            if(isset($_REQUEST[$parentKey])){
                $grid->setOption(Grid::OPTION_PARAMS, $parentKey.'='.$_REQUEST[$parentKey]);
            }
            
        }
        
        // Processamentos dos campos do model
        foreach($model->getFields() as $field){
            
            // Adiciona cada campo que esteja com atributo grid != 'n' como uma coluna
            if($field->getGrid() && $field->getGrid() != 'n' && $field->getNameDatabase() !== false){
                
                $column = new GridColumn($field->getAttributes());
                
                // Alinhamento padr�o de n�meros � direita da c�lula
                if(ModelConfig::checkTypeNumeric($field)
                && !ModelConfig::checkType($field, ModelConfig::SELECT)
                && !ModelConfig::checkType($field, ModelConfig::FK)){
                    $column->setStyleContent('right');
                }
                
                // Alinhamento padr�o de datas centralizando
                if(ModelConfig::checkType($field, ModelConfig::DATE)|| ModelConfig::checkType($field, ModelConfig::DATETIME)){
                    $column->setStyleContent('center');
                }
                
                $column->setSortable(true);
                
                // Caso haja uma defini��o de width no $field a remove,
                // essa defini��o s� � usada no formul�rio
                if($column->getWidth()){
                    $column->__unset('width');
                }
                
                // Adiciona a coluna
                $grid->addColumn( $column );
            }
            
            // Adiciona cada campo que esteja com atributo filter == '1' como um filtro da grid
            if($field->getFilter() && $field->getNameDatabase() !== false){
                
                $filter = new GridFilter($field->getAttributes());
                
                // Adiciona o filtro
                $grid->addFilter( $filter );
                
            }
            
        }
        
        // Adiciona os bot�es padr�o da linha do grid
        $grid->addButton( Button::GRID_VIEW );
        
        if( $this->_checkPermission(Fenix_Profile::PERMISSION_EDIT) ){
            $grid->addButton( Button::GRID_EDIT );
        }
        
        if( $this->_checkPermission(Fenix_Profile::PERMISSION_DELETE) ){
            $grid->addButton( Button::GRID_DELETE );
        }
        
        // Adiciona os bot�es padr�o de rodap�/topo da p�gina
        if( $this->_checkPermission(Fenix_Profile::PERMISSION_INSERT) ){
            $grid->addButtonPage( Button::PAGE_NEW );
        }

        if( $grid->getOption(Grid::OPTION_XHR) ){
            $grid->addButtonPage( Button::PAGE_CLOSE );
        }
        
        return $grid;
    }
    
    /**
     * M�todo que faz a prepara��o completa do objeto form utilizando as defini��es
     * padr�es realizadas atrav�s dos atributos do Model em XML
     *
     * @return Form
     */
    protected function _getForm(){
    
        // Modelo configurado nesse controller
        $model = $this->_getModel();
    
        // Define o t�tulo do grid para ser igual ao t�tulo do modelo
        $this->_setFormOption(Form::OPTION_TITLE, $model->getSection()->getTitle());
    
        // Define o nome do container (por onde ser� poss�vel manipular o form via JS)
        // para ser o mesmo do modelo
        $this->_setFormOption(Form::OPTION_CONTAINER, $this->_getPageContainer());
        
        // Define o ID do grid no sistema
        $this->_setFormOption(Grid::OPTION_ID, $this->_getFormOption(Grid::OPTION_CONTAINER));
        
        // Define a action padr�o de salvamento de dados
        $this->_setFormOption(Form::OPTION_ACTION, 'save');
        
        // Define o callback de salvamento padr�o
        $this->_setFormOption(Form::OPTION_SUBMIT_CALLBACK, 'Fenix_Model.submitCallback');
        
        // Define o model/section do grid no sistema
        $this->_setFormOption(Grid::OPTION_MODEL, $model->getName());
        $this->_setFormOption(Grid::OPTION_SECTION, $model->getSection()->getName());
        
        // Define o registro (caso seja uma altera��o)
        if(isset($_REQUEST['id'])){
            $id = preg_replace("/([^0-9]+)/", "", $_REQUEST['id']);
            $record = null;
            if(strlen($id) > 0){
                $record = $this->_getModel()->select($_REQUEST['id']);
                $this->_setFormOption(Form::OPTION_RECORD, $record);
            }
            if(!$id || $record == null){
                throw new Fenix_Exception("Registro n�o localizado.");
            }
        }
        
        // Verifica se � um carregamento din�mico via XHR e faz as prepara��es necess�rias
        if(isset($_REQUEST['_container']) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest"){
            
            // Define o carregamento via XHR
            $this->_setFormOption(Form::OPTION_XHR, true, true);
            
            // O t�tulo do formul�rio ser� definido pelo layer
            $this->_setFormOption(Form::OPTION_TITLE, "", true);
            
            // Define o container onde ser� criado o formul�rio
            $this->_setFormOption(Form::OPTION_ID, "#".$_REQUEST['_container'], true);
            $this->_setFormOption(Form::OPTION_CONTAINER, "#".$_REQUEST['_container'], true);
            
            // Caso esteja definido um container dos bot�es redireciona a cria��o
            // dos bot�es para essa localiza��o
            if(isset($_REQUEST['_containerBtn'])){
                $this->_setFormOption(Form::OPTION_BUTTONS_PAGE_CONTAINER, "#".$_REQUEST['_containerBtn'], true);
            }
        }
        
        // Define a base_uri para permitir que as pr�ximas requisi��es referenciem o controller correto
        $this->_setFormOption(Form::OPTION_BASE_URI, $this->_getBaseUri(), true);
        
        $form = new Form( $this->_formOption );
    
        // Javascript padr�o do controller
        $form->addJs('fenix/Fenix_Model.js');
        
        // Define o nome do modelo como um campo oculto do formul�rio
        $form->addHiddenField('_model', $model->getName());
        $form->addHiddenField('_section', $model->getSection()->getName());
        
        // Se � um form de uma subse��o adiciona aos par�metros do form
        // uma referencia ao id da se��o superior se ele vier no request
        if($model->getSection()->getSection() != null){
            $parentKey = $model->getSection()->getParentKey()->getNameDatabase();
        
            if(isset($_REQUEST[$parentKey])){
                // Para que o form salve a refer�ncia do registro pai
                $form->addHiddenField($parentKey, $_REQUEST[$parentKey]);
            }
        }
        
        // Adiciona cada campo que esteja com atributo grid != 'n' como uma coluna
        foreach($model->getFields() as $field){
            if($field->getForm() && $field->getForm() != 'n'){
                
                $formField = new FormField($field->getAttributes());
                
                // Se o tamanho do campo for nulo e no tipo houver uma defini��o
                // fixa para o tamanho usa essa defini��o
                if($formField->getSize() == null){
                    $type = ModelConfig::getType($field->getType());
                    if($type->getSize() != null){
                        $formField->setSize( $type->getSize() );
                    }
                }
                
                // Tratamento especial para campos do tipo SELECT_TEXT
                if( ModelConfig::checkType($field, ModelConfig::SELECT_TEXT) ){
                    
                    $dadosSelectText = $model->prepareSelect( $field->getName() )
                                            ->group( $field->getName() )
                                            ->order( $field->getName() . ' ASC ' )
                                            ->query()->fetchAll();
                    
                    $str = array_map(function($v) use ($field){
                        return $v[ $field->getName() ];
                    }, $dadosSelectText);
                    
                    $formField->setAttribute(FormField::DATA, implode(",", $str));
                }
                
                // Adiciona a coluna
                $form->addField( $formField );
            }
        }
        
        // Prepara as subse��es para o formul�rio
        $this->_getFormPrepareSubsections($form);
        
        // Modo de visualiza��o ativado
        if($form->getOption(Form::OPTION_VIEW)){
            
            if( $this->_checkPermission(Fenix_Profile::PERMISSION_EDIT) ){
                $form->addButtonPage( Button::FORM_EDIT );
            }
            
            if( $this->_checkPermission(Fenix_Profile::PERMISSION_DELETE) ){
                $form->addButtonPage( Button::FORM_DELETE );
            }
            
            $form->addButtonPage( Button::FORM_CANCEL );
            
        }
        // Modo de visualiza��o desativado - Edi��o/Cria��o de registro
        else{
            
            // Adiciona os bot�es padr�o de rodap�/topo da p�gina
            $form->addButtonPage( Button::FORM_SAVE );
            
            if(isset($_REQUEST['id'])){
                
                if( $this->_checkPermission(Fenix_Profile::PERMISSION_DELETE) ){
                    $form->addButtonPage( Button::FORM_DELETE );
                }
                
            }
            
            $form->addButtonPage( Button::FORM_CANCEL );
        }
        
        return $form;
    }
    
    /**
     * Insere um grid j� preparado ou prepara o grid padr�o do model para inser��o em um formul�rio
     * 
     * @param Form $form Form que est� sendo criado
     * @param Model|Grid $object Um objeto Grid ou Model a ser inserido
     * @param boolean $addTab Determina se o grid ser� adicionado numa aba pr�pria
     * @param String[] $request Par�metros a serem disponibilizados no $_REQUEST que o _getGrid acessar�
     * 
     * @return Grid Grid criado ou o mesmo que foi inserido
     */
    protected function _getFormAddGrid(Form $form, $object, $addTab = true, $request = array()){
    
        // Se for um objeto Model obt�m o grid padr�o dele
        if($object instanceof Model){
            
            list($module, $name) = explode(".", $object->getName());
        
            // Salva o conte�do do request original antes de fazer modifica��es
            // para simular a execu��o de um request de gera��o de uma grid 
            $requestOriginal = $_REQUEST;
            
            // Modifica request atual para que uma nova grid seja gerada
            $_REQUEST = array();
            $_REQUEST['_model'] = $object->getName();
            $_REQUEST['_section'] = $object->getSection()->getName();
            foreach($request as $key => $value){
                $_REQUEST[$key] = $value;
            }
            
            $controllerFilename = null;
            $controllerClass = null;
            
            // Par�metros da cria��o de um controller no sistema
            $controllerModule = null;
            $controllerName = null;
            $controllerAction = "index";
            
            // Se o objeto sendo criado � da mesma classe de que o controller atual
            // d� preperencia ao controller atual para cria��o do grid
            if(get_class($object) == get_class($this->_getModel())){
                
                $controllerClass = get_class($this);
                $controllerFilename = __FILE__;
                
                $controllerModule = $this->_module;
                $controllerName = $this->_controller;
            }
            
            // Caso n�o v� usar o controller atual tenta criar o controller padr�o do
            // model a ser utilizado na cria��o do grid
            if($controllerClass === null){
                
                // Define a se��o do modelo do controller para gerar o grid dessa se��o
                $controllerFilename = Util::getControllerClassFilename( $object );
                $controllerClass = Util::getControllerClassName( $object );
                
            }
            
            $controller = null;
            
            // Caso haja um controller espec�fico faz a cria��o dele, caso contr�rio
            // faz a cria��o do ModelController do F�nix para gera��o do grid padr�o
            if(is_file($controllerFilename)){
                require_once $controllerFilename;
                if(class_exists($controllerClass)){

                    // Caso n�o tenha sido definido o modulo do controller utiliza 
                    // o padr�o igual ao schema do model
                    if($controllerModule == null){
                        $controllerModule = $module;
                    }
                    
                    // Caso n�o tenha sido definido o nome do controller utiliza
                    // o nome padr�o igual ao do model
                    if($controllerName == null){
                        $controllerName = Util::getControllerName($object);
                    }
                    
                    $controller = new $controllerClass($controllerModule, $controllerName, $controllerAction);
                    
                    if(!$controller instanceof Fenix_ModelController){
                        $controller = null;
                    }
                }
                if($controller == null){
                    throw new Exception("N�o foi poss�vel criar o controller '{$controllerClass}' que deveria ser controller padr�o do modelo: '".$object->getName()."'.");
                }
            } else {
                $class = get_class($this);
                $controller = new $class;
            }
            
            // Cria o grid padr�o para essa se��o
            $grid = $controller->index();
            
            // Reverte o request original
            $_REQUEST = $requestOriginal;
            
        }
        // Caso j� seja um grid j� o usa diretamente
        else if($object instanceof Grid){
            
            $grid = $object;
            
        } else {
            throw new Exception("O par�metro '\$object' precisa ser um Model ou um Grid.");
        }
        
        $title = Util::normalize( $grid->getOption(Grid::OPTION_TITLE) );
        $title = preg_replace("/([^A-Za-z0-\_]+)/", "_", $title);
        
        // Cria uma aba com o t�tulo da grid
        if($addTab === true){
            $form->addField( FormField::factory("tab_".$title, ModelConfig::TAB)->setTitle( $grid->getOption(Grid::OPTION_TITLE) ) );
        }
        
        // Adiciona um campo do tipo grid dentro do formul�rio
        $fieldGrid = FormField::factory($title, ModelConfig::GRID)->setGrid( $grid );
        
        $form->addField($fieldGrid);
        
        // Retorna o objeto grid para poss�veis personaliza��es
        return $grid;
    }
    
    protected function _getFormPrepareSubsections(Form $form){
        $record = $form->getOption(Form::OPTION_RECORD);
        
        if( $record !== null && isset($record['id']) && $this->_getModel()->isSubsection() === false ){
            
            foreach($this->_getModel()->getSubsections() as $name => $section){
                
                $modelSection = Model::factory($this->_getModel()->getName(), $name);
                
                $parentKey = $section->getParentKey()->getNameDatabase();
                
                $grid = $this->_getFormAddGrid($form, $modelSection, true, array($parentKey => $record['id']));
                
                $grid->setOption(Grid::OPTION_PARAMS, $parentKey.'='.$record['id']);
                
            }
            
        }
        
    }
    
    /**
     * Faz o tratamento de exce��es que possam acontecer no salvamento de um registro
     * @param Exception $e
     * @param mixed $item Item sendo salvo
     */
    protected function _saveExceptionHandler(Exception $e, $item){
        
        if(stripos($e->getMessage(), "Unique violation") !== false || stripos($e->getMessage(), "Unique constraint") !== false){
        
            $fields = array();
            
            Model::rollbackTransaction();
            
            foreach(explode(",", $this->_getModel()->getSection()->getUnique()) as $field){
                $field = Model::factory($this->_getModel()->getName(), $this->_getModel()->getSection()->getName())->getField(trim($field));
                if($field && isset($item[$field->getNameDatabase()])){
                    $fieldValue = $item[$field->getNameDatabase()];
                    if(ModelConfig::checkType($field, ModelConfig::FK)){
                        $fieldRecord = Model::factory($field->getTable())->select($fieldValue);
                        if( isset($fieldRecord[$field->getField(false)]) ){
                            $fieldValue = $fieldRecord[$field->getField(false)];  
                        }   
                    }
                    $fields[$field->getTitle()] = $fieldValue;
                }
            }
            
            if(count($fields) > 1){
                throw new Fenix_Exception("Erro no salvamento dos dados.<br><br>Os campos <strong>".implode(", ", array_keys($fields))."</strong> j� foram cadastrados em outro registro com o mesmos valores: <strong>".implode(", ", array_values($fields))."</strong>.<br><br>Verifique os dados digitados.");
            } else if(count($fields) == 1){
                throw new Fenix_Exception("Erro no salvamento dos dados.<br><br>O campo <strong>".key($fields)."</strong> j� foi cadastrado em outro registro com o mesmo valor: <strong>".current($fields)."</strong>.<br><br>Verifique os dados digitados.");
            } else {
                Util::exceptionLog($e);
                $e = new Fenix_Exception("Erro no salvamento dos dados.<br><br>Algum dos campos j� foi cadastrado em outro registro com o mesmo valor.<br><br>Verifique os dados digitados.");
                throw $e;
            }
        
        }
        
        throw $e;
    }
    
    /**
     * M�todo de interface entre o Controller e Model para salvamento de um registro
     *
     * @param String[] $item
     */
    protected function _save($item){
        
        $id = false;

        try {
            
            if(isset($item['id']) && !empty($item['id'])){
                $id = $this->_getModel()->update($item);
            } else {
                $id = $this->_getModel()->insert($item);
            }
            
        } catch(Exception $e){
            $this->_saveExceptionHandler($e, $item);
        }
        
        return $id;
    }
    
    /**
     * Faz o tratamento de exce��es que possam acontecer na exclus�o de um registro
     * @param Exception $e
     * @param int $id Item sendo deletado
     */
    protected function _deleteExceptionHandler(Exception $e, $id){
        
        if(stripos($e->getMessage(), "foreign key violation") !== false){
        
            Model::rollbackTransaction();
        
            preg_match("@from table \"([^\"]+)\"@", $e->getMessage(), $table);
        
            $table = $table[1];
            $schema = null;
            
            if(stripos($table, ".") === false){
                list($schema, $tmp) = explode(".", $this->_getModel()->getName());
            }
            
            $model = null;
            
            // @TODO - Melhor identifica��o do model a partir da tabela que causou o problema na exclus�o
            try {
                $model = Model::factory($table);
            } catch(Exception $e){
                if( stripos($e->getMessage(), "N�o foi poss�vel identificar um Model") !== false){
                    try {
                        $model = Model::factory($schema.".".$table);
                    } catch(Exception $e){
                        if( stripos($table, "_") !== false ){
                            list($modelName, $modelSection) = explode("_", $table);
                            $model = Model::factory($modelName, $modelSection);
                        }
                    }
                } else {
                    throw $e;
                }
            }
            
            $titulo = $model->getSection()->getTitle();
        
            $msg = "N�o � poss�vel excluir esse item pois ele ainda � referenciado em <strong>{$titulo}</strong>.<br><br>Exclua ou modifique o registro de {$titulo} para depois excluir esse item.";
            throw new Fenix_Exception($msg);
        }
        
        throw $e;
    }
    
    /**
     * M�todo de interface entre o Controller e Model para dele��o de um registro
     *
     * @param int $id
     */
    protected function _delete($id){
        
        $ret = false;
        
        try {
            $ret = $this->_getModel()->delete($id);
        } catch(Exception $e){
            $this->_deleteExceptionHandler($e, $id);
        }
        
        return $ret;
    }
    
    /**
     * Cria a cl�sula where da pesquisa para os campos n�o num�ricos registrados
     * @param string $value Valor
     * @return String[]
     */
    protected function _dataSearchQueryString($value){
        
        $section = $this->_getModel()->isSubsection() === false ? '_default' : $this->_getModel()->getSection()->getName();
        
        $where = array();
        
        if(isset($this->_searchFields[$section])){
            foreach($this->_searchFields[$section] as $field){
                if(strlen($value) > 0){
                    $whereKey = !is_string($field) ? $field->__toString() : $field;
                    $where[$whereKey][] = $this->_dataWhere($field, self::DATA_OPERATOR_CONTAINS, $value, true);
                }
            }
        }
        
        return $where;
    }
    
    /**
     * Cria a cl�sula where da pesquisa para os campos num�ricos registrados
     * @param string $value Valor
     * @return String[]
     */
    protected function _dataSearchQueryNumeric($value){
        
        $section = $this->_getModel()->isSubsection() === false ? '_default' : $this->_getModel()->getSection()->getName();
        
        // @TODO - In�cio - Remover - c�digo espec�fico para tarefas e solicita��es
        if(preg_match("@s([0-9]{1,})@", $value)){
            $value = str_replace("s", '', $value);
        }
        if(preg_match("@t([0-9]{1,})@", $value)){
            $value = str_replace("t", '', $value);
        }
        // @TODO - Fim - Remover - c�digo espec�fico para tarefas e solicita��es
        
        $where = array();
        
        if(isset($this->_searchFieldsNumeric[$section])){
            foreach($this->_searchFieldsNumeric[$section] as $field){
        
                $whereKey = !is_string($field) ? $field->__toString() : $field;
        
                $fieldName = explode('.', $field);
                $fieldName = end($fieldName);
        
                $fieldModel = $this->_getModel()->getField($fieldName);
        
                // Caso seja um campo do tipo select faz o parser do conte�do do atributo data
                // para permitir que seja buscado pelo t�tulo dos itens ao inv�s do c�digo deles
        
                if($fieldModel && ModelConfig::checkType($fieldModel, ModelConfig::SELECT)){
        
                    $data = Util::parseFieldSelectData($fieldModel);
        
                    if($data !== null && is_array($data)){
                        foreach($data as $k => $v){
                            if(stripos(Util::strip_accents($k), $value) !== false){
                                $value = $v;
                            }
                        }
                    }
                }
        
                // Caso seja um campo do tipo option tenta identificar o crit�rio da busca
                // e se ele for Sim ou N�o (case insensitive e sem acentos) faz a busca pelo inteiro
                if($fieldModel && ModelConfig::checkType($fieldModel, ModelConfig::OPTION)){
                    if(strtolower(Util::strip_accents(trim($value))) === "sim"){
                        $where[$whereKey][] = $this->_dataWhere($field, self::DATA_OPERATOR_EQ, 1);
                    }
                    if(strtolower(Util::strip_accents(trim($value))) === "nao"){
                        $where[$whereKey][] = $this->_dataWhere($field, self::DATA_OPERATOR_EQ, 0) . " OR " . $this->_dataWhere($field, self::DATA_OPERATOR_ISNULL);
                    }
                }
        
                // S� adiciona o crit�rio no where caso o conte�do da busca seja um n�mero
                if( preg_match("/^([0-9]+)$/", $value) ){
                    $where[$whereKey][] = $this->_dataWhere($field, self::DATA_OPERATOR_EQ, $value);
                }
            }
        }
        
        return $where;
    }
    
    /**
     * M�todo que prepara a consulta a ser executada no banco de dados
     */
    protected function _dataSearch(Zend_Db_Select $select, $q = ''){
        
        $q = Util::strip_accents($q);
        $q = preg_replace("@([^0-9\sA-Za-z\/\#\-\.])@", "", $q);
        $q = strtolower($q);
        $q = explode(' ', $q);
                
        $where = array();
        
        foreach($q as $q1){
            
            if(strlen($q1) > 0){
                
                // Cria a busca por campos de texto e num�ricos
                $wheres = array_merge($this->_dataSearchQueryString($q1), $this->_dataSearchQueryNumeric($q1));
                
                // Faz o merge dos resultados
                foreach($wheres as $identifier => $clauses){
                    if(!isset($where[$identifier])){
                        $where[$identifier] = array();
                    }
                    $where[$identifier] = array_merge($where[$identifier], $clauses);
                }
            }

        }
        
        // Para o mesmo identificador a busca padr�o � AND
        foreach($where as $k => $v){
            $where[$k] = implode(" AND ", $v);
        }
        
        // Para identificadores diferentes
        if(count($where) > 0){
            $select->where(implode(" OR ", $where));
        }
        
    }
    
    /**
     * M�todo que prepara a consulta a ser executada no banco de dados
     * 
     * @return Zend_Db_Select
     */
    protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
        
        $select = $this->_getModel()->prepareSelect(null, null, $sortCol, $sortDir, $limit, $offset);
        
        $this->_dataSubsection($select);
        
        // S� executa o _dataSearch caso a busca esteja ativdada para essa se��o
        if($query !== null && $this->_searchActivated($this->_getModel()->getSection()->getName())){
            $this->_dataSearch($select, $query );
        }
        
        // Caso haja filtros no request
        if($filters !== null){
            $this->_dataFilters($select, $filters);
        }
        
        return $select;
    }
    
    /**
     * M�todo de prepara��o do valor de um operador [NOT] LIKE
     * @param stirng $v
     * @return string
     */
    protected function _dataOperatorLike($v){
        return "%".$v."%";
    }
    
    /**
     * Prepara uma cl�usula where para ser utilizada numa query
     * @param string $identifier Identificador
     * @param string $operator Operador (constantes ModelController::DATA_OPERATOR_*)
     * @param string $value Valor
     * @param boolean $isString Define se ser� uma cla�sula com string (retira-se acentos por padr�o) 
     * @throws Exception
     * @return string SQL formato de escapado
     */
    protected function _dataWhere($identifier, $operator, $value = null, $isString = null){
        
        if($value !== null && !is_array($value)){
            $value = array($value);
        }
        
        if(!isset($this->_dataOperator[$operator])){
            throw new Exception(__METHOD__ . " - n�o foi poss�vel localizar as informa��es do operador '{$operator}'.");
        }
        
        $operatorSql = $this->_dataOperator[$operator][0];
        
        // Faz o processamento relativo ao callback (modifia��o do conte�do do value)
        // ou elimina��o do value (operadores un�rios) 
        if(isset($this->_dataOperator[$operator][1])){
            // Aplica o callback no value
            if(method_exists($this, $this->_dataOperator[$operator][1])){
                $value = array_map( array($this, $this->_dataOperator[$operator][1]), $value);
            }
            // Remove o conte�do do value
            if($this->_dataOperator[$operator][1] === false){
                $value = array();
            }
        }
        
        // Escapa o identificador
        $identifier = Table::getDefaultAdapter()->quoteIdentifier($identifier);
        
        // Processamentos extras no caso de query por string
        if($isString === true){
            // Modifica o identifier para executar a fun��o de retirar acentos
            $identifier = "strip_accents(".$identifier.")";
            // Remove os acentos dos valores
            $value = array_map( array("Util", "strip_accents"), $value);
        }
        
        // Mudan�as espec�ficas do postgres
        if(Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_PGSQL'){
            // Transforma o operador LIKE em postgres no operador ILIKE para ser case insensitive
            $operatorSql = str_ireplace("LIKE", "ILIKE", $operatorSql);
        }
        
        // Escapa todos os valores tamb�m
        $value = array_map(function($v){ return Table::getDefaultAdapter()->quote($v); }, $value);
        
        // Constru��o final da cl�usula
        $ret = $identifier . " {$operatorSql} ".implode(" AND ", $value);
        
        return trim($ret);
    }
    
    /**
     * Cria os filtros estruturados das consultas
     * @param Zend_Db_Select $select
     * @param mixed $filters
     * @return String[] Lista dos filtros criados
     */
    protected function _dataFilters(Zend_Db_Select $select = null, $filters = array()){
        
        if(!$filters){
            $filters = array();
        }
        
        $where = array();
        
        foreach($filters as $field => $filter){
            
            $tableName = $this->_getModel()->getTableName();
            
            // @TODO Caso n�o seja poss�vel definir o modelo usa o FROM prim�rio do objeto da consulta (a menos que j� esteja definido no nome do campo a tabela) 
            //$select->getPart(Zend_Db_Select::FROM)
            
            $fieldNameDatabase = $tableName.".".$field;
            $type = null;
            $fieldObj = null;
            
            if( $fieldObj = $this->_getModel()->getField($field) ){
                $field = $fieldObj->getNameDatabase();
                $type = ModelConfig::getType($fieldObj);
            }
            
            if($field !== null){

                // Define se � um campo date
                $isDate = null !== $type ? ModelConfig::checkType($fieldObj, ModelConfig::DATE) : false;
                $isDateTime = null !== $type ? ModelConfig::checkType($fieldObj, ModelConfig::DATETIME) : false;
                
                // Define se � um campo num�rido
                $isString = null !== $type ? ModelConfig::checkTypeNumeric($fieldObj) === false && !$isDate && !$isDateTime : false;
                
                // Paracada um dos filtros para o campo faz o processamento dos valores e gera as cl�usulas where
                foreach($filter as $operator => $values){
                    foreach($values as $value){
                        
                        // Sempre trabalha com arrays (generalizando j� que no operador between h� dois valores)
                        if(!is_array($value)){
                            $value = array($value);
                        }
                        
                        // Caso exista uma formata��o padr�o para o campo executa o m�todo configurado
                        if(null !== $type && $type->getModelFormatter() != null){
                            $value = array_map($type->getModelFormatter(), $value);
                        }
                        
                        // Prepara o array que receber� as cl�usulas where para o campo
                        if(!isset($where[$fieldNameDatabase])){
                            $where[$fieldNameDatabase] = array();
                        }
                        
                        // Ajuste para funcionamento do campo datetime
                        if($isDateTime){
                            // Redefine o operador para ser sempre between
                            $operator = self::DATA_OPERATOR_BETWEEN;
                            $value = array_map("trim", $value);
                            // Inclui sempre o segundo valor, caso j� n�o exista
                            if(!isset($value[1])){
                                $value[1] = $value[0];
                            }
                            // Quando s� h� a defini��o de data inclui a hora no final
                            if(strlen($value[0]) == 10){
                                $value[0] .= " 00:00:00";
                            }
                            if(strlen($value[1]) == 10){
                                $value[1] .= " 23:59:59";
                            }
                        }
                        
                        // Cria o where agrupando por operador
                        $where[$fieldNameDatabase][$operator][] = $this->_dataWhere($fieldNameDatabase, $operator, $value, $isString);
                    }
                }
                
                // Junta as cl�usulas do mesmo operador
                foreach($where[$fieldNameDatabase] as $operator => $clauses){
                    $where[$fieldNameDatabase][$operator] = implode(" OR ", $clauses);
                }
            }
        }
        
        if($select !== null){
            foreach($where as $field){
                $select->where( implode(" AND ", $field) );
            }
        }
        
        return $where;
    }
    
    /**
     * Faz prepara��es para quando os dados a serem carregados s�o de uma subse��o
     * @param Zend_Db_Select $select
     */
    protected function _dataSubsection(Zend_Db_Select $select){
        
        if( $this->_getModel()->isSubsection() ){
            
            // Caso seja uma subse��o e j� haja no request o filtro padr�o
            // da chave da se��o pai insere um where na consulta
            $parentKeyName = $this->_getModel()->getSection()->getParentKey()->getNameDatabase();
            if(isset($_REQUEST[$parentKeyName]) && !empty($_REQUEST[$parentKeyName])){
        
                $tableName = $this->_getModel()->getTableName();
                
                $select->where("{$tableName}.{$parentKeyName} = ?", $_REQUEST[$parentKeyName]);
        
            }
            
            // Insere um marcador para permitir se diferenciar pelo record se � uma subse��o ou n�o
            $select->columns(array('_section' => new Zend_Db_Expr("'".$this->_getModel()->getSection()->getName()."'")));
            
        }
        
    }
    
    /**
     * M�todo utilizado para fazer algum processamento nos dados depois de
     * efetuada a consulta no banco de dados e antes do retorno dos dados 
     * 
     * @param String[][] $data Dados que ser�o retornados
     * 
     * @param String[][] Dados processados
     */
    protected function _dataPrepare($data){
        return $data;
    }
    
    /**
     * Caso haja a defini��o do m�todo $prefix<SECTION> executa esse m�todo
     * 
     * @param String $prefix
     * 
     * @return String
     */
    protected final function _sectionMethodCall($prefix, $args = array()){
        $ret = null;
        
        $section = $prefix . ucfirst($this->_getModel()->getSection()->getName());
        
        if(method_exists($this, $section)){
            $ret = call_user_func_array(array($this, $section), $args);
        }
        
        return $ret;
    }
    
    /**
     * Cria o grid padr�o para exibi��o
     * 
     * @return Grid
     */
    protected function _indexAction(){
        
        // Tenta executar o m�todo padr�o da se��o
        $grid = $this->_sectionMethodCall("_getGrid");
        
        // Caso n�o tenha sido executado o m�todo personalizado da se��o
        // executa o m�todo padr�o do model
        if($grid == null){
            $grid = $this->_getGrid();
        }
        
        return $grid;
    }
    
    /**
     * Disponibiliza o grid para que outro controller fa�a a chamada
     * (para usar numa aba de um formul�rio, por exemplo)
     * 
     * Para uso interno na classe utilize o m�todo _getGrid ao inv�s
     * 
     * @return Grid
     */
    public function index(){
        return $this->_indexAction();
    }
    
    /**
     * Action final de exibi��o de um grid, n�o pode ser reescrito
     * e por isso modifica��es no grid deve ser realizadas reescrevendo
     * o m�todo _getGrid();
     */
    public final function indexAction(){
    
        $grid = $this->_indexAction();
    
        // Chama o processo padr�o de renderiza��o da tela
        $grid->render();
    }
    
    /**
     * Action final de retorno de dados padr�o para um Grid, n�o pode ser 
     * reescrito e por isso modifica��es no grid deve ser realizadas 
     * reescrevendo o m�todo _data() e _dataPrepare()
     */
    public final function dataAction(){
        
        $filters = isset($_REQUEST['_filters']) ? Util::utf8_decode(json_decode(utf8_encode($_REQUEST['_filters']), true)) : null;
        $query = isset($_REQUEST['q']) ? utf8_decode($_REQUEST['q']) : null;
        
        $sortCol = isset($_REQUEST['_sortCol']) ? $_REQUEST['_sortCol'] : null;
        $sortDir = isset($_REQUEST['_sortDir']) ? $_REQUEST['_sortDir'] : null;
        
        $limit = isset($_REQUEST['_limit']) ? $_REQUEST['_limit'] : null;
        $offset = isset($_REQUEST['_offset']) ? $_REQUEST['_offset'] : null;
        
        $requestId = isset($_REQUEST['_requestId']) ? $_REQUEST['_requestId'] : '';
        
        // Tenta executar o m�todo padr�o da se��o
        $select = $this->_sectionMethodCall("_data", array($filters, $query, $sortCol, $sortDir, $limit, $offset));
        
        // Caso n�o tenha sido executado o m�todo personalizado da se��o
        // executa o m�todo padr�o do model 
        if($select == null){
            $select = $this->_data($filters, $query, $sortCol, $sortDir, $limit, $offset);
        }
        
        // Cria um clone da consulta principal para fazer a contagem de linhas para a grid
        $selectCount = clone $select;
        $selectCount->reset(Zend_Db_Select::LIMIT_COUNT)->reset(Zend_Db_Select::LIMIT_OFFSET);
        $selectCount = $selectCount->__toString();
        
        // Faz com que seja sempre adicionada uma ordena��o fixa pela primeira coluna sendo obtida
        // para que em consultas do postgres a ordem dos campos n�o fique variando a cada requisi��o
        $from = $select->getPart(Zend_Db_Select::FROM); 
        $cols = $select->getPart(Zend_Db_Select::COLUMNS);
        foreach($cols as $col){
            foreach($from as $table){
                if($col[0] == $table['tableName'] && !$col[1] instanceof Zend_Db_Expr){
                    $select->order($col[0].".".$col[1]." DESC");
                    break 2;
                }
            }
        }
        
        // Adiciona a coluna de contagem
        $select->columns(array('fenix_count' => new Zend_Db_Expr("(SELECT COUNT(*) FROM ({$selectCount}) AS fenix_count_table)")));
        
        // Executa a query
        $data = $select->query()->fetchAll();
        
        $ret = array();
        $ret['header'] = array('count' => isset($data[0]) ? $data[0]['fenix_count'] : "0",
                               'limit' => $select->getPart(Zend_Db_Select::LIMIT_COUNT),
                               'offset' => $select->getPart(Zend_Db_Select::LIMIT_OFFSET),
                               'requestId' => $requestId);
        
        // Tenta executar o m�todo padr�o da se��o
        $ret['data'] = $this->_sectionMethodCall("_dataPrepare", array($data) );
        
        // Caso n�o tenha sido executado o m�todo personalizado da se��o
        // executa o m�todo padr�o do model
        if(!isset($ret['data'])){
            $ret['data'] = $this->_dataPrepare( $data );
        }
        
        $json = json_encode(Util::utf8_encode($ret));
        
        header("Content-Type: application/json");
        
        // Se for uma vers�o de desenvolvimento mostra o JSON no browser de
        // forma mais f�cil de ler
        if(Util::isDev()){
            $json = Util::json_indent( $json );
        }
        
        die($json);
    }
    
    /**
     * Cria o formul�rio do registro
     * 
     * @return Form
     */
    protected function _recordAction(){
        // Tenta executar o m�todo padr�o da se��o
        $form = $this->_sectionMethodCall("_getForm");
        
        // Caso n�o tenha sido executado o m�todo personalizado da se��o
        // executa o m�todo padr�o do model
        if($form == null){
            $form = $this->_getForm();
        }
        
        return $form;
    }
    
    /**
     * Disponibiliza o form para que outro controller fa�a a chamada
     * (para usar numa aba de um formul�rio, por exemplo)
     *
     * Para uso interno na classe utilize o m�todo _getForm ao inv�s
     *
     * @return Form
     */
    public function record(){
        return $this->_recordAction();
    }
    
    /**
     * Action final de exibi��o de um form, n�o pode ser reescrito
     * e por isso modifica��es no form deve ser realizadas reescrevendo
     * o m�todo _getForm();
     */
    public final function recordAction(){
    
        $form = $this->_recordAction();
        
        // Chama o processo padr�o de renderiza��o da tela
        $form->render();
    }
    
    /**
     * Cria o formul�rio de visualiza��o de registro
     * @return Form
     */
    protected function _viewAction(){
        
        // Defini��o do modo de visualiza��o
        $this->_setFormOption(Form::OPTION_VIEW, true);
        
        // Tenta executar o m�todo padr�o da se��o
        $form = $this->_sectionMethodCall("_getForm");
        
        // Caso n�o tenha sido executado o m�todo personalizado da se��o
        // executa o m�todo padr�o do model
        if($form == null){
            $form = $this->_getForm();
        }
        
        return $form;
    }
    
    /**
     * Action final de visualiza��o de um registro
     */
    public final function viewAction(){
        
        $form = $this->_viewAction();
        
        // Chama o processo padr�o de renderiza��o da tela
        $form->render();
    }
    
    /**
     * Action final salvamento de dados de um registro, n�o pode ser reescrito
     * e por isso modifica��es no grid deve ser realizadas reescrevendo
     * o m�todo _save();
     */
    public final function saveAction(){
        
        Model::beginTransaction();
        
        // Verifica��o de permiss�o de inser��o de novo registro
        if( empty($item['id']) && $this->_checkPermission(Fenix_Profile::PERMISSION_INSERT) == false ){
            throw new Fenix_Exception("Acesso negado.");
        } 
        
        // Verifica��o de permiss�o de altera��o de registro
        if( !empty($item['id']) && $this->_checkPermission(Fenix_Profile::PERMISSION_EDIT) == false ){
            throw new Fenix_Exception("Acesso negado.");
        } 
        
        $item = Util::utf8_decode($_REQUEST);
        
        $id = $this->_save($item);
        
        Model::commitTransaction();
        
        echo $id;
        
        die();
    }
    
    /**
     * Action final de exibi��o de um grid, n�o pode ser reescrito
     * e por isso modifica��es no grid deve ser realizadas reescrevendo
     * o m�todo _getForm();
     */
    public final function deleteAction(){
        
        // Verifica��o de permiss�o de altera��o de registro
        if( $this->_checkPermission(Fenix_Profile::PERMISSION_DELETE) == false ){
            throw new Fenix_Exception("Acesso negado.");
        } 
        
        Model::beginTransaction();
        
        $this->_delete($_REQUEST['id']);
        
        Model::commitTransaction();
        
        die("1");
    }
    
    /**
     * Faz o procedimento de upload de um arquivo
     * 
     * @param String $hash
     * @param String $name
     * @param String $tmp_name
     * @param String $type
     * @param int $size
     * 
     * @return String[] Array padr�o de metadados do arquivo
     */
    protected function _upload($hash, $name, $tmp_name, $type, $size){
        
        $info = Util::getModelFile()->upload($hash, $name, $tmp_name, $type, $size);
        
        $ret = "<script>window.parent.$.fn.upload.callback(".json_encode(Util::utf8_encode($info)).");</script>";
        
        return $ret;
    }
    
    /**
     * Action de upload de arquivos
     * 
     * @throws UploadException
     */
    public function uploadAction(){
        
        $js = array();
        
        foreach($_FILES as $file){
    
            if($file['error'] === UPLOAD_ERR_OK){
    
                $js[] = $this->_upload($_REQUEST['hash'], $file['name'], $file['tmp_name'], $file['type'], $file['size']);
                
            } else {
    
                throw new UploadException($file['error']);
    
            }
        }
        
        die(implode("\n", $js));
    }
    
    /**
     * M�todo para download de um arquivo a partir do hash no sistema
     * 
     * @param String $hash
     * @param boolean $force Define se ser� for�ado o download do arquivo
     * @param String $page P�gina a ser exibida, opcionalmente
     * @param int $w Largura m�xima da imagem, quando � uma, opcionalmente
     * @param int $h Altura m�xima da imagem, quando � uma, opcionalmente
     */
    protected function _download($hash, $force, $page, $w = false, $h = false){
        
        Util::getModelFile()->download($hash, $force, $page, $w, $h);
        
    }
    
    /**
     * Action de download de arquivos
     * 
     */
    public function downloadAction(){
    
        // Caso haja a defini��o da p�gina repassa a m�todo de download
        $page = isset($_REQUEST['page']) && is_numeric($_REQUEST['page']) && $_REQUEST['page'] >= 0 ? $_REQUEST['page'] : null;
        $force = isset($_REQUEST['force']) ? true : false;
        
        if(isset($_REQUEST['width'])){
            $_REQUEST['w'] = $_REQUEST['width'];
        }
        
        if(isset($_REQUEST['height'])){
            $_REQUEST['h'] = $_REQUEST['height'];
        }
        
        $w = isset($_REQUEST['w']) && is_numeric($_REQUEST['w']) ? $_REQUEST['w'] : false;
        $h = isset($_REQUEST['h']) && is_numeric($_REQUEST['h']) ? $_REQUEST['h'] : false;
        
        foreach($_GET as $hash => $v){
            if(strlen($hash) >= 32){
                $this->_download($hash, $force, $page, $w, $h);
            }
        }
    }
    
    /**
     * M�todo de realiza��o da consulta de busca de um campo FK (atributo remote do model)
     * 
     * @param String $field Campo FK do modelo sendo pesquisado
     * @param String $q Termo da busca
     * @param String $section Se��o do model onde o $field se localizad
     * 
     * @return Dados da pesquisa
     */
    protected function _fkSearch($field, $q){
        return $this->_getModel()->fkSearch($field, $q)->query()->fetchAll();
    }
    
    /**
     * Action de recebimento da requisi��o de busca de FK
     */
    public final function fkSearchAction(){
        
        if(!isset($_REQUEST['q']) || !isset($_REQUEST['field'])){
            throw new Exception(__METHOD__ . " - a busca de campos fk precisa ter pelo menos os par�metros 'field' e 'q' para ser acionada.");
        }

        $data = $this->_fkSearch($_REQUEST['field'], $_REQUEST['q']);
        
        header("Content-Type: application/json");
        die(json_encode(Util::utf8_encode($data)));
    }
    
    /**
     * M�todo de realiza�ao da consulta de Dependencia de FK no sistema
     * @param String|ModelField $field Nome ou objeto do campo
     * @param int $value Valor da dependencia
     */
    protected function _fkDependency($field, $value){
        return $this->_getModel()->fkDependency($field, $value)->query()->fetchAll();
    }
    
    /**
     * Action de recebimento da requisi��o de carregamento de FK Dependente
     */
    public final function fkDependencyAction(){
    
        if(!isset($_REQUEST['value']) || !isset($_REQUEST['field'])){
            throw new Exception(__METHOD__ . " - a dependencia de fk precisa ter pelo menos os par�metros 'field' e 'value' para ser acionada.");
        }
        
        $data = $this->_fkDependency($_REQUEST['field'], $_REQUEST['value']);
        
        header("Content-Type: application/json");
        die(json_encode(Util::utf8_encode($data)));
    }
}

/**
 * Cria uma exce��o espec�fica para o upload de arquivos
 * para que o sistema registre o erro caso ele exista
 */
class UploadException extends Exception {

    public function __construct($code) {

        $message = $this->codeToMessage($code);

        parent::__construct($message, $code);
    }

    private function codeToMessage($code){

        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }

        return $message;
    }
}



















