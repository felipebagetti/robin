<?php

require_once 'PageInternal.php';
require_once __DIR__.'/Form.php';

/**
 * Classe para representar um grid de dados no sistema
 *
 * @copyright Eramo Software
 */
class Grid extends PageInternal {
    
    /** JSON - Define um objeto JSON com os dados iniciais da grid */
    const OPTION_DATA = 'data';
    /** String - Define a URL que será chamada via AJAX para recarregar dados da grid */
    const OPTION_DATA_URL = 'dataUrl';
    /** String - Define parâmetros extras */
    const OPTION_PARAMS = 'params';
    /** boolean - Define se o grid será carregado automaticamente quando a página for carregada */
    const OPTION_AUTO_LOAD = 'autoLoad';
    /** boolean - Define se os botões devem ser ocultados, o padrão é serem mostrados */
    const OPTION_BUTTONS_HIDE = 'buttonsHide';
    /** String - Define o formatter padrão dos botões da grid, o padrão é $.fn.grid.formatter.uttons  */
    const OPTION_BUTTONS_FORMATTER = 'buttonsFormatter';
    /** String - Define a largura máxima da coluna de botões  */
    const OPTION_BUTTONS_WIDTH = 'buttonsWidth';
    /** boolean - Define se haverá uma barra de busca rápida  */
    const OPTION_SEARCH = 'search';
    /** boolean - Define se haverá uma barra de busca rápida  */
    const OPTION_SEARCH_AUTOFOCUS = 'searchAutofocus';
    /** String - Define uma função javascript do escopo que será chamada ao final do carregamento do grid  */
    const OPTION_LOAD_CALLBACK = 'loadCallback';
    /** boolean - Define ser haverá paginação no grid */
    const OPTION_PAGINATION = 'pagination';
    /** int - Define a página inicial ao carregar o grid */
    const OPTION_PAGINATION_PAGE = 'paginationPage';
    /** int - Define o número de registros a ser exibido por página */
    const OPTION_PAGINATION_PER_PAGE = 'paginationPerPage';
    /** String - Define a coluna e ordenação padrão */
    const OPTION_SORT_COL = 'sortCol';
    /** String - Define a direção padrão de ordenação */
    const OPTION_SORT_DIR = 'sortDir';
    /** String - Define a direção padrão de ordenação */
    const OPTION_EDIT_ON_LAYER = 'editOnLayer';
    
    /**
     * Lista das colunas do grid
     * @var GridColumn
     */
    protected $_columns = array();
    
    /**
     * Lista dos filtros da grid
     * @var GridFilter
     */
    protected $_filters = array();
    
    /**
     * Lista de botões do Grid
     * @var Button[]
     */
    protected $_buttons = array();
    
    /**
     * Lista de Options padrão do componente
     * 
     * @var String[]
     */
    protected $_options = array(
                self::OPTION_TITLE => 'Default Grid',
                self::OPTION_DATA_URL => 'data',
                self::OPTION_AUTO_LOAD => true,
                self::OPTION_BUTTONS_FORMATTER => '$.fn.grid.formatter.buttons',
                self::OPTION_ID => '#grid',
                self::OPTION_CONTAINER => '#grid',
                self::OPTION_PAGINATION => true,
                self::OPTION_PAGINATION_PAGE => '0',
                self::OPTION_PAGINATION_PER_PAGE => '10',
                self::OPTION_EDIT_ON_LAYER => true,
                self::OPTION_BASE_URI => ''
            );
    
    /**
     * Constrói o objeto padrão definindo uma lista de Options
     * 
     * @param unknown_type $options
     */
    public function __construct($options = array()){
        parent::__construct($options);
        
        // Adicionado o form também pelo uso dos filtros
        $this->addJs('fenix/jquery.form.js');
        $this->addJs('fenix/jquery.maskedinput.js');
        
        $this->addJs('fenix/jquery.grid.js');
        
    }
    
    /**
     * Adiciona uma nova coluna ao grid
     *
     * @param String $name Define o nome da coluna
     * @param String $title Define o título da coluna a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     * @param String $styleContent Define uma classe css de estilo específica para o conteúdo
     * @param String $styleHeader Define uma classe css de estilo específica para o cabeçalho
     * @param String $width Define a largura (prefencialmente em 'em' da coluna no grid)
     * @param String $sortable Define se essa será uma coluna ordenável
     */
    protected function _addColumn($name, $title = null, $formatter = null, $styleContent = null, $styleHeader = null, $width = null, $sortable = true){
        
        if( !($name instanceof GridColumn) ){
            $name = GridColumn::factory($name, $title);
            
            $args = array('formatter', 'styleContent', 'styleHeader', 'width', 'sortable');
            foreach($args as $arg){
                if($$arg != null){
                    $method = "set" . $arg;
                    $name->$method($$arg);
                }
            }
        }
        
        return $name;
    }
    
    /**
     * Adiciona uma nova coluna ao grid
     * 
     * @param String $name Define o nome da coluna
     * @param String $title Define o título da coluna a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     * @param String $styleContent Define uma classe css de estilo específica para o conteúdo
     * @param String $styleHeader Define uma classe css de estilo específica para o cabeçalho
     * @param String $width Define a largura (prefencialmente em 'em' da coluna no grid)
     * @param String $sortable Define se essa será uma coluna ordenável
     */
    public function addColumn($name, $title = null, $formatter = null, $styleContent = null, $styleHeader = null, $width = null, $sortable = true){
        $this->_columns[] = $this->_addColumn($name, $title, $formatter, $styleContent, $styleHeader, $width, $sortable);
    }
    
    /**
     * Adiciona uma nova coluna ao grid na posição determinada
     * 
     * @param int $pos Define a posição a adicionar a coluna no grid
     * @param String $name Define o nome da coluna
     * @param String $title Define o título da coluna a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     * @param String $styleContent Define uma classe css de estilo específica para o conteúdo
     * @param String $styleHeader Define uma classe css de estilo específica para o cabeçalho
     * @param String $width Define a largura (prefencialmente em 'em' da coluna no grid)
     * @param String $sortable Define se essa será uma coluna ordenável
     */
    public function addColumnAt($pos, $name, $title = null, $formatter = null, $styleContent = null, $styleHeader = null, $width = null, $sortable = true){
        $column = $this->_addColumn($name, $title, $formatter, $styleContent, $styleHeader, $width, $sortable);
        
        if(!$pos){
            $pos = 0;
        }
        
        $this->_columns = array_merge( array_slice($this->_columns, 0, $pos), array($column), array_slice($this->_columns, $pos) );
    }
    
    /**
     * Adiciona uma nova coluna ao grid na posição determinada
     * 
     * @param String $columnName Define depois de qual coluna a nova será inserida
     * @param String $name Define o nome da coluna
     * @param String $title Define o título da coluna a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     * @param String $styleContent Define uma classe css de estilo específica para o conteúdo
     * @param String $styleHeader Define uma classe css de estilo específica para o cabeçalho
     * @param String $width Define a largura (prefencialmente em 'em' da coluna no grid)
     * @param String $sortable Define se essa será uma coluna ordenável
     */
    public function addColumnAfter($columnName, $name, $title = null, $formatter = null, $styleContent = null, $styleHeader = null, $width = null, $sortable = true){
        $pos = $this->_getColumnPosition($columnName) + 1;
        $this->addColumnAt($pos, $name, $title, $formatter, $styleContent, $styleHeader, $width, $sortable);
    }
    
    /**
     * Obtém uma coluna da grid
     * 
     * @param String $name Nome da coluna a ser localizada
     * 
     * @return GridColumn
     */
    public function getColumn($name){
        foreach($this->_columns as $column){
            if($column->getName() == $name){
                return $column;
            }
        }
        
        return null;
    }
    
    /**
     * Obtém a lista de colunas da grid
     * 
     * @return GridColumn[]
     */
    public function getColumns(){
        return $this->_columns;
    }
    
    /**
     * Obtém a posição de uma coluna na grid
     * 
     * @param String $name Nome da coluna a ser localizada
     * 
     * @return int
     */
    protected function _getColumnPosition($name){
        foreach($this->_columns as $key => $column){
            if($column->getName() == $name){
                return $key;
            }
        }
        
        return null;
    }
    
    /**
     * Define um atributo de uma coluna
     * 
     * @param String $name
     * @param String $attr
     * @param String $value
     */
    public function setColumnAttribute($name, $attr, $value){
        $column = $this->getColumn($name);
        if($column != null){
            $column->__set($attr, $value);
        } else {
            Util::log(__METHOD__ . " - coluna '{$name}' não existe na grid.", Util::FENIX_LOG_WARNING);
        }
    }
    
    /**
     * Obtém um botão da grid
     *
     * @param String $title Título do botão a ser localizado
     *
     * @return Button
     */
    public function getButton($title){
        foreach($this->_buttons as $button){
            if($button->getTitle() == $title){
                return $button;
            }
        }
    
        return null;
    }
    
    /**
     * Obtém a lista de botões
     *
     * @return Button[]
     */
    public function getButtons(){
        return $this->_buttons;
    }

    /**
     * Adiciona um novo filtro a grid
     *
     * @param String $name Define o nome do campo
     * @param String $type Define o título do campo a ser exibido
     * @param String $title Define o título do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addFilter($name, $type = null, $title = null, $formatter = null){
        $this->addFilterAt(null, $name, $type, $title, $formatter);
    }
    
    /**
     * Adiciona um novo filtro a grid
     *
     * @param int $pos Posição do campo no formulário
     * @param String $name Define o nome do campo
     * @param String $type Define o título do campo a ser exibido
     * @param String $title Define o título do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addFilterAt($pos = null, $name, $type = null, $title = null, $formatter = null){
    
        if( !($name instanceof GridFilter) ){
            $name = GridFilter::factory($name, $type);
    
            $args = array('title', 'formatter');
            foreach($args as $arg){
                if($arg != null){
                    $method = "set" . $arg;
                    $name->$method($$arg);
                }
            }
        }
    
        if($pos === null){
            $pos = count($this->_filters);
        }
    
        $this->_filters = array_merge( array_slice($this->_filters, 0, $pos), array($name), array_slice($this->_filters, $pos) );
    
    }
    
    /**
     * Adiciona um novo filtro a grid
     *
     * @param String $fieldName Define depois de qual campo a nova será inserida
     * @param String $name Define o nome do campo
     * @param String $type Define o título do campo a ser exibido
     * @param String $title Define o título do campo a ser exibido
     * @param String $formatter Define um ponteiro para uma função javascript com assinatura function(text, record, column, grid, table, tr, td){}
     */
    public function addFilterAfter($fieldName, $name, $type = null, $title = null, $formatter = null){
        $pos = $this->_getFilterPosition($fieldName) + 1;
        $this->addFilterAt($pos, $name, $type, $title, $formatter);
    }
    
    /**
     * Obtém a posição de um campo no form
     *
     * @param String $name Nome do campo a ser localizado
     *
     * @return int
     */
    protected function _getFilterPosition($name){
        foreach($this->_filters as $key => $field){
            if($field->getName() == $name){
                return $key;
            }
        }
    
        return null;
    }
    
    /**
     * Remove um dos campos do formulário
     *
     * @param String|GridFilter $name
     *
     * @return GridFilter
     */
    public function deleteFilter($name){
    
        $ret = null;
    
        if($name instanceof GridFilter){
            $name = $name->getName();
        }
    
        foreach($this->_filters as $key => $field){
            if($field->getName() == $name){
                $ret = $this->_filters[$key];
                unset($this->_filters[$key]);
            }
        }
    
        if($ret === null){
            throw new Exception("Filter with name '".$name."' not found.");
        }
    
        $this->_filters = array_values($this->_filters);
    
        return $ret;
    }
    
    /**
     * Move um filtro para determinada posição
     *
     * @param int|String $pos Posição ou nome do campo a inserir depois
     * @param GridFilter|String $field
     *
     */
    public function moveFilter($pos, $field){
    
        if(is_string($pos)){
            foreach($this->_filters as $key => $f){
                if($f->getName() == $pos){
                    $pos = $key+1;
                }
            }
        }
    
        if($pos > count($this->_filters) || !is_numeric($pos)){
            $pos = count($this->_filters);
        }
    
        $this->addFilterAt($pos, $this->deleteFilter($field));
    }
    
    /**
     * Move uma coluna para determinada posição
     *
     * @param int|String $pos Posição ou nome da coluna a inserir depois
     * @param GridColumn|String $field
     *
     */
    public function moveColumn($pos, $column){
    
        if(is_string($pos)){
            foreach($this->_columns as $key => $f){
                if($f->getName() == $pos){
                    $pos = $key+1;
                }
            }
        }
    
        if($pos > count($this->_columns) || !is_numeric($pos)){
            $pos = count($this->_columns);
        }
    
        $this->addColumnAt($pos, $this->deleteColumn($column));
    }
    
    /**
     * Obtém um campo do form
     *
     * @param String $name Nome da campo a ser localizada
     *
     * @return GridFilter
     */
    public function getFilter($name){
        foreach($this->_filters as $field){
            if($field->getName() == $name){
                return $field;
            }
        }
    
        return null;
    }
    
    /**
     * Retorna a lista de campos do formulário
     *
     * @return GridFilter[]
     */
    public function getFilters(){
        return $this->_filters;
    }
    
    /**
     * Adiciona um novo botão à cada linha do grid
     * 
     * @param int $pos Posição do botão
     * @param String $title Título a ser exibido no botão
     * @param String $action Ponteiro da função a ser executada com a assinatura function(record){}
     * @param String $icon Classe css de ícone desse botão (classes icon-* do bootstrap)
     * @param String $extra Definições extras, exemplo: array('className' => 'btn-primary') para um botão primário do Bootstrap
     */
    public function addButtonAt($pos, $title, $action = null, $icon = null, $extra = array()){
        
        if( !($title instanceof Button) ){
            $title = Button::factory($title, $action, $this);
            
            if($icon != null){
                $title->setIcon($icon);
            }
            
            foreach($extra as $key => $value){
                $title->__set($key, $value);
            }
        }
        
        // Definição da classe padrão dos botões
        $className = $title->getClassName() ? $title->getClassName() : '';
        
        // Tamanho xs (extra small) por padrão
        if(stripos($className, "btn-lg") === false
        && stripos($className, "btn-sm") === false
        && stripos($className, "btn-xs") === false){
            $className = "btn-xs ".$className;
        }

        // Botão default por padrão  
        if(stripos($className, "btn-default") === false
        && stripos($className, "btn-primary") === false
        && stripos($className, "btn-success") === false
        && stripos($className, "btn-info") === false
        && stripos($className, "btn-warning") === false
        && stripos($className, "btn-danger") === false
        && stripos($className, "btn-link") === false){
            $className = "btn-default ".$className;
        }
        // classe btn por padrão
        if(stripos($className, "btn ") === false){
            $className = "btn ".$className;
        }
        $title->setClassName($className);
        
        if($pos === null){
            $pos = count($this->_buttons);
        }
        
        $this->_buttons = array_merge( array_slice($this->_buttons, 0, $pos), array($title), array_slice($this->_buttons, $pos) );
        
    }
    
    /**
     * Adiciona um novo botão à cada linha do grid
     * 
     * @param String $title Título a ser exibido no botão
     * @param String $action Ponteiro da função a ser executada com a assinatura function(record){}
     * @param String $icon Classe css de ícone desse botão (classes icon-* do bootstrap)
     * @param String $extra Definições extras, exemplo: array('className' => 'btn-primary') para um botão primário do Bootstrap
     */
    public function addButton($title, $action = null, $icon = null, $extra = array()){
        $this->addButtonAt(null, $title, $action, $icon, $extra);
    }
    
    /**
     * Remove uma das colunas adicionadas ao grid 
     * 
     * @param String $title
     */
    public function deleteColumn($name){
        
        $ret = null;

        if($name instanceof GridColumn){
            $name = $name->getName();
        }
        
        foreach($this->_columns as $key => $column){
            if($column->getName() == $name){
                $ret = $this->_columns[$key];
                unset($this->_columns[$key]);
            }
        }
        
        if($ret === null){
            throw new Exception("Column with name '".$name."' not found.");
        }
        
        $this->_columns = array_values($this->_columns);
        
        return $ret;
    }
    
    /**
     * Remove um dos botões adicionados a grid 
     * 
     * @param String $title
     */
    public function deleteButton($title){
        foreach($this->_buttons as $key => $button){
            if($button->getTitle() == $title){
                unset($this->_buttons[$key]);
            }
        }
        $this->_buttons = array_values($this->_buttons);
    }
    
    /**
     * Faz a preparação das colunas para a renderização
     */
    protected function _prepareRenderColumns(){
    
        $columns = $this->_columns;
        
        foreach($columns as $column){
            
            // Para colunas sem tipo (geralmente definidas posteriomente a criação do modelo)
            // ignora esse processamento
            if(!$column->getType()){
                continue;
            }
            
            $type = ModelConfig::getType($column->getType());
            
            // Caso haja a definição padrão de um formatter para o tipo de campo
            // na grid define esse formatter 
            if( $type->getFormatterGrid() ){
                
                $formatter = $type->getFormatterGrid();
                
                // Armazena o formatter original caso seja necessário em outras situações
                $column->setFormatterOriginal($column->getFormatter());
                
                // Se já existir um formatter transforma define uma função anônima para
                // repassar ao formatter definido no grid o conteúdo depois de ser forma
                // tado pelo formatter padrão do tipo de campo
                if($column->getFormatter()){

                    $args = "text, record, column, grid, table, tr, td";
                    $args2 = str_replace("text, ", "", $args);
                    
                    $formatter = "function({$args}){ var x = {$column->getFormatter()}({$formatter}({$args}), {$args2}); if(typeof x != 'undefined') return x; }";
                    
                }
    
                $column->setFormatter($formatter);
            }
            
            // Caso haja a definição de um JS específico para o tipo o inclui no carregamento da página
            if( $type->getJs() ){
                $this->addJs( $type->getJs() );
            }
    
        }
        
        // Verifica se há formatters JS, no padrão de definição de javascript para
        // os modelos, que ainda não tiveram seus arquivos JS inseridos no escopo
        // caso encontre o arquivo JS correspondente faz a inserção na lista de Javascript
        $cmd = array();
        foreach($columns as $column){
            $cmd[] = $column->getFormatter();
        }
        $this->_parseJsLines($cmd);
        
        // Retorna as colunas preparadas
        return $this->_prepareRenderAttributeList($columns);
    }
    
    /**
     * Faz a preparação dos campos para a renderização
     */
    protected function _prepareRenderFilters(){
        
        foreach($this->_filters as $field){
    
            // Para campos sem tipo (geralmente definidas posteriomente a criação do modelo)
            // ignora esse processamento
            if(!$field->getType()){
                continue;
            }

            // Campos do tipo GRID não são colocados
            if(ModelConfig::checkType($field, ModelConfig::GRID)){
                continue;
            }
    
            $type = ModelConfig::getType($field->getType());
            
            // Campos do tipo bigtext tem seu tipo modificacdo para text para evitar problemas de layout
            // com um textarea no cabeçalho do grid
            if(ModelConfig::checkType($field, ModelConfig::BIGTEXT)){
                $type = ModelConfig::getType( ModelConfig::TEXT );
            
                $field->setType( ModelConfig::TEXT );
                $field->setSize(100);
                $field->setWidth('20em');
                $field->setFormatter( $type->formatterForm() );
                $field->setFormatterView( null );
            }
    
            // Caso não haja a definição explícita de um formatter
            // nesse campo utiliza a padrão desse tipo no sistema
            if(!$field->getFormatter()){
                $field->setFormatter($type->getFormatterForm());
            }
            
            // Disponibiliza também o formatterView (usado na grid) para ser usado na exibição dos filtros na tela
            if(!$type->getFormatterFormView()){
                $field->setFormatterView("$.fn.form.formatter.defaultView");
            } else {
                $field->setFormatterView($type->getFormatterFormView());
            }
    
            // Caso haja a definição de um JS específico para o tipo o inclui no carregamento da página
            if( $type->getJs() ){
                $this->addJs( $type->getJs() );
            }
    
            // Caso haja a definição de um CSS específico para o tipo o inclui no carregamento da página
            if( $type->getCss() ){
                $this->addCss( $type->getCss() );
            }
    
            // Define o nome na base de dados como sendo um atributo a mais
            // para permitir que o javascript acesse esse valor
            $field->setNameDatabase ( $field->getNameDatabase() );
            
            // Num campo do tipo FK faz o carregamento dos dados diretamente pelo modelo referenciado
            if(ModelConfig::checkType($field, ModelConfig::FK)
            && $field->getData() === null // caso o atributo data não tenha sido definido
            && $field->getRemote() === null // não carrega dados no caso de pesquisa remota
            && $field->getDepends() === null){ // não carrega dados no caso do campo ser dependente
                
                $data = null;
                // Se for um form baseado num model
                if( $this->getOption(Grid::OPTION_MODEL) ){
                    $model = Model::factory($this->getOption(Grid::OPTION_MODEL));
                    // Se o field existir no model
                    if($model->getField( $field->getName() )){
                        $select = $model->prepareSelectFk($field);
                        $data = $select->query()->fetchAll();
                    }
                }
                // Caso não seja um Grid baseado num model tenta selecionar os dados conforme especificado no field
                else if($data == null){
                    if(!$field->getField(false) || !$field->getKey()){
                        throw new Exception("Um campo do tipo FK num precisa que seus atributos Field e Key estejam definidos.");
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
    
        }
    
        // Verifica se há formatters JS, no padrão de definição de javascript para
        // os modelos, que ainda não tiveram seus arquivos JS inseridos no escopo
        // caso encontre o arquivo JS correspondente faz a inserção na lista de Javascript
        $cmd = array();
        foreach($this->_filters as $field){
            $cmd[] = $field->getFormatter();
        }
        $this->_parseJsLines($cmd);
    
        // Retorna os campos preparados para o formulário
        return $this->_prepareRenderAttributeList($this->_filters);
    }
    
    /**
     * Prepara a criação do component de grid no Javascript e faz outras definições de renderização
     */
    protected function _prepareRenderOptions(){
        
        // Adiciona a coluna de botões caso haja botões incluídos e não
        // esteja definida a opção de ocultar botões
        if(count($this->_buttons) > 0 && $this->_checkOption(self::OPTION_BUTTONS_HIDE) == false ){
            $formatter = $this->_checkOption(self::OPTION_BUTTONS_FORMATTER);
            $this->addColumn( '__buttons', 'Ações', $formatter, 'center', 'center', $this->_checkOption(self::OPTION_BUTTONS_WIDTH), false );
        }
        
        $options = parent::_prepareRenderOptions();

        // Prepara os botões para serem repassados ao javascript
        $options['buttons'] = $this->_prepareRenderAttributeList($this->_buttons);
        
        // Prepara as colunas para serem repassadas ao javascript
        $options['columns'] = $this->_prepareRenderColumns();
        
        // Prepara os filtros para serem repassadas ao javascript
        $options['filters'] = $this->_prepareRenderFilters();
        
        $options = json_encode(Util::utf8_encode($options));
        
        // Faz ajustes no funcionamento do onload quando a tela é carregada via um XHR
        $this->_prepareRenderOptionsXhr();
        
        // Javascript padrão de inicialização do componente jquery de grid
        // adiciona como sendo o primeiro item do onload da página
        $this->addOnload( "$('".$this->_checkOption(self::OPTION_CONTAINER)."').grid(".$options.");", 0 );
        
        // Caso seja um XHR faz com que seja feita a chamada do callback do Fenix_Model.page depois
        // da criação do elemento
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
    
        // Faz o procedimento normal de geração de testes
        parent::_prepareJsTests();
    
        // Determina a lista completa de requires
        $requires = array();
        foreach($this->_js as $js){
            if(!in_array($js, $this->_jsDefault)){
                $requires[] = $this->_prepareFilesJoinRealFilename($js);
            }
        }
        
        // Diretório padrão de salvamento
        $dirname = "app_tests/fenix/view/js/jquery.grid/";
    
        // Prepara os testes de todos os formatters sendo utilizados nesse objeto Form
        foreach($this->getColumns() as $column){
    
            // Só considera os formatters padrão (do fenix)
            $formatter = $column->getFormatterOriginal() ? $column->getFormatterOriginal() : $column->getFormatter();
            
            if($formatter && stripos($formatter, "$.fn.grid") !== false){
                
                $functionList = array();
                $functionList[] = $formatter . " = function(text, record, column, grid, table, tr, td, formatter)";
                
                // Faz a geração das chamadas
        
                $variableValues = array();
                $variableValues["text"] = "'@TODO'";
                $variableValues["record"] = "{}";
                $variableValues["column"] = json_encode(Util::utf8_encode($column->getAttributes()));
                $variableValues["grid"] = "{}";
                $variableValues["table"] = "$('<table>').appendTo( $('body') )";
                $variableValues["tr"] = "$('<tr>').appendTo( $('table:last') )";
                $variableValues["td"] = "$('<td>').appendTo( $('tr:last') )";
                $variableValues["formatter"] = "function(text, record, column, grid, table, tr, td, formatter){ return text; }";
        
                $this->_prepareJsTestsGenerateStubFunctionCall($dirname, $functionList, $requires, $variableValues);
                
            }
            
        }
    
    }
}

/**
 * Classe para representar uma coluna de um Grid
 */
class GridColumn extends PageField {
    
    /**
     * Define se a coluna é ordenável através do clique na linha de cabeçalho
     * @var boolean
     */
    const SORTABLE = 'sortable';
    /**
     * Define o css da coluna no cabeçalho
     * @var string
     */
    const STYLE_HEADER = 'styleHeader';
    /**
     * Define o css da coluna no conteúdo
     * @var string
     */
    const STYLE_CONTENT = 'styleContent';
    
    /**
     * Disables field name validation
     * @var String
     */
    protected $_validateRegExp = null;
    
    /**
     * Define o valor do atributo
     * @param boolean $v
     */
    public function setSortable($v){ return $this->__set(self::SORTABLE, $v); }
    
    /**
     * Define o valor do atributo
     * @param String $v
     */
    public function setStyleHeader($v){ return $this->__set(self::STYLE_HEADER, $v); }
    
    /**
     * Define o valor do atributo
     * @param String $v
     */
    public function setStyleContent($v){ return $this->__set(self::STYLE_CONTENT, $v); }
    
    /**
     * Obtém o valor do atributo
     * @return boolean $v
     */
    public function getSortable(){ return $this->__get(self::SORTABLE); }
    
    /**
     * Obtém o valor do atributo
     * @return String $v
     */
    public function getStyleHeader($v){ return $this->__get(self::STYLE_HEADER); }
    
    /**
     * Obtém o valor do atributo
     * @return String $v
     */
    public function getStyleContent($v){ return $this->__get(self::STYLE_CONTENT); }
    
    /**
     * (non-PHPdoc)
     * @see AttributeList::_required()
     */
    protected function _required(){
        return array('name');
    }
    
    /**
     * 
     * @param String $name
     * @param String $title
     * @throws Exception
     * 
     * @return GridColumn
     */
    public static function factory($name, $title = null){
        $attributes = array('name' => $name);
        if($title != null){
            $attributes['title'] = $title;
        }
        return new self( $attributes );
    } 
    
}


/**
 * Classe para representar um filtro da grid
 * herda da classe FormField
 */
class GridFilter extends FormField { 
    
    /**
     *
     * @param String $name
     * @param String $title
     * @throws Exception
     *
     * @return GridFilter
     */
    public static function factory($name, $type){
        return new self( array('name' => $name, 'type' => $type) );
    }
    
}








