<?php

class ModelSqlPdoPgsql extends ModelSql {
    
    /**
     * Generates, if needed, the schema creation
     *
     * @param Model $model
     *
     * @return mixed (boolean or String)
     */
    protected function _generateSchema(Model $model){
    
        $ret = false;
    
        $db = Table::getDefaultAdapter();
    
        if($db != null){
    
            $schemaName = current(explode(".", $model->getName()));

            $sql = "SELECT schema_name FROM information_schema.schemata WHERE schema_name = '".$schemaName."'";

            if($db->query($sql)->rowCount() == 0){
                $ret = "CREATE SCHEMA " . $schemaName .";";
            }
        }
    
        return $ret;
    }
    
    public function pk(ModelSection $section, ModelField $field){

        $tableName = $this->_generateTableNameInline($section);
        $fieldName = ModelConfig::name($field);

        $ret = "CONSTRAINT ".$tableName."_pk PRIMARY KEY (".$fieldName.")";

        return $ret;
    }
    
    /**
     * Generate a field index
     *
     * @param ModelSection $section
     * @param ModelField $field
     *
     * @return String
     */
    protected function _generateTableFieldIndex(ModelSection $section, ModelField $field){
    
        $ret = array();
    
        $tableName = $section->getTableName();
        $fieldName = $field->getNameDatabase();
    
        $name = $this->_generateTableNameInline($section).'_'.$field->getNameDatabase();
    
        $ret[] = "CREATE INDEX {$name} on {$tableName} ({$fieldName});";
    
        return $ret;
    }

    public function fk(ModelSection $section, ModelField $field){

        $tableName = $this->_generateTableNameInline($section);
        $fieldName = ModelConfig::name($field);

        if($field->getTable() == null || $field->getKey() == null || $field->getField() == null){
            throw new Exception(__CLASS__.": A column defined as a FK must have the following attributes: 'table', 'key', 'field' in order to reference another field correctly.");
        }

        $schema = null;
        $table = $field->getTable();
        
        if(stripos($table, ".") !== false){
            list($schema, $table) = explode('.', $table);
        }
        
        $referenceSection = ModelSection::factory($table);
        
        if($schema == null){
            $schema = $section->getSchema();
        }

        if($schema == null && $section->getSection() != null){
            $schema = $section->getSection()->getSchema();
        }

        if($schema == null){
            throw new Exception(__CLASS__.": Unable to determine a FK referenced schema.");
        }

        $referenceSection->setSchema( $schema );

        $referenceTableName = $referenceSection->getTableName();
        $referenceKey = $field->getKey();

        $ret = "CONSTRAINT ".$tableName."_fk_".$fieldName." FOREIGN KEY (".$fieldName.")";
        $ret.= " REFERENCES ".$referenceTableName." (".$referenceKey.") ON DELETE " . ($field->getFkOnDelete() ? $field->getFkOnDelete() : 'RESTRICT');
        
        return $ret;
    }
    
    protected function _generatePrepareModel(Model $model){
        list($schema, $name) = explode(".", $model->getName());
        
        $item = array();
        $item['name'] = $name;
        $item['schema'] = $schema;
        $item['xml'] = 'xxx';
        $item['xml'] = $model->getXml();
        
        $item['xml'] = preg_replace("@([\n\r]*)@", "", $item['xml']);
        $item['xml'] = preg_replace("@([\s]{2,})@", "", $item['xml']);
        
        $item['xml'] = preg_replace("@([']{1})@", "''", $item['xml']);
        
        return $item;
    }

    public function _generateInsert(Model $model){
        
        $item = $this->_generatePrepareModel($model);
        
        $sql = "INSERT INTO ".Model::getModelTableName()." (".implode(", ", array_keys($item)).") VALUES ('".implode("', '", array_values($item))."');"; 
        
        return $sql;
    }

    public function _generateUpdate(Model $model){
        
        $item = $this->_generatePrepareModel($model);
        
        $set = array();
        
        foreach($item as $field => $value){
            if($field == 'name' || $field == 'schema'){
                continue;
            }
            $set[] = $field . " = '" . $value . "'"; 
        }
        
        $sql = "UPDATE ".Model::getModelTableName()." SET ".implode(", ", $set)." WHERE name = '".$item['name']."' AND schema = '".$item['schema']."';"; 
        
        return $sql;
    }
    
    /**
     * Generates the unique field SQL
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected function _generateTableUnique(ModelSection $section, array $fields){
        return  "CREATE UNIQUE INDEX ".$this->_generateTableUniqueName($section, $fields)." ON ".$section->getTableName()." (".$this->_generateTableUniqueFieldList($section, $fields).");";
    }
    
    /**
     * Generates the unique field DROP SQL
     * 
     * @param Model $model
     * 
     * @return String
     */
    protected function _generateTableUniqueDrop(ModelSection $section, array $fields){
        $schema = explode(".", $section->getTableName());
        $schema = current($schema);
        return  "DROP INDEX {$schema}.".$this->_generateTableUniqueName($section, $fields).";";
    }
}












