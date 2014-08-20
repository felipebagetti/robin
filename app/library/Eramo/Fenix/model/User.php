<?php 

class Fenix_User extends Model {
    
    /**
     * @return Fenix_User
     */
    public static function factory($name = "user", $section = null){
        return parent::factory($name, $section);
    }
    
    /**
     * Validates a item before insert/update
     * 
     * @param String[][] $item
     * 
     * @return String[][] Same $item validated
     */
    protected function _validate($item){
        
        // Só define a senha caso ela seja definida no formulário de criação/alteração
        if(isset($item['password'])){
            if(!empty($item['password'])){
                $item['password'] = $this->passwordHash($item['login'], $item['password']);
            } else {
                unset($item['password']);
            }
        }
        
        return $item;
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::insert()
     */
    public function insert($item){
        
        $item = $this->_validate($item);
        
        return parent::insert($item);
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::update()
     */
    public function update($item){
        
        $item = $this->_validate($item);
        
        return parent::update($item);
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::select()
     */
    public function select($id, $unsetPassword = true){
        
        $item = parent::select($id);
        
        if($unsetPassword){
            unset($item['password']);
        }
        
        return $item;
    }
    
    /**
     * Faz o tratamento padrão de uma senha no sistema
     * 
     * @param String $password
     * 
     * @return String
     */
    public function passwordHash($login, $password){
        
        $ret = sha1( $login . $password );
        
        $count = 16384;
        
        while($count-- >= 0){
            $ret = sha1( $login . $ret );
        }
        
        return $ret;
    }
    
    /**
     * Atualiza a senha do usuário logado
     *
     * @param String $password
     * 
     * @return int Id do registro atualizado
     */
    public function updatePassword($login, $password){
        return $this->getTable()->update(array('password' => $password), "login = '".$login."'");
    }
    
    /**
     * Valida que o cookie de lembrar computador é válido e está definido para esse usuário
     */
    protected function _authenticateTfaRememberValidate($user){
        
        $ret = false;
        
        $cookieName = '__fid_tfa_'.sha1($user['tfa_secret']);
        
        if( isset($_COOKIE[$cookieName]) ){
            
            list($content, $signature) = explode(";", $_COOKIE[$cookieName]);
            
            $signatureCheck = base64_encode(hash_hmac("sha1", $content, $user['tfa_secret'], true));
            
            if($signatureCheck === $signature){
                $ret = true;
            }
            
        }
        
        return $ret;
    }
    
    /**
     * Define o cookie de lembrar computador é válido e está definido para esse usuário
     */
    protected function _authenticateTfaRememberSet($user){
        
        $cookieName = '__fid_tfa_'.sha1($user['tfa_secret']);
        
        $content = date("Y-m-d-H:i:s");
        $signature = base64_encode(hash_hmac("sha1", $content, $user['tfa_secret'], true));
        
        $path = str_replace("//", "/", dirname($_SERVER['PHP_SELF']) . "/");
        $secure = isset($_SERVER['HTTPS']) && stripos($_SERVER['HTTPS'], 'on') !== false ? true : false;
        
        setcookie($cookieName, $content.";".$signature, time()+(60*86400), $path, null, $secure, true);
        
    }

    /**
     * Faz a autenticação do segredo do usuário
     * @param mixed[] $user
     * @param String $tfa
     * @return boolean true caso o tfa esteja inativo ou tenha havido sucesso na autenticação
     */
    public function authenticateTfa($user, $tfa, $rememberTfa = false){
        
        $config = Util::getConfig(Util::CONFIG_AUTH);
        
        $authenticated = false;
        
        // Se a autenticação em dois fatores está ativa requer que o usuário tenha o segredo já criado
        if($config->tfa){
        
            // Se o usuário não tem o segredo
            if(!$user[ $config->column_tfa_secret ]){
                $authenticated = new Fenix_Exception("Autenticação em dois fatores não configurada. Por favor, contacte o administrador do sistema.");
            }
            
            // Se a máquina está configurada para que seja lembrada deixa passar
            if( $this->_authenticateTfaRememberValidate($user) ){
                $authenticated = true;
            }
            // Se já veio o código de autenticação faz a checagem
            else if($user[ $config->column_tfa_secret ] && $tfa != null){
                require_once 'Others/GoogleAuthenticator.php';
                $ga = new GoogleAuthenticator();
        
                // Código verificado faz a escrita dos dados de autenticação
                if($ga->verifyCode($user[ $config->column_tfa_secret ], $tfa, 4)){
                    $authenticated = true;
                    
                    if($rememberTfa){
                        $this->_authenticateTfaRememberSet($user);
                    }
                }
            }
        
        }
        // Caso o sistema não esteja configurado com autenticação em dois fatores
        else {
            $authenticated = true;
        }
        
        return $authenticated;
    }

    /**
     * Autentica um usuário no sistema
     *
     * @param String $login Login do usuário
     * @param String $password Já no formato de hash
     * @param boolean $write Determina se a sesssão será iniciada (útil quando se está fazendo autenticação em 2 fatores)
     *
     * @return boolean true se autenticou
     */
    public function authenticate($login, $password, $tfa = null, $remember = false, $rememberTfa = false){
    
        $config = Util::getConfig(Util::CONFIG_AUTH);
    
        require_once 'Zend/Auth/Adapter/DbTable.php';
    
        $authAdapter = new Zend_Auth_Adapter_DbTable( $this->getTable()->getDefaultAdapter() );
    
        $authAdapter->setTableName( $config->model )
        ->setIdentityColumn( $config->column_user )
        ->setCredentialColumn( $config->column_password );
    
        $authAdapter->setIdentity( $login );
        $authAdapter->setCredential( $password );
    
        $auth = Zend_Auth::getInstance();
    
        $result = $auth->authenticate($authAdapter);
    
        if($result->isValid()){
    
            $userObj = $authAdapter->getResultRowObject(null, $config->column_password );
            
            $authenticated = $this->authenticateTfa( (array) $userObj, $tfa, $rememberTfa );
    
            // Erro qualquer de autenticação
            if($authenticated !== true){
                $auth->clearIdentity();
                if($authenticated instanceof Exception){
                    throw $authenticated;
                }
            }
            // Autenticação realizada com sucesso
            else {
                // Limpa o cache dos modelos caso seja uma versão de desenvolvimento
                if( Util::isDev() ){
                    Util::cacheClean();
                }
                // Armazena as informação de logon do usuário
                $auth->getStorage()->write($userObj);
                // Se o permanecer logado estiver ativo faz com que o cookie tenha vida mais longa
                if($remember === true){
                    $params = session_get_cookie_params();
                    setcookie(session_name(), session_id(), time()+30*86400, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
            }
    
            return $userObj->id;
        }
    
        return false;
    }
    
    /**
     * Faz o logon do usuário no sistema
     * @param String $login
     * @param String $password
     * @param string $tfa
     * @return boolean
     */
    public function logon($login, $password, $tfa = null, $remember = false, $rememberTfa = false){
    
        // Cria o hash padrão de logon de usuário
        $passwordHash = $this->passwordHash($login, $password);
    
        // Permite que o usuário logue com a senha em MD5 para que seja
        // mais fácil redefinir uma senha diretamente no banco de dados
        // Caso tenha sucesso faz a atualização da senha para o valor mais seguro
        if ( $this->authenticate($login, md5($password), $tfa, $remember, $rememberTfa) !== false ) {
            $this->updatePassword($login, $passwordHash);
        }
    
        // Faz o processo normal de logon de usuário no sistema
        if ( $this->authenticate($login, $passwordHash, $tfa, $remember, $rememberTfa) !== false ) {
            return true;
        }
    
        return false;
    }
    
}










