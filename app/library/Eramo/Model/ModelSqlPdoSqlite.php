<?php

class ModelSqlPdoSqlite extends ModelSql {
    
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
            
            $config = $db->getConfig();
            
            $sql = "PRAGMA database_list;";
            $dados = $db->query($sql)->fetchAll();

            $schemaExists = false;

            foreach($dados as $database){
                if($database['name'] == $schemaName){
                    $schemaExists = true;
                }
            }

            // ATTACH new Database
            if($schemaExists == false){
                $filename = str_replace(".db", "_".$schemaName.".db", $config['dbname']);
                
                // Caminho absoluto se já não for um
                if(stripos($filename, "/") !== 0){
                    $filename = dirname($_SERVER['SCRIPT_FILENAME'])."/".$filename;
                }
                
                $sql = "ATTACH DATABASE '{$filename}' AS {$schemaName};";
                $db->query($sql);
            }
    
        }
    
        return $ret;
    }
    
    public function pk(ModelSection $section, ModelField $field){
        return "";
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
    
        list($tableSchema, $tableName) = explode(".", $tableName);
        
        $ret[] = "CREATE INDEX {$tableSchema}.{$name} on {$tableName} ({$fieldName});";
    
        return $ret;
    }
    
    protected function _generateTableField(ModelSection $section, ModelField $field, $update = false){
        
        $ret = parent::_generateTableField($section, $field);
        
        if(ModelConfig::checkType($field, ModelConfig::FK)){
            
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
            
            list($referenceTableSchema, $referenceTableName) = explode(".", $referenceTableName);
            
            $ret[0] .= " REFERENCES ".$referenceTableName." (".$referenceKey.")";
            $ret[0] .= " ON DELETE CASCADE ";
            
            $ret = array($ret[0]);
        }
        
        return $ret;
    }

    public function fk(ModelSection $section, ModelField $field){
        return false;
    }
    
    protected function _generatePrepareModel(Model $model){
        list($schema, $name) = explode(".", $model->getName());
        
        $item = array();
        $item['name'] = $name;
        $item['schema'] = $schema;
        $item['xml'] = $model->getXml();
        
        $item['xml'] = preg_replace("@([\n\r]*)@", "", $item['xml']);
        $item['xml'] = preg_replace("@([\s]{2,})@", "", $item['xml']);
        
        // Escapa o conteúdo do XML pelo caractere '
        $item['xml'] = str_replace("'", "''", $item['xml']);
        
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
        list($schema, $table) = explode(".", $section->getTableName());
        return  "CREATE UNIQUE INDEX {$schema}.".$this->_generateTableUniqueName($section, $fields)." ON {$table} (".$this->_generateTableUniqueFieldList($section, $fields).");";
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
    
    /**
     * Generate a table field update
     *
     * There is no DROP COLUMN in SQLITE
     *
     * @param ModelSection $section
     * @param ModelField $field
     *
     * @return String
     */
    protected function _generateTableDropField(ModelSection $section, ModelField $field){
        Util::log("Não é possível remover colunas de uma tabela SQLITE. A coluna não foi removida da tabela, somente do modelo XML.", Util::FENIX_LOG_WARNING);
        return array();
    }
    
    /**
     * Generate a table field update
     * 
     * There is no ALTER COLUMN in SQLITE
     * 
     * @param ModelSection $section
     * @param ModelField $field
     * 
     * @return String
     */
    protected function _generateTableUpdateField(ModelSection $section, ModelField $field){
        Util::log("Não é possível alterar colunas de uma tabela SQLITE. A coluna não foi alterada na tabela, somente do modelo XML.", Util::FENIX_LOG_WARNING);
        return array();
    }
}












