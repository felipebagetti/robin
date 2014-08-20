<?php

require_once dirname(__FILE__). '/ModelController.php';

abstract class Fenix_FileController extends Fenix_ModelController {

    protected function _model(){
        return Util::getConfig(Util::CONFIG_FILE)->model;
    }
    
    /**
     * @return Fenix_File
     */
    protected function _getModel(){
        return $this->_model;
    }

    /**
     * (non-PHPdoc)
     * @see ModelController::_getGrid()
     */
    protected function _getGrid(){

        $this->_setGridOption(Grid::OPTION_SORT_COL, 'id');
        $this->_setGridOption(Grid::OPTION_SORT_DIR, 'DESC');
        
        $grid = parent::_getGrid();
        
        $modelTypeFile = ModelConfig::getType(ModelConfig::FILE);
        
        $grid->addJs( $modelTypeFile->getJs() );
        
        $grid->addColumnAt(0, 'id', 'ID', null, 'right');
        
        $grid->setColumnAttribute('info', GridColumn::FORMATTER, $modelTypeFile->getFormatterGrid());
        $grid->setColumnAttribute('size', GridColumn::FORMATTER, "function(text){ return $.fn.upload.size(text); }");

        
        $grid->deleteButton('Visualizar');
        $grid->deleteButton('Alterar');
        
        $grid->deleteButtonPage('Novo Registro');
        
        $grid->addButtonPage('Limpar Registos Sem Referência', "Fenix.showLoading(); $.get('cleanup', function(){ Fenix.closeLoading(); Fenix_Model.grids.grid('load'); Fenix.alert('Concluido'); });", 'glyphicon glyphicon-trash', array('className'=>'btn-primary'));

        return $grid;
    }
    
    public function cleanupAction(){
        
        Model::beginTransaction();
        
        $this->_getModel()->cleanup();
        
        Model::commitTransaction();
        
        die("1");
    }
    
    protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
        
        $select = parent::_data($filters, $query, $sortCol, $sortDir, $limit, $offset);
        
        return $select;
    }
    
    protected function _dataPrepare($data){
        
        // Calculo do tamanho dos lob no sqlite
        if(Util::getConfig(Util::CONFIG_DATABASE)->dbtype == 'PDO_SQLITE'){
            foreach($data as $key => $record){
                $filename = dirname(Util::getConfig(Util::CONFIG_DATABASE)->dbname) . "/files/" . $record['oid'];
                $data[$key]['lob_size'] = filesize($filename);
            }
        }
        
        return $data;
    }
}












