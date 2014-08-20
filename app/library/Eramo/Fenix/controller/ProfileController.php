<?php

abstract class Fenix_ProfileController extends Fenix_ModelController {

    /**
     * Determina uma lista de model que serão removidos das permissões (não haverá controle de permissões)
     */
    protected $_removedModels = array();
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_model()
     */
    protected function _model(){
        return Util::getConfig(Util::CONFIG_PROFILE)->model;
    }
    
    /**
     * @return Fenix_Profile
     */
    protected function _getModel(){
        return parent::_getModel();
    }
    
    /**
     * Personalizações da grid principal
     * @return Grid
     */
    protected function _getGridPerfil(){
        
        $this->_setGridOption(Grid::OPTION_BUTTONS_WIDTH, '16em');
        
        $grid = parent::_getGrid();
        
        return $grid;
    }
    
    /**
     * Remove um modelo da lista de permissões do sistema
     * @param unknown $model
     */
    public function _removeModel($name){
        if($name == null){
            throw Exception("\$name precisa ser definido.");
        }
        if($name instanceof Model){
            $name = $name->getName();
        }
        $this->_removedModels[] = $name;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_getForm()
     */
    protected function _getForm(){
        
        $form = parent::_getForm();
        
        $form->addJs('fenix/Fenix_Profile.js');
        
        if($form->getField('tab_permissoes')){
            
            $form->deleteField('tab_permissoes');
            $form->addFieldAt( 1, FormField::factory('permissoes_separador', ModelConfig::SEPARATOR)->setTitle('Permissões do Perfil') );
            
            $grid = $form->getField('permissoes')->getGrid();
            
            $grid->deleteButtonPage('Novo Registro');
            $grid->setOption(Grid::OPTION_PAGINATION, false);
            $grid->setOption(Grid::OPTION_BUTTONS_HIDE, true);
            
            if($grid->getColumn('model')){
                $grid->deleteColumn('model');
            }
            
            $grid->setColumnAttribute('name', GridColumn::FORMATTER, 'Fenix_Profile.formatterName');
            
            $cols = array('none', 'view', 'edit', 'insert', 'delete');
            foreach($cols as $col){
                $grid->setColumnAttribute($col, GridColumn::WIDTH, '4em');
                $grid->setColumnAttribute($col, GridColumn::SORTABLE, false);
                $grid->setColumnAttribute($col, GridColumn::FORMATTER, 'Fenix_Profile.formatterCheckbox');
            }
        }
        
        
        return $form;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_save()
     */
    protected function _save($item){
        
        // Saves the main record
        $id = parent::_save($item);
        
        // Saves each permission set
        $itemsPermission = array();
        
        $levels = array('none', 'view', 'edit', 'insert', 'delete');
        foreach($levels as $level){
            if(isset($item[$level]) && is_array($item[$level])){
                foreach($item[$level] as $idPermission => $valuePermission){
                    if(!isset($itemsPermission[$idPermission])){
                        $itemsPermission[$idPermission] = array('value' => 0);
                    }
                    if(is_numeric($idPermission)){
                        $itemsPermission[$idPermission]['id_model'] = $idPermission;
                    } else {
                        $itemsPermission[$idPermission]['name'] = $idPermission;                        
                    }
                    $itemsPermission[$idPermission]['id_perfil'] = $id;
                    $itemsPermission[$idPermission][$level] = intval($valuePermission);
                    $itemsPermission[$idPermission]['value'] += intval($valuePermission);
                }
            }
        }
        
        $model = Model::factory( $this->_getModel()->getName(), Util::getConfig(Util::CONFIG_PROFILE)->section_permissions);
        
        $model->getTable()->delete('id_perfil = '. $id);
        
        foreach($itemsPermission as $itemPermission){
            $model->insert($itemPermission);
        }
        
        return $id;
    }
    
    /**
     * Cria a consulta do grid de permissões
     * @return Zend_Db_Select
     */
    protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
    
        $select = parent::_data($filters, $query, $sortCol, $sortDir, null, null);
        
        // Só quando está sendo feita a consulta para subseção de permissões
        if($this->_getModel()->getSection()->getName() == Util::getConfig(Util::CONFIG_PROFILE)->section_permissions){
            // Só obtém as permissões do perfil selecionado
            list($profileSchemaName, $profileTableName) = explode(".", Util::getModelProfile()->getTableName());
            $select->where('id_'.$profileTableName.' = ?', $_REQUEST['id_perfil']);
        }
        
        // Retorna o objeto da consulta
        return $select;
    }
    
    /**
     * Prepara de forma recursiva o menu do sistema para ser exibido na grid de permissões do perfil 
     * @param mixed[][] $menu
     * @param mixed[][] $data
     * @param number $level
     * @return mixed[][]
     */
    protected function _dataPreparePermissionsMenu($menu, $data, $level = 0){

        $ret = array();
        
        foreach($menu as $key => $record){
            
            // Remove os submenus do resultado final
            if(isset($record['_submenu'])){
                unset($record['_submenu']);
            }
            
            // Preenche as permissões para cada item de menu
            foreach($data as $permission){
                if((isset($record['id_model']) && $record['id_model'] && $permission['id_model'] == $record['id_model'])
                || ($record['name'] && $permission['name'] == $record['name'])){
                    foreach(array('none', 'view', 'edit', 'insert', 'delete', 'value') as $item){
                        $record[$item] = $permission[$item];
                    }
                }
            }
            
            // Define o nível do item de menu
            $record['_level'] = $level;
            
            // Insere o próprio item no array de retorno
            $ret[] = $record;
            
            // Para cada submenu executa a função de forma recursiva
            if(isset($menu[$key]['_submenu']) && is_array($menu[$key]['_submenu'])){
                $ret = array_merge($ret, $this->_dataPreparePermissionsMenu($menu[$key]['_submenu'], $data, $level+1));
            }
        }
        
        return $ret;
    }
    
    /**
     * Retorna o menu a ser definido nas permissões
     * @return mixed[][]
     */
    protected function _getMenu(){
        $ret = Util::getMenu();
        return $ret;
    }
    
    /**
     * Prepara os dados para serem exibidos na grid de permissões
     * @param mixed[][] $data
     * @return mixed[][]
     */
    protected function _dataPrepare($data){
        
        // Só quando está sendo feita a consulta para subseção de permissões
        if($this->_getModel()->getSection()->getName() == Util::getConfig(Util::CONFIG_PROFILE)->section_permissions){
            $data = $this->_dataPreparePermissionsMenu($this->_getMenu(), $data);
        }
        
        $ret = array();
        $ret['header'] = array('count' => count($data),
                               'requestId' => isset($_REQUEST['_requestId']) ? $_REQUEST['_requestId'] : '');
        
        // Define os dados
        $ret['data'] = $data;
        
        $json = json_encode(Util::utf8_encode($ret));
        
        // Se for uma versão de desenvolvimento mostra o JSON no browser de
        // forma mais fácil de ler
        if(Util::isDev()){
            $json = Util::json_indent( $json );
            header("Content-Type: text/plain");
        }
        
        die($json);
    }
}























