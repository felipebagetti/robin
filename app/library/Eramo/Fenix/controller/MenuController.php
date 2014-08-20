<?php

abstract class Fenix_MenuController extends Fenix_ModelController {

    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_model()
     */
    protected function _model(){
        return Util::getConfig(Util::CONFIG_MENU)->model;
    }
    
    /**
     * @return Fenix_Menu
     */
    protected function _getModel(){
        return $this->_model;
    }

    /**
     * (non-PHPdoc)
     * @see ModelController::_getGrid()
     */
    protected function _getGrid(){

        $grid = parent::_getGrid();
        
        $grid->addJs("fenix/Fenix_Menu.js");
        
        $grid->addColumnAt(0, 'id', 'ID', null, 'right');
        $grid->setColumnAttribute('name', GridColumn::FORMATTER, 'Fenix_Menu.formatterName');
        
        foreach($grid->getColumns() as $column){
            $grid->setColumnAttribute($column->getName(), GridColumn::SORTABLE, false);
        }
        
        $grid->deleteButton('Visualizar');
        
        return $grid;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_data()
     */
        protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
        
        $select = parent::_data($filters, $query, $sortCol, $sortDir, $limit, $offset);
        
        return $select;
    }
    
    /**
     * Prepara os itens de menu para exibição na tela
     * @param mixed[][] $data
     * @param number $level
     * @return mixed Dados preparados
     */
    protected function _dataPrepareSubmenu($data, $level = 0){
        $ret = array();
        
        foreach($data as $key => $record){
            
            // Remove os submenus do resultado final
            if(isset($record['_submenu'])){
                unset($record['_submenu']);
            }
            
            // Define o nível do item de menu
            $record['_level'] = $level;
            
            // Insere o próprio item no array de retorno
            $ret[] = $record;
            
            // Para cada submenu executa a função de forma recursiva
            if(isset($data[$key]['_submenu']) && is_array($data[$key]['_submenu'])){
                $ret = array_merge( $ret, $this->_dataPrepareSubmenu($data[$key]['_submenu'], $level+1) );
            }
            
        }
        
        return $ret;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_dataPrepare()
     */
    protected function _dataPrepare($data){
        
        // Ignora os dados padrão da consulta e usa o método de
        // criação dos itens de menu para abastecer a grid
        $ret = $this->_dataPrepareSubmenu( Util::getMenu(true) );
        
        return $ret;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_getForm()
     */
    protected function _getForm(){
        
        $form = parent::_getForm();
        
        // Define os itens da chave FK parent para serem os próprios itens do menu
        $parentData = $this->_dataPrepare( null );
        $recordOpen = $form->getOption(Form::OPTION_RECORD);
        foreach ($parentData as $key => $record){
            if($recordOpen != null && $record['id'] == $recordOpen['id']){
                unset($parentData[$key]);
                continue;
            }
            if($record['_level'] > 0){
                $parentData[$key]['name'] = str_pad("", $record['_level']*18, "&nbsp;")."&rarr; ".$record['name'];
            }
        }
        $parentData = array_merge(array(array('id' => '', 'text' => '&nbsp;' )), $parentData);
        $form->setFieldAttribute('parent', FormField::DATA, array_values($parentData));
        
        return $form;
    }
}






















