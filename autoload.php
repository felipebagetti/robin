<?php

// Definições de include

set_include_path(get_include_path().PATH_SEPARATOR.__DIR__."/app/library/".PATH_SEPARATOR.__DIR__."/app/");

$autoloadList = array('Fenix_Exception' => 'Eramo/Exception/Fenix_Exception.php',
                      'Util' => 'Eramo/Util/Util.php',
                      'AttributeList' => 'Eramo/Util/AttributeList.php',
        
                      'Zend_Auth' => 'Zend/Auth.php',
        
                      'Zend_Db' => 'Zend/Db.php',
                      'Zend_Db_Expr' => 'Zend/Db/Expr.php',
                      'Zend_Db_Table_Abstract' => 'Zend/Db/Table/Abstract.php',
                      'Zend_Db_Select' => 'Zend/Db/Select.php',
                      'Zend_Registry' => 'Zend/Registry.php',
        
                      'Zend_Session_Namespace' => 'Zend/Session/Namespace.php',
        
                      'Model' => 'Eramo/Model/Model.php',
                      'ModelConfig' => 'Eramo/Model/ModelConfig.php',
                      'ModelField' => 'Eramo/Model/Model.php',
                      'ModelSql' => 'Eramo/Model/ModelSql.php',
        
                      'Table' => 'Eramo/Table/Table.php',
                      'Db_Profiler' => 'Eramo/Table/Db_Profiler.php',

                      'Eramo_Controller_Front' => 'Eramo/Controller/Front.php',
                      'Eramo_Controller_Action' => 'Eramo/Controller/Action.php',
        
                      'Fenix_ModelController' => 'Eramo/Fenix/controller/ModelController.php',
                      'UploadException' => 'Eramo/Fenix/controller/ModelController.php',
                      
                      'Fenix_FileController' => 'Eramo/Fenix/controller/FileController.php',
                      'Fenix_AuthController' => 'Eramo/Fenix/controller/AuthController.php',
                      'Fenix_UserController' => 'Eramo/Fenix/controller/UserController.php',
                      'Fenix_ProfileController' => 'Eramo/Fenix/controller/ProfileController.php',
                      'Fenix_MenuController' => 'Eramo/Fenix/controller/MenuController.php',
        
                      'Fenix_User' => 'Eramo/Fenix/model/User.php',
                      'Fenix_File' => 'Eramo/Fenix/model/File.php',
                      'Fenix_Profile' => 'Eramo/Fenix/model/Profile.php',
                      'Fenix_Menu' => 'Eramo/Fenix/model/Menu.php',
        
                      'View' => 'Eramo/Page/Page.php',
                      'ViewInternal' => 'Eramo/Page/PageInternal.php',
                      'Page' => 'Eramo/Page/Page.php',
                      'Grid' => 'Eramo/Page/Grid.php',
                      'Form' => 'Eramo/Page/Form.php');

function autoload($className){
    global $autoloadList;
    if(isset($autoloadList[$className])){
        $cwd = null;
        if(getcwd() != dirname(__FILE__)){
            $cwd = getcwd();
            chdir( dirname(__FILE__) );
        }
        require_once $autoloadList[$className];
        if( $cwd != null ){
            chdir( $cwd );
        }
    }
}

// Define o método padrão do autoload
spl_autoload_register("autoload", true);
