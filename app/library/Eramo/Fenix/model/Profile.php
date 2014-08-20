<?php 

class Fenix_Profile extends Model {
    
    const PERMISSION_NONE = 0; 
    const PERMISSION_VIEW = 2;
    const PERMISSION_EDIT = 4;
    const PERMISSION_INSERT = 8;
    const PERMISSION_DELETE = 16;
    
    /**
     * @return Fenix_Profile
     */
    public static function factory($name = "profile", $section = null){
        return parent::factory($name, $section);
    }
    

    /**
     * Valida se o perfil logado no sistema tem a permiss�o de acesso definida
     * 
     * @param Model|String $item Model a ser verificado ou String com nome da permiss�o personalizada/item de menu
     * @param int $level
     */
    public static function checkPermission($item, $permission = Fenix_Profile::PERMISSION_VIEW){
        
        $ret = false;
        
        $modelProfile = Util::getModelProfile();
        
        // O model de perfil est� ativo no sistema
        if($modelProfile){
            
            // H� usu�rio logado
            if( Zend_Auth::getInstance()->hasIdentity() ){
                
                // Usu�rio logado
                $user = Zend_Auth::getInstance()->getIdentity();
            
                // Perfil do usu�rio logado
                $idProfile = $user->{ Util::getConfig(Util::CONFIG_AUTH)->column_profile };
                    
                // Usu�rio de perfil administrador sempre tem permiss�o
                if($idProfile == Util::_ID_PERFIL_ADMINISTRADOR){
                    $ret = true;
                }
                // Usu�rios diferentes faz a verifica��o
                else {
                    
                    $modelProfilePermission = Model::factory( $modelProfile->getName(), Util::getConfig(Util::CONFIG_PROFILE)->section_permissions );
                    
                    // Faz a consulta para localizar 
                    list($profileSchemaName, $profileTableName) = explode(".", $modelProfile->getTableName());
                    list($profilePermissionsSchemaName, $profilePermissionsTableName) = explode(".", $modelProfilePermission->getTableName());
                    list($modelSchemaName, $modelTableName) = explode(".", Util::getModelModel()->getTableName());
                    
                    $select = $modelProfilePermission->prepareSelect( array('id_model', 'name', 'value') );
                    $select->joinLeft($modelTableName, "{$modelTableName}.id = {$profilePermissionsTableName}.id_model", array(), $modelSchemaName);
                    $select->columns( array('name' => new Zend_Db_Expr("COALESCE({$profilePermissionsTableName}.name, {$modelTableName}.schema||'.'||{$modelTableName}.name)")) );
                    $permissions = $select->where('id_'.$profileTableName.' = ?', $idProfile)->query()->fetchAll();
                    
                    foreach ($permissions as $record){
                        // Se � a checagem de um model
                        if( (is_object($item) && $item instanceof Model && !empty($record['id_model']) && $record['name'] == $item->getName())
                        
                        // Se � a checagem de uma string
                        ||  (is_string($item) && empty($record['id_model']) && $record['name'] == $item) ){
                            
                            // Valida a permiss�o
                            if(($record['value'] & $permission) > 0){
                                $ret = true;
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        // Quando o model de perfil n�o est� ativo no sistema o padr�o
        // � considerarado sempre que o usu�rio tem as permiss�es
        else {
            $ret = true;
        }
        
        return $ret;
    }
    
}










