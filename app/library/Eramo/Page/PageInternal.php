<?php

require_once 'Page.php';

/**
 * Classe abstrata para representar de forma genérica uma página interna do sistema
 *
 * @copyright Eramo Software
 */
abstract class PageInternal extends Page {
    
    /**
     * Lista dos botões da página
     * @var Button[]
     */
    protected $_buttonsPage = array();
    
    /** String - Define o id único do grid em uma tela */
    const OPTION_ID = 'id';
    /** String - Define o título do grid (a ser exibido ao usuário) */
    const OPTION_TITLE = 'title';
    /** boolean - Define o ID do elemento HTML (geralmente DIV) onde o componente  */
    const OPTION_CONTAINER = 'container';
    /** String - Define o model do sistema ao qual essa página corresponde (opcional) */
    const OPTION_MODEL = 'model';
    /** String - Define a section do model definido por OPTION_MODEL do sistema ao qual essa página corresponde (opcional) */
    const OPTION_SECTION = 'section';
    /** boolean - Define se o carregamento da página está sendo feito via XHR */
    const OPTION_XHR = 'xhr';
    /** String - Define a URI base para todo o carregamento de informações e páginas seguintes */
    const OPTION_BASE_URI = 'baseUri';
    
    /**
     * Lista de Options padrão do componente
     *
     * @var String[]
     */
    protected $_options = array();
    
    /**
     * Constrói o objeto padrão definindo uma lista de Options
     *
     * @param unknown_type $options
     */
    public function __construct($options = array()){
        parent::__construct();
    
        foreach($options as $option => $value){
            $this->_options[$option] = $value;
        }
        
    }
    
    /**
     * Verifica se uma OPTION foi definida e a retorna
     *
     * @param String $option Uma das constantes ::OPTION_*
     *
     * @return mixed O valor da constante ou false caso não esteja definida
     */
    protected function _checkOption($option){
        $ret = false;
        if( isset($this->_options[$option]) && $this->_options[$option]){
            $ret = $this->_options[$option];
        }
        return $ret;
    }
    
    /**
     * Define o valor de uma Option
     * 
     * @param String $option Uma das constantes em ::OPTION_*
     * @param String $value Valor da Option
     * @return PageInternal
     */
    public function setOption($option, $value){
        $this->_options[$option] = $value;
        return $this;
    }
    
    /**
     * Retorna o valor definido atualmente para uma Option
     * 
     * @param String $option Uma das constantes em ::OPTION_*
     * @return mixed
     */
    public function getOption($option){
        return $this->_checkOption($option);
    }

    /**
     * Adiciona um novo botão ao rodapé ou topo da página (conforme configuração de criação do página)
     *
     * @param String $title Título a ser exibido no botão
     * @param String $action Nome da função a ser executada, exemplo: "Fenix_Model.new()"
     * @param String $icon Classe css de ícone desse botão (classes icon-* do bootstrap)
     * @param String $extra Definições extras, exemplo: array('className' => 'btn-primary') para um botão primário do Bootstrap
     */
    public function addButtonPage($title, $action = null, $icon = null, $extra = array()){
        $this->addButtonPageAt(null, $title, $action, $icon, $extra);
    }
    
    /**
     * Adiciona um novo botão ao rodapé ou topo da página (conforme configuração de criação do página)
     *
     * @param String $pos Posição a ser inserido o botão
     * @param String $title Título a ser exibido no botão
     * @param String $action Nome da função a ser executada, exemplo: "Fenix_Model.new()"
     * @param String $icon Classe css de ícone desse botão (classes icon-* do bootstrap)
     * @param String $extra Definições extras, exemplo: array('className' => 'btn-primary') para um botão primário do Bootstrap
     */
    public function addButtonPageAt($pos, $title, $action = null, $icon = null, $extra = array()){
        
        if( !($title instanceof Button) ){
            
            $title = Button::factory($title, $action, $this);
            
            if($icon != null){
                $title->setIcon($icon);
            }
    
            foreach($extra as $key => $value){
                $title->__set($key, $value);
            }
        }
    
        $className = $title->getClassName() ? ' '.$title->getClassName() : '';
        $title->setClassName($className);
    
        if($pos === null){
            $pos = count($this->_buttonsPage);
        }
        
        $this->_buttonsPage = array_merge( array_slice($this->_buttonsPage, 0, $pos), array($title), array_slice($this->_buttonsPage, $pos) );
    }
    
    /**
     * Obtém um botão
     *
     * @param String $title Título do botão a ser localizado
     *
     * @return Button
     */
    public function getButtonPage($title){
        foreach($this->_buttonsPage as $button){
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
    public function getButtonsPage(){
        return $this->_buttonsPage;
    }

    /**
     * Remove um dos botões da página
     *
     * @param String $title
     */
    public function deleteButtonPage($title){
        foreach($this->_buttonsPage as $key => $button){
            if($button->getTitle() == $title){
                unset($this->_buttonsPage[$key]);
            }
        }
        $this->_buttonsPage = array_values($this->_buttonsPage);
    }
    
    /**
     * Prepara a renderização de um AttributeList[]
     * 
     * @return String[][] Todos os objetos listados em formato de array
     */
    protected function _prepareRenderAttributeList($attributeList){
        $ret = array();
        foreach($attributeList as $obj){
            $ret[] = $obj->getAttributes();
        }
        return $ret;
    }
    
    /**
     * Faz os procedimentos de preparação das Options para renderização
     */
    protected function _prepareRenderOptions(){
        
        $options = $this->_options;
        
        // Prepara os botões da página para serem repassados ao javascript
        $options['buttonsPage'] = $this->_prepareRenderAttributeList($this->_buttonsPage);
        
        return $options;
    }
    
    /**
     * (non-PHPdoc)
     * @see Page::prepareRender()
     */
    public function prepareRender($separateOnload = false){

        $this->_prepareRenderOptions();
        
        // Executa o procedimento de renderização padrão das páginas
        $view = parent::prepareRender($separateOnload);
        
        // Define o container padrão da Página Interna
        $view->_container = $this->_checkOption( self::OPTION_CONTAINER );
        
        // Define o título da página
        $view->_title = $this->_checkOption(self::OPTION_TITLE);
        
        return $view;
    }
    
    /**
     * Prepara um objeto para renderização dentro dentro de um outro componente
     */
    public function prepareRenderInline(){
    
        $ret = array();
    
        $ret['onload'] = $this->_onload;
        
        $ret['options'] = $this->_prepareRenderOptions();
        
        $ret['css'] = $this->_css;
        $ret['js'] = $this->_js;
        
        return $ret;
    }
    
    /**
     * Prepara um objeto para renderização dentro dentro de um outro componente
     */
    public function prepareRenderXhr($separateOnload = false){
    
        $ret = array();
    
        $ret['options'] = $this->_prepareRenderOptions();
        
        // Remove os arquivos JS padrão da tela (eles já deve ter sido carregados)
        foreach($this->_jsDefault as $jsDefault){
            foreach($this->_js as $hash => $js){
                if($js == $jsDefault){
                    unset($this->_js[$hash]);
                }
            }
        }
        
        // Prepara um arquivo JS dessa tela sem o documentReady
        $ret['js'] = $this->prepareJs(false, $separateOnload);
        
        return $ret;
    }
    
    /**
     * Quando o carregamento é feito em XHR o onload deve ser colocado numa
     * estrutura esperado pelo Fenix_Model.page para que seja possível controlar
     * o momento certo de execução do onload antes e depois da visibilidade do modal
     */
    protected function _prepareRenderOptionsXhr(){
        if($this->getOption(self::OPTION_XHR)){
            $onload = $this->_onload;
    
            $this->_onload = array();
            $this->addOnload("Fenix_Model.pageOnload = function( modal ){ ".implode("; ", $onload)." }");
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Page::render()
     */
    public function render($separateOnload = false){
        
        // Define se o onload será criado separadamente
        $separateOnload = $this->getOption(Form::OPTION_RECORD) || $separateOnload ? true : false;
        
        // Se é um carregamento via XHR faz um procedimento diferente do padrão
        if($this->getOption(self::OPTION_XHR)){
            
            // Chama o procedimento de preparação de renderização em XHR
            $ret = $this->prepareRenderXhr($separateOnload);
            
            $output = "";
            
            foreach($ret['js'] as $js){
                if(strpos($js, "?") !== false){
                    $js = explode("?", $js);
                    $js = current($js);
                }
                
                // Localiza o arquivo JS criado para essa tela
                $filename = explode("/", $js);
                $filename = "tmp/files/".end($filename);
            
                if(!$filename.".utf8"){
                    $content = utf8_encode(file_get_contents($filename));
                    file_put_contents($filename.".utf8", $content);
            
                    $output .= ";".$content;
                }
            }
            
            $output .= ";".$this->_prepareJsOnloadString();
            
            // Retorna diretamente o conteúdo do JS
            header("Content-Type: text/javascript");            
            die($output);
        }
        
        return parent::render($separateOnload);
    }
}

class Button extends AttributeList {

    /**
     * Título
     * @var string
     */
    const TITLE = 'title';
    
    /**
     * Action
     * @var string
     */
    const ACTION = 'action';
    
    /**
     * ClassName
     * @var string
     */
    const CLASS_NAME = 'className';
    
    /**
     * Icon
     * @var string
     */
    const ICON = 'icon';
    
    /**
     * Constantes para Grid
     * @var String
     */
    const GRID_DELETE = '__gridDelete';
    const GRID_EDIT = '__gridEdit';
    const GRID_VIEW = '__gridView';

    /**
     * Constantes para Page
     * @var String
     */
    const PAGE_BACK = '__pageBack';
    const PAGE_CLOSE = '__pageClose';
    const PAGE_NEW = '__pageNew';

    /**
     * Constantes para Page
     * @var String
     */
    const FORM_CANCEL = '__formCancel';
    const FORM_EDIT = '__formEdit';
    const FORM_DELETE = '__formDelete';
    const FORM_SAVE = '__formSave';
    const FORM_CONFIRM = '__formConfirm';

    /**
     * (non-PHPdoc)
     * @see AttributeList::_required()
     */
    protected function _required(){
        return array('title', 'action');
    }

    /**
     * Cria um novo botão para ser inserido numa grid
     *
     * @param String $title
     * @param String $action
     * @throws Exception
     *
     * @return Button
     */
    public static function factory($title, $action = null, PageInternal $page){

        $pageId = $page->getOption(PageInternal::OPTION_ID);
        
        $attributes = array();

        switch ($title){
            case self::GRID_DELETE:
                $attributes[self::TITLE] = 'Excluir';
                $attributes[self::ACTION] = 'Fenix_Model.delete';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-trash';
                break;
            case self::GRID_EDIT:
                $attributes[self::TITLE] = 'Alterar';
                $attributes[self::ACTION] = 'Fenix_Model.edit';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-edit';
                break;
            case self::GRID_VIEW:
                $attributes[self::TITLE] = 'Visualizar';
                $attributes[self::ACTION] = 'Fenix_Model.view';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-file';
                break;
                
            case self::PAGE_BACK:
                $attributes[self::TITLE] = 'Voltar';
                $attributes[self::ACTION] = 'Fenix_Model.back()';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-backward';
                break;
            case self::PAGE_CLOSE:
                $attributes[self::TITLE] = 'Fechar';
                $attributes[self::ACTION] = 'Fenix_Model.close()';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
//                 $attributes[self::ICON] = 'glyphicon glyphicon-step-backward';
                break;
            case self::PAGE_NEW:
                $attributes[self::TITLE] = 'Novo Registro';
                $attributes[self::ACTION] = "Fenix_Model.new( __PAGE__ )";
                $attributes[self::CLASS_NAME] = 'btn btn-primary btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-file';
                break;
                
            case self::FORM_CANCEL:
                $attributes[self::TITLE] = 'Cancelar';
                $attributes[self::ACTION] = "Fenix_Model.cancel( __PAGE__ )";
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                break;
            case self::FORM_EDIT:
                $attributes[self::TITLE] = 'Alterar';
                $attributes[self::ACTION] = 'Fenix_Model.editRecord ( __PAGE__ )';
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-edit';
                break;
            case self::FORM_DELETE:
                $attributes[self::TITLE] = 'Excluir';
                $attributes[self::ACTION] = "Fenix_Model.delete(null, __PAGE__ )";
                $attributes[self::CLASS_NAME] = 'btn btn-default btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-trash';
                break;
            case self::FORM_SAVE:
                $attributes[self::TITLE] = 'Salvar';
                $attributes[self::ACTION] = "Fenix_Model.save( __PAGE__ )";
                $attributes[self::CLASS_NAME] = 'btn btn-primary btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-white glyphicon-file';
                break;
            case self::FORM_CONFIRM:
                $attributes[self::TITLE] = 'Confirmar';
                $attributes[self::ACTION] = "Fenix_Model.save( __PAGE__ )";
                $attributes[self::CLASS_NAME] = 'btn btn-primary btn-xs';
                $attributes[self::ICON] = 'glyphicon glyphicon-file icon-white';
                break;
        }

        if(!isset($attributes[self::TITLE])){
            $attributes[self::TITLE] = $title;
        }

        if(!isset($attributes[self::ACTION])){
            $attributes[self::ACTION] = $action;
        }

        return new self($attributes);
    }
    
    /**
     * Título do botão (exibição na interface)
     * @param string
     */
    public function setTitle($v){ return $this->__set(self::TITLE, $v); }

    /**
     * Ação do botão: javascript a ser executado no caso de botão da página e referência no caso de botão de uma grid
     * @param string
     */
    public function setAction($v){ return $this->__set(self::ACTION, $v); }

    /**
     * Classe extra a ser inserida no botão
     * @param string
     */
    public function setClassName($v){ return $this->__set(self::CLASS_NAME, $v); }

    /**
     * Classe do ícone do botão (o padrão é não ter ícone)
     * @param string
     */
    public function setIcon($v){ return $this->__set(self::ICON, $v); }

    /**
     * Título do botão (exibição na interface)
     * @param string
     */
    public function getTitle(){ return $this->__get(self::TITLE); }

    /**
     * Ação do botão: javascript a ser executado no caso de botão da página e referência no caso de botão de uma grid
     * @param string
     */
    public function getAction(){ return $this->__get(self::ACTION); }

    /**
     * Classe extra a ser inserida no botão
     * @param string
     */
    public function getClassName(){ return $this->__get(self::CLASS_NAME); }

    /**
     * Classe do ícone do botão (o padrão é não ter ícone)
     * @param string
     */
    public function getIcon(){ return $this->__get(self::ICON); }
}

/**
 * Uma classe simples e genérica para exibição de um template qualquer no sistema
 *
 * @copyright Eramo Software
 * @since 02/2014
 */
class ViewInternal extends PageInternal {

    /**
     * Título padrão da Página
     * @var String
     */
    protected $_title = "";

    /**
     * Template PHP para exibição
     *
     * @var String
     */
    protected $_template = null;

    /**
     * Marcado para determinar se já há uma página interna incluída
     * @var boolean
     */
    protected $_hasPage = false;

    /**
     * (non-PHPdoc)
     * @see Page::_template()
     */
    protected function _template(){
        return $this->_template;
    }

    /**
     * Cria um objeto view
     * @param String $template Template PHP a ser criado
     * @throws Exception
     * @return View
     */
    public static function factory($template){
        return new self($template);
    }

    /**
     *
     * @param String $template
     * @throws Exception
     */
    public function __construct($template, $cssDefault = true, $jsDefault = true){

        // Redefine o css padrão do sistema
        if($cssDefault === false){
            $this->_cssDefault = array();
        }

        // Redefine o js padrão do sistema
        if($jsDefault === false){
            $this->_jsDefault = array();
        }

        parent::__construct();

        // Se não conseguir abrir da view arquivo diretamente faz uma busca em locais
        // prováveis para tentar localizar o arquivo e redefinir o caminho antes de mostrar
        // um erro
        if(!is_file($template)){

            $dispatcher = Eramo_Controller_Front::getInstance()->getDispatcher();

            $prefix = null;

            if($dispatcher['module'] == "default"){
                $prefix = "app/fenix/view/html/";
            } else {
                $prefix = "app/modules/".$dispatcher['module']."/view/html/";
            }

            if(is_file($prefix . $template)){
                $template = $prefix . $template;
            }

        }

        // Se ainda assim não for localizado o template lança a execção
        if(!is_file($template)){
            throw new Exception("Template '{$template}' not found");
        }

        // Define o template do objeto atual
        $this->_template = $template;

        $this->_urlPrefix = Util::getBaseUrl();
    }

    /**
     * Adiciona uma nova página interna (Grid ou Form) ao escopo de execução
     *
     * @param String $name Nome da página para referência dentro do JS
     * @param PageInternal $instancia de um objeto PageInternal
     * @param boolean $ignoreCss Ignora a inclusão dos arquivos CSS
     * @param boolean $ignoreJs Ignora a inclusão dos arquivos JS
     */
    public function addPage($name, PageInternal $page, $ignoreCss = false, $ignoreJs = false){

        $options = $page->prepareRenderInline();

        if($ignoreCss === false){
            $this->addCss($options['css']);
        }

        if($ignoreJs === false){
            $this->addJs($options['js']);
        }

        if($this->_hasPage == false){
            $this->addOnload(" View = {}; ");
            $this->addOnload(" ViewOnload = {}; ");
            $this->_hasPage = true;
        }

        $this->addOnload(" View['{$name}'] = {$options['options']}; ");
        $this->addOnload(" ViewOnload['{$name}'] = function(){ ".implode("; ", $options['onload'])." }; ");

    }

    public static function formatDate($v){
        return implode("/", array_reverse(explode("-", substr($v, 0, 10))));
    }

    public static function formatCurrency($v){
        return number_format($v, 2, ",", ".");
    }
    
    /**
     * (non-PHPdoc)
     * @see PageInternal::render()
     */
    public function render($separateOnload = true){
        return parent::render($separateOnload);
    }
}


















