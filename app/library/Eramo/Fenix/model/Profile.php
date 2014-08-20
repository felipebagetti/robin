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
     * Valida se o perfil logado no sistema tem a permissão de acesso definida
     * 
     * @param Model|String $item Model a ser verificado ou String com nome da permissão personalizada/item de menu
     * @param int $level
     */
    public static function checkPermission($item, $permission = Fenix_Profile::PERMISSION_VIEW){
        
        $ret = false;
        
        $modelProfile = Util::getModelProfile();
        
        // O model de perfil está ativo no sistema
        if($modelProfile){
            
            // Há usuário logado
            if( Zend_Auth::getInstance()->hasIdentity() ){
                
                // Usuário logado
                $user = Zend_Auth::getInstance()->getIdentity();
            
                // Perfil do usuário logado
                $idProfile = $user->{ Util::getConfig(Util::CONFIG_AUTH)->column_profile };
                    
                // Usuário de perfil administrador sempre tem permissão
                if($idProfile == Util::_ID_PERFIL_ADMINISTRADOR){
                    $ret = true;
                }
                // Usuários diferentes faz a verificação
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
                        // Se é a checagem de um model
                        if( (is_object($item) && $item instanceof Model && !empty($record['id_model']) && $record['name'] == $item->getName())
                        
                        // Se é a checagem de uma string
                        ||  (is_string($item) && empty($record['id_model']) && $record['name'] == $item) ){
                            
                            // Valida a permissão
                            if(($record['value'] & $permission) > 0){
                                $ret = true;
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
        }
        // Quando o model de perfil não está ativo no sistema o padrão
        // é considerarado sempre que o usuário tem as permissões
        else {
            $ret = true;
        }
        
        return $ret;
    }
    
}










