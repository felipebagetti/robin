<?php

class ModelController extends Fenix_ModelController {

    /**
     * Define se a instância do Ace foi iniciada
     * @var boolean
     */
    protected static $_aceStarted = false;
    
    /**
     * Armazena a lista de itens de ajuda do sistema
     * @var String[]
     */
    protected $_help = array();
    
    /**
     * Determina se a ajuda será inicializada ao carregar a página
     * @var boolean
     */
    protected $_helpAutostart = false;
    
    /**
     * Faz a preparação do JS
     *
     * @param PageInternal $page
     * @param Page $view
     */
    protected function _prepareAceMenu(Page $view){
    
        $menu = array();
        
//         $menu[] = array( 'title' => 'Página Inicial', 'href' => 'home/', 'icon' => 'icon-home' );    
//         $menu[] = array( 'title' => 'Dashboard', 'href' => 'home/', 'icon' => 'icon-dashboard');
        $menu[] = array( 'title' => 'Metas', 'href' => 'goal/calendar', 'icon' => 'icon-calendar');
        $menu[] = array( 'title' => 'Buscar Usuários', 'href' => 'user/', 'icon' => 'icon-search');
        $menu[] = array( 'title' => 'Meus Amigos', 'href' => 'friendship/', 'icon' => 'icon-user');
//         $menu[] = array( 'title' => 'Grupos', 'href' => 'team/', 'icon' => 'icon-users');
        $menu[] = array( 'title' => 'Meu Perfil', 'href' => 'user/profile?id='.Zend_Auth::getInstance()->getIdentity()->id, 'icon' => 'icon-cog');
        
    
        // Método de impressão do HTML do menu
        $printMenu = function($menu, $isSubmenu = false) use (&$printMenu){
            $html = "";
            $requestUri = $_SERVER['REQUEST_URI'];
            // Ajuste no caso de um .../record?id=XXXX para deixar ativado o link que leva à grid no menu do sistema
            if((stripos($_SERVER['REQUEST_URI'], "/record") !== false && stripos($_SERVER['REQUEST_URI'], "venda/record") === false) || stripos($_SERVER['REQUEST_URI'], "venda/record?id=") !== false ){
                $requestUri = str_replace("/record", "/", $requestUri);
            }
            $requestUri = substr($requestUri, 0, strpos($requestUri, "?") ? strpos($requestUri, "?") : strlen($requestUri));
            foreach($menu as $item){
    
                $ativo = false;
                
                $hrefSemHash = $item['href'];
                if($hashPosition = strpos($item['href'], "#")){
                    $hrefSemHash = substr($item['href'], 0, $hashPosition);
                }
                
                if( preg_match("/".preg_quote("/".$hrefSemHash, "/")."$/", $requestUri) ){
                    $ativo = true;
                }
                
                $item['href'] = Util::getBaseUrl() . $item['href'];
                
                if($isSubmenu == true && !isset($item['icon'])){
                    $item['icon'] = 'icon-double-angle-right';
                }
    
                $html .= '<li class="'.($ativo ? ' active' : '').'">';
                $html .= '<a class="'.(isset($item['submenu']) ? 'dropdown-toggle' : '').'" href="'.$item['href'].'">';
                if(isset($item['icon'])){
                    $html .= "<i class='".$item['icon']."'></i>";
                }
    
                $html .= '<span class="menu-text"> '.$item['title'].' </span>';
    
                $html .= "</a>";
    
                if(isset($item['submenu'])){
                    $html .= '<ul class="submenu">';
                    $html .=  $printMenu($item['submenu'], true);
                    $html .= '</ul>';
                }
    
                $html .= "</li>";
            }
            return $html;
        };
    
//         header('HTTP/1.1 412 Precondition Failed', true);
//         print_r(htmlentities($printMenu( $menu )));
//         die();
        
        $view->menu = $printMenu( $menu );
    
    }
        
    /**
     * Determina a versão do IE sendo executada
     * @return null caso não seja IE e um número inteiro maior que zero (a versão) caso seja IE
     */
    protected function _ieVersion(){
        
        $ret = null;
        
        preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
        
        if (count($matches)>1){
            $ret = intval($matches[1]);
        }
        
        return $ret;
    }
        
    /**
     * Determina sé um IOS
     * @return null caso não seja IOS
     */
    protected function _ios(){
        
        $ret = null;
        
        preg_match('/ios/i', $_SERVER['HTTP_USER_AGENT'], $matches);
        
        if (count($matches)>=1){
            $ret = true;
        }
        
        return $ret;
    }
    
    /**
     * Faz a preparação do CSS
     * 
     * @param PageInternal $page
     * @param Page $view
     */
    protected function _prepareAceCss(Page $view, PageInternal $page = null){
        
        $view->addCss("robin/bootstrap.css");
        
        $view->addCss("robin/font-awesome.css");
        
        if($this->_ieVersion() !== null && $this->_ieVersion() === 7){
            $view->addCss("robin/font-awesome-ie7.css");
        }
        
        $view->addCss('robin/fullcalendar.css');
        
        $view->addCss("robin/ace.css");
        $view->addCss("robin/ace-rtl.css");
        $view->addCss("robin/ace-skins.css");
        $view->addCss("robin/robin.css");
        
        if($this->_ieVersion() !== null && $this->_ieVersion() <= 8){
            $view->addCss("robin/ace-ie.css");
        }
        
        if($this->_ios()){
            $view->addCss("robin/ace-ios.css");
        }
        
        $view->addCss("fenix/bootstrap-modal.css");
        $view->addCss("fenix/datepicker.css");
        $view->addCss("fenix/select2.css");
        $view->addCss("fenix/select2.defaults.css");
        $view->addCss("fenix/typeahead.css");
        
        $view->addCss("robin/fenix.css");
        
    }
    
    /**
     * Faz a preparação do JS
     * 
     * @param PageInternal $page
     * @param Page $view
     */
    protected function _prepareAceJs(Page $view, PageInternal $page = null){
        
        $view->addJs("robin/ace-extra.js");
        
        // Caso seja IE menor que 8 inclui arquivos JS
        // de HTML5 e Responsiveness
        if($this->_ieVersion() !== null && $this->_ieVersion() <= 8){
            $view->addJs("robin/html5shiv.js");
            $view->addJs("robin/respond.js");
        }
        
        // Se não é o IE usa o jquery 2.x
        if($this->_ieVersion() === null){
            $view->addJs("fenix/jquery.js");
        }
        // Caso seja IE usa o jquery 1.x
        else {
            $view->addJs("robin/jquery-1.10.2.js");
        }
        
        $view->addJs("fenix/sha1.js");
        $view->addJs("fenix/jquery.upload.js");
        $view->addJs("fenix/jquery.autosize.js");
        
        $view->addJs("robin/bootstrap.js");
        $view->addJs("robin/typeahead-bs2.min.js");
        
        $view->addJs("robin/ace-elements.js");
        $view->addJs("robin/ace.js");
        
        // Funcionamento da grid/fenix
        
        $view->addJs('fenix/bootstrap-modal.js');
        $view->addJs('fenix/bootstrap-modalmanager.js');
        
        $view->addJs('fenix/jquery.timePicker.js');
        $view->addJs('fenix/datepicker.js');
        
        $view->addJs("fenix/Fenix.js");
        $view->addJs("fenix/Fenix_Model.js");
        
        $view->addJs("robin/ace-ios.js");
        
        $view->addJs("robin/Robin_Misc.js");
        
    }
    
    /**
     * Prepara um componente Grid/Form para ser mostrado dentro do template padrão do sistema 
     * @param PageInternal $page
     * @return View
     */
    protected function _prepareAce(PageInternal $page = null){

        self::$_aceStarted = true;
        
        $view = new ViewAce("ace.html.php", false, false);
        
        $view->breadcumbs = array();
        
        // Preparação do CSS
        $this->_prepareAceCss($view, $page);
        
        // Preparação do JS
        $this->_prepareAceJs($view, $page);

        // Preparação da estrutura de menu do sistema
        $this->_prepareAceMenu($view);

        // Notificações do sistema
        $this->_prepareAceNotifications($view);
        
        // Carrega a página (Form ou Grid)
        if($page !== null){
            
            $view->title = $page->getOption(PageInternal::OPTION_TITLE);
            $view->descrition = "";
            $view->showHeader = true;
            
            $page->setOption(PageInternal::OPTION_TITLE, "");
            
            // Prepara a estrutura para renderizar o grid/form dentro do template padrão do sistema
            $view->addPage("pageInternal", $page);
            
            if($page instanceof Form){
                $view->addOnload("$('#element-container').form( View.pageInternal );");
            }
            
            if($page instanceof Grid){
                $view->addOnload("$('#element-container').grid( View.pageInternal );");
            }
            
            $view->addOnload(" if(ViewOnload.pageInternal) ViewOnload.pageInternal(); ");
            
        }

        return $view;
    }
    
    /**
     * Faz a preparação do JS
     *
     * @param PageInternal $page
     * @param Page $view
     */
    protected function _prepareAceNotifications(Page $view){
    	
    	$idUser = Zend_Auth::getInstance()->getIdentity()->id;
    	
    	$select = Model::factory("friendship")->prepareSelect();
    	$select->columns( array('user_picture' => 'user_user_1.picture') );
    	$select->where('friendship.status = ?', Friendship::PENDENTE);
    	$select->where('friendship.id_user_2 = ?', $idUser);
    	$select->order('friendship.id DESC');
    	$select->limit('10');
    	
    	$notification = array();
    	$notification['url'] = Util::getBaseUrl();
    	$notification['data'] = $select->query()->fetchAll();
    	$notification['unread'] = count($notification['data']);

    	$view->addOnload("Robin_Misc.notification(".json_encode(Util::utf8_encode($notification)).")");
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_indexAction()
     */
    protected function _indexAction(){
        
        $prepareAce = false;
        
        if(self::$_aceStarted === false){
            if(!Util::isXhr()){
                self::$_aceStarted = true;
                $prepareAce = true;
            }
        }
        
        $grid = parent::_indexAction();
        
        $buttonCount = count( $grid->getButtons() );
        $buttonWidth = 3;
        $margin = 4;
        $grid->addOnload("$('th:contains(Ações)', Fenix_Model.grids.last()).css('width', '".(($buttonCount*$buttonWidth)+$margin)."em')");
        
        return $prepareAce ? $this->_prepareAce( $grid ) : $grid;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_recordAction()
     */
    protected function _recordAction(){

        $prepareAce = false;
        
        if(self::$_aceStarted === false){
            if(!Util::isXhr()){
                self::$_aceStarted = true;
                $prepareAce = true;
            }
        }
        
        $form = parent::_recordAction();
        
        return $prepareAce ? $this->_prepareAce( $form ) : $form;
    }
    
    /**
     * Modifica a baseUri para sempre ocultar o /robin/ da url, como aplicacão
     * só tem um módulo, não teremos problema
     * 
     * @param PageInternal $page
     */
    protected function _setBaseUri(PageInternal $page){
        $baseUri = $page->getOption(PageInternal::OPTION_BASE_URI);
        $baseUri = str_replace(Util::getBaseUrl()."robin/", Util::getBaseUrl(), $baseUri);
        $page->setOption(Grid::OPTION_BASE_URI, $baseUri);
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_getGrid()
     */
    protected function _getGrid(){
        
        $grid = parent::_getGrid();
        
        $grid->addJs('robin/Robin_Misc.js');
        
        $grid->setOption(Grid::OPTION_BUTTONS_FORMATTER, 'Robin_Misc.buttonsFormatter');
        $grid->setOption(Grid::OPTION_SEARCH_AUTOFOCUS, false);
        $grid->setOption(Grid::OPTION_LOAD_CALLBACK, 'Robin_Misc.changePaginator');
        
        $grid->addOnload("Robin_Misc.novoRegistroFocus();");
        
        // Desabilita a opção de visualizar registros
        $grid->deleteButton('Visualizar');
        
        // Define o tamanho de todos os botões em sm ao invés do padrão xs do fenix
        foreach($grid->getButtonsPage() as $button){
            $button->setClassName( str_replace("btn-xs", "btn-sm", $button->getClassName()) );
        }

        // Mudar texto do botão 'Fechar' para 'Voltar'
        if( $grid->getButtonPage('Fechar') ){
            $grid->deleteButtonPage('Fechar');
        }

        // Mudar ícone padrão de novo registro
        if( $grid->getButtonPage('Novo Registro') ){
            $grid->getButtonPage('Novo Registro')->setAttribute('icon', 'icon-plus');
        }
        
        // Modifica a baseUri
        $this->_setBaseUri($grid);
        
        return $grid;  
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_getForm()
     */
    protected function _getForm(){
        
        $form = parent::_getForm();
        
        $form->addJs('robin/Robin_Misc.js');
        
        // Desatia o foco automática na alteração de registros
        $record = $form->getOption(Form::OPTION_RECORD);
        if( $record && isset($record['id']) ){
            $form->setOption(Form::OPTION_AUTOFOCUS, false);
        }
        
        // Personaliza o formatter dos campos do tipo option
        foreach ($form->getFields() as $field){
            if(ModelConfig::checkType($field, ModelConfig::OPTION)){
                $field->setFormatter('Robin_Misc.formatterOption');
            }
        }

        foreach($form->getButtonsPage() as $button){
            
            // Mudar texto do botão 'Cancelar' para 'Voltar'
            if($button->getTitle() == 'Cancelar'){
                if(Util::isXhr()){
                    $form->deleteButtonPage('Cancelar');
                } else {
                    $form->getButtonPage('Cancelar')->setTitle('Voltar')->setClassName('btn-default btn-sm')->setIcon('icon-undo');
                }
            }
            
            // Mudar a cor do botão de excluir
            if($button->getTitle() == 'Excluir'){
                $button->setClassName('btn-danger btn-sm');
            }
            
            // Redefine o uso dos ícones
            $button->setIcon( str_replace("glyphicon", "icon", $button->getIcon()) );
            $button->setIcon( str_replace("icon-trash", "icon-trash-o", $button->getIcon()) );
            
            // Define o tamanho de todos os botões em sm ao invés do padrão xs do fenix
            $button->setClassName( str_replace("btn-xs", "btn-sm", $button->getClassName()) );
        }
        
        // Modifica a baseUri
        $this->_setBaseUri($form);
        
        return $form;  
    }
    
    /**
     * Insere um modelo qualquer (que tenha uma chave fk id_doacao) como uma aba do modelo
     *
     * @param Form $form
     * @param Model $model
     * @param String $titulo
     *
     * return Grid Grid inserido pelo procedimento
     */
    protected function _getFormPrepareGridSubcadastro(Form $form, Model $model, $titulo){
    
    	$grid = $this->_getFormAddGrid($form, $model);
    
    	$record = $form->getOption(Form::OPTION_RECORD);
    
    	$title = Util::normalize( $grid->getOption(Grid::OPTION_TITLE) );
    
    	$form->setFieldAttribute('tab_'.$title, FormField::TITLE, $titulo);
    	
    	$grid->setOption(Grid::OPTION_TITLE, '');
    
    	return $grid;
    }
    
}

class ViewAce extends ViewInternal {

    /**
     * Adiciona uma nova página interna (Grid ou Form) ao escopo de execução
     *
     * @param String $name Nome da página para referência dentro do JS
     * @param PageInternal $instancia de um objeto PageInternal
     * @param boolean $ignoreCss Ignora a inclusão dos arquivos CSS
     * @param boolean $ignoreJs Ignora a inclusão dos arquivos JS
     */
    public function addPage($name, PageInternal $page, $ignoreCss = false, $ignoreJs = false){

        parent::addPage($name, $page, true, true);
        
        if($ignoreCss === false){
            $this->addPageCss($page);
        }
        
        if($ignoreJs === false){
            $this->addPageJs($page);
        }
        
    }
    
    /**
     * Inclui somente arquivos CSS que não sejam do jquery ou boostrap
     */
    public function addPageCss(PageInternal $page){
        if($page !== null){
            foreach($page->getCss() as $css){
                if(!in_array($css, array("fenix/bootstrap.css","fenix/fenix.css"))){
                    $this->addCss($css);
                }
            }
        }
    }
    
    /**
     * Inclui somente arquivos JS que não sejam do jquery ou boostrap
     */
    public function addPageJs(PageInternal $page){
        if($page !== null){
            foreach($page->getJs() as $js){
                if(stripos($js, "jquery.js") === false && stripos($js, "fenix/bootstrap") === false){
                    $this->addJs($js);
                }
            }
        }
    }

}

















