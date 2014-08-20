<?php

/**
 * Classe de edição de modelos
 * 
 */
class EditorController extends Eramo_Controller_Action {
    
    protected function _listTypes(){
        
        $ret = array();
        
        foreach(ModelConfig::getTypes() as $key => $type){
            if($type->getName() == 'pk' || stripos($type->getName(), "_") === 0){
                continue;
            }
            $ret[$key] = $type->getAttributes();
        }
        
        ksort($ret);
         
        return json_encode($ret);
    }
    
    protected function _listModels(){
        $ret = array();
        
        $modules = Eramo_Controller_Front::getInstance()->getModules();
        
        foreach($modules as $module){
            
            $dirname ="app/modules/{$module}/model/xml/";
            
            if($module == "fenix"){
                continue;
            }
            
            if(is_dir($dirname)){
                
                $dir = opendir($dirname);
                while($file = readdir($dir)){
                    if(preg_match("@(.*)xml$@", $file)){
                        $ret[$dirname.$file] = $dirname.$file;
                    }
                }
                
            }
            
        }
        
        ksort($ret);
        
        return $ret;
    }
    
    public function indexAction(){
        
        $models = $this->_listModels();
        
        if(!isset($_REQUEST['model']) || !$_REQUEST['model']){
            header("Location: ?model=" . current($models));
            die();
        }
        
        if(!isset($models[$_REQUEST['model']])){
            $models[] = $_REQUEST['model'];
        }
        
        $options = array();
        $options[Grid::OPTION_TITLE] = 'Editor';
        $options[Grid::OPTION_DATA_URL] = 'data?model=' . $_REQUEST['model'];
        $options[Grid::OPTION_LOAD_CALLBACK] = 'Fenix_Editor.loadCallback';
        $options[Grid::OPTION_PAGINATION] = false;
        
        $grid = new Grid($options);
        
        $grid->addJs('fenix/Fenix_Model.js');
        $grid->addJs('fenix/Fenix_Editor.js');
        
        $grid->addOnload('Fenix_Editor.types = ' . $this->_listTypes());
        $grid->addOnload('Fenix_Editor.models = ' . json_encode(array_values($models)));
        $grid->addOnload('Fenix_Editor.model = "' . $_REQUEST['model'].'"');
        
        // Criação de um novo model
        if(!is_file($_REQUEST['model'])){
            $grid->addOnload("Fenix_Editor.modelCreate = true;");
        }
        
        $grid->addColumn( 'name', 'Name', 'Fenix_Editor.formatterName' );
        $grid->addColumn( 'title', 'Title', 'Fenix_Editor.formatterTitle' );
        $grid->addColumn( 'type', 'Type', 'Fenix_Editor.formatterType', null, null, '7em' );
        $grid->addColumn( 'table', 'Table', 'Fenix_Editor.formatterText', null, null, '7em' );
        $grid->addColumn( 'key', 'Key', 'Fenix_Editor.formatterText', null, null, '2em' );
        $grid->addColumn( 'field', 'Field', 'Fenix_Editor.formatterText', null, null, '7em' );
        $grid->addColumn( 'depends', 'Depends', 'Fenix_Editor.formatterText', null, null, '4em' );
        $grid->addColumn( 'dependsKey', 'Depends<br>Key', 'Fenix_Editor.formatterText', null, null, '4em' );
        $grid->addColumn( 'data', 'Data', 'Fenix_Editor.formatterText', null, null, '4em' );
        $grid->addColumn( 'default', 'Default', 'Fenix_Editor.formatterText', null, null, '3em' );
        $grid->addColumn( 'size', 'Size', 'Fenix_Editor.formatterText', null, null, '2em' );
        $grid->addColumn( 'description', 'Description', 'Fenix_Editor.formatterText', null, null, '3em' );
        $grid->addColumn( 'required', '<span style="cursor: help;" title="Faz com que o formulário requeira o preenchimento do campo para submissão dos dados."><i class="glyphicon glyphicon-ok"></i></span>', 'Fenix_Editor.formatterCheck', 'center', null, '1em' );
        $grid->addColumn( 'searchable', '<span style="cursor: help;" title="Permite que a coluna seja pesquisável pela grid."><i class="glyphicon glyphicon-search"></i></span>', 'Fenix_Editor.formatterCheck', 'center', null, '1em' );
        $grid->addColumn( 'filter', '<span style="cursor: help;" title="Permite que a coluna seja filtrada pelo componente padrão do grid."><i class="glyphicon glyphicon-filter"></i></span>', 'Fenix_Editor.formatterCheck', 'center', null, '1em' );
        $grid->addColumn( 'insert', '<span style="cursor: help;" title="Permite que o valor da FK seja cadastrado de maneira smiples"><i class="glyphicon glyphicon-plus"></i></span>', 'Fenix_Editor.formatterCheck', 'center', null, '1em' );
        $grid->addColumn( 'remote', '<span style="cursor: help;" title="Faz o carregamento dos dados do combo via ajax."><i class="glyphicon glyphicon-refresh"></i></span>', 'Fenix_Editor.formatterCheck', 'center', null, '1em' );
        $grid->addColumn( 'width', '<span style="cursor: help;" title="Determina a largura do campo.">Width</span>', 'Fenix_Editor.formatterText', null, null, '3em' );
        $grid->addColumn( 'row', 'Row', 'Fenix_Editor.formatterText', null, null, '1em' );
        $grid->addColumn( 'form', "Form<br>E V N", 'Fenix_Editor.formatterRadio', null, null, '4em' );
        $grid->addColumn( 'grid', 'Grid<br>E V N', 'Fenix_Editor.formatterRadio', null, null, '4em' );
        
        foreach($grid->getColumns() as $column){
            $column->setSortable(false);
        }
        
        $grid->addButtonPage( 'Nova Seção', "Fenix_Editor.newSection()", 'glyphicon glyphicon-plus-sign icon-white', array('className' => 'btn-xs btn-primary') );
        $grid->addButtonPage( 'Salvar', "Fenix_Editor.save()", 'glyphicon glyphicon-file icon-white', array('className' => 'btn-xs btn-primary') );
        $grid->addButtonPage( 'Carregar', "Fenix_Editor.load()", 'glyphicon glyphicon-refresh icon-white', array('className' => 'btn-xs btn-primary') );
        $grid->addButtonPage( 'Visualizar', "Fenix_Editor.view()", '', array('className' => 'btn-xs btn-primary') );
        $grid->addButtonPage( 'Excluir', "Fenix_Editor.delete()", 'glyphicon glyphicon-trash icon-white', array('className' => 'btn-xs btn-danger') );
        
        $grid->render();
    }
    
    protected function _data($filename, $newSection = 0, $newFieldSection = false, $newField = 0){
        
        $model = null;
        
        try {
            $model = Model::factoryXml($filename);
        } catch(Exception $e){}
        
        $data = array();
        
        if($model != null){
            foreach($model->getSections() as $section){
                
                $item = array('__type' => 'section');
                $item = array_merge($item, $section->getAttributes());
                $data[] = Util::utf8_encode($item);
                
                foreach($model->getFields($section) as $field){
                    $item = array('__type' => 'field');
                    $item = array_merge($item, $field->getAttributes());
                    $data[] = Util::utf8_encode($item);
                }
                
                if($newFieldSection == $section->getName()){
                    for($i = 0; $i < $newField; $i++){
                        $data[] = array('__type' => 'field');
                    }
                }
            }
            
        } else {
            $newSection = 1;
            $newField = 1;
        }
        
        if($newSection == 1){
            $newField = 4;
        }
        
        for($i = 0; $i < $newSection; $i++){
            $data[] = array('__type' => 'section');
        }
        
        if(!$newFieldSection){
            for($i = 0; $i < $newField; $i++){
                $data[] = array('__type' => 'field');
            }
        }
        
        return array_values($data);
    }
    
    public function dataAction(){
        
        $newSection = isset($_REQUEST['newSection']) ? $_REQUEST['newSection'] : 0;
        $newField = isset($_REQUEST['newField']) ? $_REQUEST['newField'] : 0;
        $section = isset($_REQUEST['section']) ? $_REQUEST['section'] : false;
        
        $ret = array();
        $ret['data'] = $this->_data($_REQUEST['model'], $newSection, $section, $newField);
        
        die(json_encode($ret));
    }
    
    public function saveAction(){
        
        $new = array();
        
        for($i = 0; $i < count($_POST['name']); $i++){
            foreach($_POST as $attr => $values){
                if(isset($values[$i]) && strlen($values[$i]) > 0){
                    $new[$i][$attr] = $values[$i];
                }
            }
        }
        
        $data = $this->_data($_POST['__model']);
        
        $section = null;

        foreach($data as $record){
            if($record['__type'] == 'section' && isset($record['name'])){
                $section = $record['name'];
            }
            $sectionNew = null;
            foreach($new as $key => $recordNew){
                
                if(!isset($recordNew['name']) && !isset($recordNew['title'])){
                    continue;
                }
                
                if($recordNew['__type'] == 'section'){
                    $section = $recordNew['name'];
                }
                if($section == $sectionNew && $record['name'] == $recordNew['name']){
                    foreach($record as $key => $value){
                        if(!isset($recordNew[$key])){
                            $new[$key] = $value;
                        }
                    }
                }
            }
        }
        
        $doc = new DOMDocument("1.0", "UTF-8");
        
        $currentElement = $doc;
        
        $primeiraSecao = null;
        
        foreach($new as $record){
            
            if(!isset($record['name']) && !isset($record['title'])){
                continue;
            }
            
            $element = $doc->createElement($record['__type']);
            
            foreach($record as $name => $value){
                if(stripos($name, "__") === 0){
                    continue;
                }
                $element->setAttribute($name, $value);
            }
            
            if($record['__type'] == 'section' && $primeiraSecao != null){
                $primeiraSecao->appendChild($element);
            } else {
                $currentElement->appendChild($element);
            }
            
            if(!$primeiraSecao){
                $primeiraSecao = $element;
            }
            
            if($record['__type'] == 'section'){
                $currentElement = $element;
            }
        }
        
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        
        $model = Model::factoryXmlString( $doc->saveXML() );
        
        $sql = ModelSql::factory()->generate($model);
        
        $fileExists = is_file($_REQUEST['__model']) ? true : false;
        
        $doc->save($_REQUEST['__model']);
        
        if(!$fileExists){
            @chmod($_REQUEST['__model'], 0777);
        }
        
        die('1');
    }
    
    public function loadAction(){
        
        $model = Model::factoryXml($_REQUEST['model']);
        
        // Gera o SQL uma vez fora da transação para que a conexão ao banco seja establecida
        ModelSql::factory()->generate($model, null);
        
        Table::getDefaultAdapter()->beginTransaction();
        
        ModelConfig::modelLoad($model);
        
        Table::getDefaultAdapter()->commit();
        
        die("1");
    }
    
    public function deleteAction(){
        
        $model = Model::factoryXml($_REQUEST['model']);
        
        Table::getDefaultAdapter()->beginTransaction();
        
        ModelConfig::modelDelete($model);
        
        Table::getDefaultAdapter()->commit();
        
        die("1");
    }
}












