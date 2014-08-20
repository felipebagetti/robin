<?php

abstract class ModelSql {

    /** Extra definitions constants */
    const PK = 'pk';
    const FK = 'fk';
    
    /**
     * The factory method defines which SQL Generator class is to be used
     * according with the type of database configured in the system
     *
     * @return ModelSql
     */
    public static function factory(){
        
        $db = Table::getDefaultAdapter();
        
        $config = Util::getConfig(Util::CONFIG_DATABASE);
        
        $dbType = str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $config->dbtype))));
        
        $dbClass = "ModelSql{$dbType}";
        
        require_once dirname(__FILE__) . "/{$dbClass}.php";
        
        return new $dbClass();
    }

    /**
     * Generate a INSERT or UPDATE sql for a Model
     *
     * @param Model $model
     * @param Model $currentModel
     *
     * @return String[] One or many sql commands
     */
    public function generate(Model $model, Model $currentModel = null){

        $ret = array();

        // It`s a new model creation
        if($currentModel == null){
            
            // Verifies if it is a new schema and creates it
            $sql = $this->_generateSchema($model);
            
            if($sql !== false){
                $ret[] = $sql;
            }
            
            // Creates the table SQL

            $sections = $model->getSections();

            foreach ($sections as $section){
                
                // Generates the table definition
                $ret[] = $this->_generateTable($section, $model->getFields($section->getName()));
                
                // Generates a Index for each FK field
                foreach($model->getFields($section->getName()) as $field){
                    if(ModelConfig::checkType($field, ModelConfig::FK)){
                        $ret = array_merge($ret, $this->_generateTableFieldIndex($section, $field));
                    }
                }
                
                // Unique constraint auto generation
                if($section->getUnique()){
                    $ret[] = $this->_generateTableUnique($section, $model->getFields($section->getName()));
                }
            }
            
            $ret[] = $this->_generateInsert($model);
            
        }
        // It`s a model update
        else {
            
            $sections = $model->getSections();
            $sectionsCurrent = $currentModel->getSections();
            
            foreach ($sections as $section){
                
                $exists = false;
                
                foreach($sectionsCurrent as $key => $sectionCurrent){
                    
                    // If the section currently exists in the model creates a table update
                    if($section->getName() == $sectionCurrent->getName()){
                        $fields = $model->getFields($section->getName());
                        $fieldsCurrent = $currentModel->getFields($sectionCurrent->getName());
                        
                        $ret = array_merge($ret, $this->_generateTableUpdate($section, $fields, $sectionCurrent, $fieldsCurrent));
                        $exists = true;
                        unset($sectionsCurrent[$key]);
                    }
                    
                }
                
                // If it's a new section creates the table
                if($exists == false){
                    $ret[] = $this->_generateTable($section, $model->getFields($section->getName()));
                }
                
            }
            
            // If still there is sections on $sectionsCurrent it means that section was removed
            foreach($sectionsCurrent as $sectionRemoved){
                $ret = array_merge($ret, array($this->_generateDeleteTable($sectionRemoved)));
            }
            
            // Generates the model update sql
            $ret[] = $this->_generateUpdate($model);
            
        }
        
        return $ret;
    }
    
    /**
     * Generates, if needed, the schema creation
     * 
     * @param Model $model
     * 
     * @return mixed (boolean or String)
     */
    protected abstract function _generateSchema(Model $model);
    
    /**
     * Generates the model update SQL
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected abstract function _generateUpdate(Model $model);
    
    /**
     * Generates the unique field Name
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected function _generateTableUniqueName(ModelSection $section, array $fields){
        
        $ret = array( $this->_generateTableNameInline($section) );
        $ret[] = "unique";
        
        foreach(explode(",", $section->getUnique()) as $field){
            $ret[] = trim($field);
        }
        
        return implode("_", $ret);
    }
    
    /**
     * Generates the unique field list
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected function _generateTableUniqueFieldList(ModelSection $section, array $fields){
        $ret = array();
        
        foreach(explode(",", $section->getUnique()) as $field){
            $found = false;
            foreach($fields as $fieldObj){
                if($fieldObj->getName() == $field){
                    $ret[] = $fieldObj->getNameDatabase();
                    $found = true;
                }
            }
            if($found === false){
                throw new Fenix_Exception("Unable to find field <strong>{$field}</strong> in model's section <strong>{$section->getName()}</strong>.");
            }
        }
        
        return implode(", ", $ret);
    }
    
    /**
     * Generates the unique field SQL
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected abstract function _generateTableUnique(ModelSection $section, array $fields);
    
    /**
     * Generates the unique field DROP SQL
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected abstract function _generateTableUniqueDrop(ModelSection $section, array $fields);
    
    /**
     * Deletes as model
     * 
     * @param Model $model
     * 
     * @return String[]
     */
    public function delete(Model $model){
        $ret = array();
        
        $ret[] = $this->_generateDelete($model);
        
        foreach (array_reverse($model->getSections()) as $section){
            $ret[] = $this->_generateDeleteTable($section);
        }
        
        return $ret;
    }
    
    /**
     * Generetes a table deletion script 
     * 
     * @param ModelSection $section
     */
    protected function _generateDeleteTable(ModelSection $section){
        
        $ret = "DROP TABLE " . $section->getTableName() . ";";

        return $ret;
    }
    
    /**
     * Generates a model deletion script
     * 
     * @param Model $section
     */
    protected function _generateDelete(Model $model){
        
        list($schema, $name) = explode(".", $model->getName());
        
        $ret = "DELETE FROM ".Model::getModelTableName()." WHERE name = '".$name."' AND schema = '".$schema."';";

        return $ret;
    }
    
    /**
     * Generate a table`s SQL command
     *
     * @param ModelSection $section
     * @param ModelField[] $fields
     */
    protected function _generateTable(ModelSection $section, $fields = array()){

        $header = $this->_generateTableHeader($section);

        $body = array();
        $bodyEnd = array();

        // Fixed primary key field
        list($definition, $extraDefinition) = $this->_generateTableField($section, ModelField::factory('id', 'pk')); 
        $body[] = $definition;
        if($extraDefinition != null){
            $bodyEnd[] = $extraDefinition;
        }
        
        // When subsection add a new field to reference the main section
        if($section->getSection() !== null){
            
            $def = $this->_generateTableField( $section, $section->getParentKey() );
            
            if(count($def) == 2){
                list($definition, $extraDefinition) = $def;
            } else {
                $definition = current($def);
            }
            
            $body[] = $definition;
            if($extraDefinition != null){
                $bodyEnd[] = $extraDefinition;
            }
        }

        foreach($fields as $field){
            $fieldSql = $this->_generateTableField($section, $field);
            
            if(isset($fieldSql[0])){
                $body[] = $fieldSql[0];
            }
            
            if(isset($fieldSql[1])){
                $bodyEnd[] = $fieldSql[1];
            }
        }

        $footer = $this->_generateTableFooter($section);
        
        $body = array_merge($body, $bodyEnd);

        $sql = $header . " ( " . implode(", ", $body) . " ) " . $footer . ";";

        return $sql;
    }
    
    /**
     * Generate a table`s SQL Update command
     *
     * @param ModelSection $section
     * @param ModelField[] $fields
     */
    protected function _generateTableUpdate(ModelSection $section, array $fields = array(), ModelSection $sectionCurrent, array $fieldsCurrent = array()){

        $ret = array();
        $retExtra = array();
        
        $header = $this->_generateTableUpdateHeader($section);

        $body = array();

        foreach($fields as $field){
            
            $exists = false;
            
            foreach($fieldsCurrent as $key => $fieldCurrent){
                
                if($fieldCurrent->getName() == $field->getName()){
                    
                    $fieldTypeDefinition = ModelConfig::getType($field->getType());
                    $fieldCurrentTypeDefinition = ModelConfig::getType($fieldCurrent->getType());
                    
                    if($fieldTypeDefinition->getType() !== $fieldCurrentTypeDefinition->getType()){
                        $msg = "Não é possível alterar o tipo de um campo quando a definição no banco de dados muda.";
                        $msg.= "<br>O campo <strong>".$field->getName()."</strong> antes era <strong>".$fieldCurrentTypeDefinition->getType()."</strong> e agora é <strong>".$fieldTypeDefinition->getType()."</strong>.";
                        throw new Fenix_Exception($msg);
                    }
                    
                    // Se houve mudança no atributo size do campo (somente há suporte ao acréscimo de tamanho em campos texto)
                    if(ModelConfig::checkType($field, ModelConfig::TEXT)){
                        if($fieldCurrent->getSize() < $field->getSize()){
                            $body = array_merge($body, $this->_generateTableUpdateField($section, $field));
                        } else if($fieldCurrent->getSize() > $field->getSize()) {
                            throw new Fenix_Exception("Não é possível diminuir o tamanho de um campo. O campo '".$field->getName()."' passou de '".$fieldCurrent->getSize()."' para '".$field->getSize()."'.");
                        }
                    }
                    
                    unset($fieldsCurrent[$key]);
                    $exists = true;
                }
                
            }
            
            if($exists == false){
                $body = array_merge($body, $this->_generateTableAddField($section, $field));
                
                if(ModelConfig::checkType($field, ModelConfig::FK)){
                    $retExtra[] = $this->_generateTableFieldIndex($section ,$field);
                }
            }
            
        }
        
        // Deleta os campos que estão no atual e não foram localizados no novo
        foreach($fieldsCurrent as $key => $fieldCurrent){
            $type = ModelConfig::getType($fieldCurrent->getType());
            if($type->getType() !== ModelConfig::TYPE_VIEW){
                $body = array_merge($body, $this->_generateTableDropField($section, $fieldCurrent));
            }
        }
        
        foreach($body as $fieldBody){
            $ret[] = $header . $fieldBody . ";";
        }
        
        // Unique constraint auto generation and drop
        
        // Conditions to DROP:
        //   1. Exists NOW and will not exist anymore
        //   2. Exists NOW and WILL exist differently
        if($sectionCurrent->getUnique() && !$section->getUnique() 
        || ($sectionCurrent->getUnique() && $section->getUnique() != $sectionCurrent->getUnique()) ){
            $ret[] = $this->_generateTableUniqueDrop($sectionCurrent, $fieldsCurrent); 
        }
        
        // Conditions to CREATE
        //   1. WILL exist and is different from NOW (or not exists before)
        if($section->getUnique() && $section->getUnique() != $sectionCurrent->getUnique()){
            $ret[] = $this->_generateTableUnique($section, $fields);
        }
        
        return array_merge($ret, $retExtra);
    }

    /**
     * Generates a table name to be used in database objects names (without dots
     * and with full schema info)
     *
     * @param ModelSection $section
     *
     * @return String
     */
    protected function _generateTableNameInline(ModelSection $section){
        $ret = $section->getTableName();
        $ret = preg_replace("|([^A-Za-z0-9]+)|", "_", $ret);
        return $ret;
    }

    /**
     * Generates table header SQL
     *
     * @param ModelSection $section
     *
     * @return String
     */
    protected function _generateTableHeader(ModelSection $section){

        $ret = "CREATE TABLE " . $section->getTableName($section);

        return $ret;
    }

    /**
     * Generates table update header SQL
     *
     * @param ModelSection $section
     *
     * @return String
     */
    protected function _generateTableUpdateHeader(ModelSection $section){

        $ret = "ALTER TABLE " . $section->getTableName($section);

        return $ret;
    }
    
    /**
     * Escapes a field name
     * @param String $name
     * @return String
     */
    protected function _generateTableFieldNameEscape($name){
        return "\"".$name."\"";
    }

    /**
     * Generates table field SQL
     *
     * @param ModelSection $section
     * @param ModelField $field
     *
     * @return String
     */
    protected function _generateTableField(ModelSection $section, ModelField $field, $update = false){

        $ret = array();
        
        $name = ModelConfig::name($field);

        $type = ModelConfig::getType( $field->getType() );

        $typeDefinition = $type->getType();
        
        if($typeDefinition == ModelConfig::TYPE_VIEW){
            return $ret;
        }

        $size = $field->getSize();
        
        if($type->isSizeRequired() && !$size){
            throw new Exception(__CLASS__.": The field '" . $field->getName() . "' must have a 'size' attribute.");
        }
        
        if($type->getSize()){
            $size = $type->getSize();
        }
        
        if($size){
            $typeDefinition .= " (" . $size . ")";
        }

        $ret[] = self::_generateTableFieldNameEscape($name) . ($update ? " TYPE " : "") . " " . $typeDefinition;

        $extraDefinition = $type->getExtraDefinition();

        if($extraDefinition){
            $ret[] = $this->$extraDefinition($section, $field);
        }
        
        return $ret;
    }
    
    /**
     * Generate a table field update
     * 
     * @param ModelSection $section
     * @param ModelField $field
     * 
     * @return String
     */
    protected function _generateTableUpdateField(ModelSection $section, ModelField $field){
        $tableField = $this->_generateTableField($section, $field, true);
        if(is_array($tableField)){
            $tableField = current($tableField);
        }
        return array(" ALTER COLUMN " . $tableField);
    }
    
    /**
     * Generate a table field update
     * 
     * @param ModelSection $section
     * @param ModelField $field
     * 
     * @return String
     */
    protected function _generateTableAddField(ModelSection $section, ModelField $field){
        
        $ret = array();
        
        $def = $this->_generateTableField($section, $field);
        
        foreach($def as $key => $d){
            if($key == 0){
                $ret[] = " ADD COLUMN " . $d;
            } else {
                $ret[] = " ADD " . $d;
            }
        }
        
        return $ret;
    }
    
    /**
     * Generate a table field update
     * 
     * @param ModelSection $section
     * @param ModelField $field
     * 
     * @return String
     */
    protected function _generateTableDropField(ModelSection $section, ModelField $field){
        return array(" DROP COLUMN " . ModelConfig::name($field));
    }

    /**
     * Generates table footer SQL
     * @param ModelSection $section
     *
     * @return String
     */
    protected function _generateTableFooter(ModelSection $section){
        return "";
    }

}

