<?php

abstract class Fenix_UserController extends Fenix_ModelController {

    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_model()
     */
    protected function _model(){
        return Util::getConfig(Util::CONFIG_AUTH)->model;
    }
    
    /**
     * @return Fenix_User
     */
    protected function _getModel(){
        return parent::_getModel();
    }
    
    /**
     * Checa permissões de acesso
     * @param int $level Uma das constantes de Fenix_Profile::PERMISSION_*
     */
    protected function _checkPermission($level = Fenix_Profile::PERMISSION_VIEW, Model $model = null){
        $ret = parent::_checkPermission($level, $model);
        // Libera o acesso a tela de alterar senha e sua action de salvamento
        if($this->_action == "change-password" || $this->_action == "change-password-save"){
            $ret = true;
        }
        return $ret;
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_getForm()
     */
    protected function _getForm(){
        
        $form = parent::_getForm();
        
        if($form->getOption(Form::OPTION_VIEW)){
            $form->deleteField('password');
        }
        
        return $form;
    }
    
    public function changePasswordAction(){
        
        $user = Zend_Auth::getInstance()->getIdentity();
        
        $this->_setFormOption(Form::OPTION_TITLE, 'Trocar Senha');
        $this->_setFormOption(Form::OPTION_RECORD, $this->_getModel()->select($user->id));
        $this->_setFormOption(Form::OPTION_ACTION, 'change-password-save');
        
        $form = $this->_getForm();
        
        $record = $form->getOption(Form::OPTION_RECORD);
        
        $form->addOnload("$('#password').focus();");
        
        $form->setFieldAttribute('name', FormField::FORM, 'v');
        $form->setFieldAttribute('login', FormField::FORM, 'v');
        $form->setFieldAttribute('email', FormField::FORM, 'v');
        $form->setFieldAttribute('email', FormField::TITLE, 'Email');
        
        iF($record['login'] == $record['email']){
            $form->deleteField('login');
        }
        
        if($form->getField('profile')){
            $form->deleteField('profile');
        }
        $form->deleteField('password');
        
        $form->addField( FormField::factory('password', ModelConfig::PASSWORD)->setTitle('Senha Atual')->setRequired(true) );
        $form->addField( FormField::factory('password_new', ModelConfig::PASSWORD)->setTitle('Nova Senha')->setRequired(true) );
        $form->addField( FormField::factory('password_confirm', ModelConfig::PASSWORD)->setTitle('Confirmar Nova Senha')->setRequired(true) );
        
        $form->render();
    }
    
    public function changePasswordSaveAction(){
        
        $user = $this->_getModel()->select( Zend_Auth::getInstance()->getIdentity()->id, false );
        
        if($user['password'] === $this->_getModel()->passwordHash($user['login'], $_REQUEST['password'])){
            
            if($_REQUEST['password_new'] === $_REQUEST['password_confirm']){
                $passwordHash = $this->_getModel()->passwordHash( $user['login'], $_REQUEST['password_new'] );
                $this->_getModel()->updatePassword( $user['login'], $passwordHash );
                
                die("Ok");
            } else {
                throw new Fenix_Exception("A confirmação da nova senha não está igual à nova senha.");
            } 
            
        }
        
        throw new Fenix_Exception("Senha atual incorreta");
    }
    

    protected function _getGrid(){
    
        $grid = parent::_getGrid();
        
        $grid->addJs('fenix/Fenix_User.js');
        
        //$grid->addOnload("Fenix.alert(123);");
    
        if($col = $grid->getColumn('tfa_secret')){
            $col->setStyleContent('center');
            
            $grid->addButtonAt(0, 'Gerar Código', 'Fenix_User.generateTfaSecret', 'glyphicon glyphicon-qrcode');
        }
    
        return $grid;
    }
    
    /**
     * Gera o segredo do TFA
     */
    public function generateTfaSecretAction(){
    
        require_once 'Others/GoogleAuthenticator.php';
        require_once 'Others/phpqrcode.php';
        
        $usuario = $this->_getModel()->select($_REQUEST['id']);
    
        $ga = new GoogleAuthenticator();

        $secret = $ga->createSecret();

        $usuario['tfa_secret'] = $secret;

        $this->_getModel()->update($usuario);
    
        $image = dirname($_SERVER['SCRIPT_FILENAME'])."/tmp/files/".sha1(json_encode($usuario));
    
        $nome = preg_replace("/([^A-Za-z0-9]+)/", "_", Util::strip_accents($usuario['name']));
    
        QRcode::pngOAuth($nome."@".$_SERVER['HTTP_HOST'], $usuario['tfa_secret'], $image);
        
        header("Content-Type: image/png");
        header("Content-Length: ".filesize($image));
        header("Content-Disposition: inline; filename=qrcode.png");
        
        echo file_get_contents($image);
        unlink($image);
        die();
    }
    
    /**
     * (non-PHPdoc)
     * @see Fenix_ModelController::_data()
     */
    protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
    
        $select = parent::_data($filters, $query, $sortCol, $sortDir, $limit, $offset);
        
        if($this->_getModel()->getField('tfa_secret')){
            $cols = array('tfa_secret' => new Zend_Db_Expr(" (CASE WHEN tfa_secret IS NOT NULL THEN 'Sim' ELSE '-' END) "));
            
            $select->columns($cols);
        }
    
        return $select;
    }
    
}


