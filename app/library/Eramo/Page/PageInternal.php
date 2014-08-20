<?php

require_once 'Page.php';

/**
 * Classe abstrata para representar de forma gen�rica uma p�gina interna do sistema
 *
 * @copyright Eramo Software
 */
abstract class PageInternal extends Page {
    
    /**
     * Lista dos bot�es da p�gina
     * @var Button[]
     */
    protected $_buttonsPage = array();
    
    /** String - Define o id �nico do grid em uma tela */
    const OPTION_ID = 'id';
    /** String - Define o t�tulo do grid (a ser exibido ao usu�rio) */
    const OPTION_TITLE = 'title';
    /** boolean - Define o ID do elemento HTML (geralmente DIV) onde o componente  */
    const OPTION_CONTAINER = 'container';
    /** String - Define o model do sistema ao qual essa p�gina corresponde (opcional) */
    const OPTION_MODEL = 'model';
    /** String - Define a section do model definido por OPTION_MODEL do sistema ao qual essa p�gina corresponde (opcional) */
    const OPTION_SECTION = 'section';
    /** boolean - Define se o carregamento da p�gina est� sendo feito via XHR */
    const OPTION_XHR = 'xhr';
    /** String - Define a URI base para todo o carregamento de informa��es e p�ginas seguintes */
    const OPTION_BASE_URI = 'baseUri';
    
    /**
     * Lista de Options padr�o do componente
     *
     * @var String[]
     */
    protected $_options = array();
    
    /**
     * Constr�i o objeto padr�o definindo uma lista de Options
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
     * @return mixed O valor da constante ou false caso n�o esteja definida
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
     * Adiciona um novo bot�o ao rodap� ou topo da p�gina (conforme configura��o de cria��o do p�gina)
     *
     * @param String $title T�tulo a ser exibido no bot�o
     * @param String $action Nome da fun��o a ser executada, exemplo: "Fenix_Model.new()"
     * @param String $icon Classe css de �cone desse bot�o (classes icon-* do bootstrap)
     * @param String $extra Defini��es extras, exemplo: array('className' => 'btn-primary') para um bot�o prim�rio do Bootstrap
     */
    public function addButtonPage($title, $action = null, $icon = null, $extra = array()){
        $this->addButtonPageAt(null, $title, $action, $icon, $extra);
    }
    
    /**
     * Adiciona um novo bot�o ao rodap� ou topo da p�gina (conforme configura��o de cria��o do p�gina)
     *
     * @param String $pos Posi��o a ser inserido o bot�o
     * @param String $title T�tulo a ser exibido no bot�o
     * @param String $action Nome da fun��o a ser executada, exemplo: "Fenix_Model.new()"
     * @param String $icon Classe css de �cone desse bot�o (classes icon-* do bootstrap)
     * @param String $extra Defini��es extras, exemplo: array('className' => 'btn-primary') para um bot�o prim�rio do Bootstrap
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
     * Obt�m um bot�o
     *
     * @param String $title T�tulo do bot�o a ser localizado
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
     * Obt�m a lista de bot�es
     *
     * @return Button[]
     */
    public function getButtonsPage(){
        return $this->_buttonsPage;
    }

    /**
     * Remove um dos bot�es da p�gina
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
     * Prepara a renderiza��o de um AttributeList[]
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
     * Faz os procedimentos de prepara��o das Options para renderiza��o
     */
    protected function _prepareRenderOptions(){
        
        $options = $this->_options;
        
        // Prepara os bot�es da p�gina para serem repassados ao javascript
        $options['buttonsPage'] = $this->_prepareRenderAttributeList($this->_buttonsPage);
        
        return $options;
    }
    
    /**
     * (non-PHPdoc)
     * @see Page::prepareRender()
     */
    public function prepareRender($separateOnload = false){

        $this->_prepareRenderOptions();
        
        // Executa o procedimento de renderiza��o padr�o das p�ginas
        $view = parent::prepareRender($separateOnload);
        
        // Define o container padr�o da P�gina Interna
        $view->_container = $this->_checkOption( self::OPTION_CONTAINER );
        
        // Define o t�tulo da p�gina
        $view->_title = $this->_checkOption(self::OPTION_TITLE);
        
        return $view;
    }
    
    /**
     * Prepara um objeto para renderiza��o dentro dentro de um outro componente
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
     * Prepara um objeto para renderiza��o dentro dentro de um outro componente
     */
    public function prepareRenderXhr($separateOnload = false){
    
        $ret = array();
    
        $ret['options'] = $this->_prepareRenderOptions();
        
        // Remove os arquivos JS padr�o da tela (eles j� deve ter sido carregados)
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
     * Quando o carregamento � feito em XHR o onload deve ser colocado numa
     * estrutura esperado pelo Fenix_Model.page para que seja poss�vel controlar
     * o momento certo de execu��o do onload antes e depois da visibilidade do modal
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
        
        // Define se o onload ser� criado separadamente
        $separateOnload = $this->getOption(Form::OPTION_RECORD) || $separateOnload ? true : false;
        
        // Se � um carregamento via XHR faz um procedimento diferente do padr�o
        if($this->getOption(self::OPTION_XHR)){
            
            // Chama o procedimento de prepara��o de renderiza��o em XHR
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
            
            // Retorna diretamente o conte�do do JS
            header("Content-Type: text/javascript");            
            die($output);
        }
        
        return parent::render($separateOnload);
    }
}

class Button extends AttributeList {

    /**
     * T�tulo
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
     * Cria um novo bot�o para ser inserido numa grid
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
     * T�tulo do bot�o (exibi��o na interface)
     * @param string
     */
    public function setTitle($v){ return $this->__set(self::TITLE, $v); }

    /**
     * A��o do bot�o: javascript a ser executado no caso de bot�o da p�gina e refer�ncia no caso de bot�o de uma grid
     * @param string
     */
    public function setAction($v){ return $this->__set(self::ACTION, $v); }

    /**
     * Classe extra a ser inserida no bot�o
     * @param string
     */
    public function setClassName($v){ return $this->__set(self::CLASS_NAME, $v); }

    /**
     * Classe do �cone do bot�o (o padr�o � n�o ter �cone)
     * @param string
     */
    public function setIcon($v){ return $this->__set(self::ICON, $v); }

    /**
     * T�tulo do bot�o (exibi��o na interface)
     * @param string
     */
    public function getTitle(){ return $this->__get(self::TITLE); }

    /**
     * A��o do bot�o: javascript a ser executado no caso de bot�o da p�gina e refer�ncia no caso de bot�o de uma grid
     * @param string
     */
    public function getAction(){ return $this->__get(self::ACTION); }

    /**
     * Classe extra a ser inserida no bot�o
     * @param string
     */
    public function getClassName(){ return $this->__get(self::CLASS_NAME); }

    /**
     * Classe do �cone do bot�o (o padr�o � n�o ter �cone)
     * @param string
     */
    public function getIcon(){ return $this->__get(self::ICON); }
}

/**
 * Uma classe simples e gen�rica para exibi��o de um template qualquer no sistema
 *
 * @copyright Eramo Software
 * @since 02/2014
 */
class ViewInternal extends PageInternal {

    /**
     * T�tulo padr�o da P�gina
     * @var String
     */
    protected $_title = "";

    /**
     * Template PHP para exibi��o
     *
     * @var String
     */
    protected $_template = null;

    /**
     * Marcado para determinar se j� h� uma p�gina interna inclu�da
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

        // Redefine o css padr�o do sistema
        if($cssDefault === false){
            $this->_cssDefault = array();
        }

        // Redefine o js padr�o do sistema
        if($jsDefault === false){
            $this->_jsDefault = array();
        }

        parent::__construct();

        // Se n�o conseguir abrir da view arquivo diretamente faz uma busca em locais
        // prov�veis para tentar localizar o arquivo e redefinir o caminho antes de mostrar
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

        // Se ainda assim n�o for localizado o template lan�a a exec��o
        if(!is_file($template)){
            throw new Exception("Template '{$template}' not found");
        }

        // Define o template do objeto atual
        $this->_template = $template;

        $this->_urlPrefix = Util::getBaseUrl();
    }

    /**
     * Adiciona uma nova p�gina interna (Grid ou Form) ao escopo de execu��o
     *
     * @param String $name Nome da p�gina para refer�ncia dentro do JS
     * @param PageInternal $instancia de um objeto PageInternal
     * @param boolean $ignoreCss Ignora a inclus�o dos arquivos CSS
     * @param boolean $ignoreJs Ignora a inclus�o dos arquivos JS
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


















