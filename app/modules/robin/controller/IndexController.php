<?php

require_once __DIR__.'/ModelController.php';

class IndexController extends ModelController {
    
    const TYPE_LOGIN = 0;
    
    const TYPE_LOST_PASSWORD = 1;

    /**
     * Determina o model
     * @return String
     */
    protected function _model(){
        return "user";
    }
    
    /**
     * Desativa o controle de permissões
     */
    protected function _checkPermission($level = Fenix_Profile::PERMISSION_VIEW, Model $model = null){
        return true;
    }
    
    /**
     * Página inicial do sistema
     */
    public function _indexAction(){
        
        if( $user = Zend_Auth::getInstance()->getIdentity() ){
            if(is_object($user)){
                header("Location: " . Util::getBaseUrl() . 'goal/calendar');
                die();
            } else {
                Zend_Auth::getInstance()->getStorage()->clear();
            }
        }
        
        if(isset($_REQUEST['t']) && isset($_REQUEST['p']) && isset($_REQUEST['s'])){
            
            // Pode ser que se receba uma requisição de login antes do sistema estar pronto
            // Caso aconteça isso aconteça o trecho abaixo ai esperar até que a instância
            // do sistema esteja pronta (o usuário esteja cadastrado no sistema)
            $model = null;
            do {
                try {
                    $modelUser = Util::getModelUser();
                    if($modelUser && $modelUser instanceof Model && $modelUser->prepareSelect()->query()->rowCount() > 0){
                        $model = $modelUser;
                    }
                } catch (Exception $e){
                    $model = null;
                }
            } while($model === null);
            
        }
        
        $view = new View( "index.html.php" );
        
        $this->_prepareAceCss($view);
        $this->_prepareAceJs($view);
        
        $view->addJs("robin/Robin_Index.js");
        
        $view->addOnload("$('form').on('submit', Robin_Index.formSubmit);");
        
        if( isset($_REQUEST['fail']) ){
            $view->addOnload("window.logonCallback('-1')");
        }
        
        $view->addOnload("$('#login').focus();");
        
        $view->render();
        
        die();
    }
    
    /**
     * Faz o envio do e-mail para o
     */
    public function redefinirSenhaEmailAction(){
        
        $usuario = Util::getModelUser()->prepareSelect()->where('email LIKE ?', $_REQUEST['email'])->query()->fetch();
        
        // Caso o usuário seja localizado
        if( $usuario ){
            
            // Cria um token seguro para validar a requisição
            $token = array( "time" => time() );
            $token["hash"] = sha1( json_encode($token) . rand(1,9999999) );

            $usuario['token'] = json_encode($token);
            
            Util::getModelUser()->update($usuario);
            
            // Faz o envio do email
            $subject =  "Robin - Recuperação de senha";
            
        $message = 'Olá, recebemos um pedido de recuperar a senha da sua conta.
        
Utilize o link abaixo durante as próximas 12 horas para redefinir sua senha.
        
<a href="'.Util::getBaseUrl().'redefinir-senha?t={token}">'.Util::getBaseUrl().'redefinir-senha/?t={token}</a>
        
Se você tem alguma dúvida ou precisa de ajuda, é só responder essa mensagem.

Obrigado,
Equipe Robin
'.trim(Util::getBaseUrl(), "/");
            
            $message = str_replace("{token}", $token["hash"], $message);
            $message = str_replace("\n", "<br>", $message);
            
            // Para disponibilizar a classe
            Model::factory("goal");
            
            // Efetua o envio do email
            Robin_Model::sendmail($usuario['email'], $subject, $message, 'suporte@robin.eramo.com.br', Robin_Model::$emailFromText, Robin_Model::$emailSuporte, Robin_Model::$emailSuporteText);
            
            die("<script type='text/javascript'>window.parent.Robin_Index.redefinirSenhaSucesso()</script>");
        } else {
            die("<script type='text/javascript'>window.parent.Robin_Index.redefinirSenhaFalha()</script>");
        }
        
    }
    
    /**
     * Faz a validação do token
     * @param String $hash
     * @return mixed
     */
    protected function _redefinirSenhaValidarToken($hash, $js = false){
        
        $ret = false;
        
        if(strlen($hash) === 40){
            
            $usuario = Util::getModelUser()->prepareSelect()->where('token LIKE ?', "%\"{$hash}\"%")->query()->fetch();
            
            $token = json_decode($usuario['token'], true);
            
            // O token só é válido por 12 horas
            if($token['time'] > time() - 43200){
                $ret = $usuario;
            }
            
        }
        
        // Encerra a execução em caso de token inválido ou expirado
        if($ret === false){
            if($js){
                die("<script type='text/javascript'>window.parent.location = '".Util::getBaseUrl()."'</script>");
            } else {
                header("Location: ".Util::getBaseUrl());
                die();
            }
        }
        
        return $ret;
    }
    
    /**
     * Tela de recuperar senha
     */
    public function redefinirSenhaAction(){

        // Valida o token recebido
        $this->_redefinirSenhaValidarToken($_REQUEST['t']);
        
        // Mostra o formulário de redefinição 
        $view = new View( "redefinirSenha.html.php" );
        
        $this->_prepareAceCss($view);
        $this->_prepareAceJs($view);
        
        $view->addJs("robin/Robin_Index.js");
        
        $view->token = $_REQUEST['t'];
        $view->addOnload("$('#password').focus();");
        $view->addOnload("$('form').on('submit', Robin_Index.redefinirSenhaValidar);");
        
        $view->render();
        
        die();
    }
    
    /**
     * Tela de recuperar senha
     */
    public function redefinirSenhaSubmitAction(){
        
        Model::beginTransaction();
        
        // Valida o token recebido
        $usuario = $this->_redefinirSenhaValidarToken($_REQUEST['token'], true);
        
        $usuario['token'] = new Zend_Db_Expr("NULL");
        $usuario['password'] = $_REQUEST['password'];
        
        Util::getModelUser()->update($usuario);
        
        Model::commitTransaction();
        
        Util::getModelUser()->logon($usuario['login'], $_REQUEST['password']);
        
        die("<script type='text/javascript'>window.parent.Robin_Index.redefinirSenhaFinal()</script>");
    }
    
    /**
     * Prepara o comando de geração da nova instância do sistema
     */
    public function cadastroSubmitAction(){
    
        // Preparação dos dados
        $dados = Util::utf8_decode($_POST);
        
        Model::beginTransaction();
        
        // Verifica se o email já está cadastrado
        $emailCadastrado = count( Util::getModelUser()->prepareSelect("id")->where('email LIKE ?', $dados['email'])->query()->fetchAll() );
        
        // Mensagem de email já cadastrado
        if($emailCadastrado){
            die("<script type='text/javascript'>window.parent.Robin_Index.cadastroErro();</script>");
        }
        
        $dados['login'] = $dados['email']; 
        
        $id = Model::factory("user")->insert($dados);
        
        $this->_cadastroEmail($dados);
        
        Model::commitTransaction();
        
        // Loga o usuário no sistema
        Util::getModelUser()->logon($dados['email'], $dados['password']);
        
        $location = Util::getBaseUrl().'user/record?id='.$id;
        
        die("<script type='text/javascript'>if(window.parent.logonCallback) window.parent.logonCallback('".$location."');</script>");
    }
    

    /**
     * Faz o envio do e-mail
     * @param String[] $config
     */
    protected function _cadastroEmail($user){
    
        // Para disponibilizar a classe
        Model::factory("goal");
    
        $replyTo = Robin_Model::$emailSuporte;
        $replyToText = Robin_Model::$emailSuporteText;
    
        $subject = "Seja bem vindo ao Robin - Confirme seu email";
    
        $link = '<a href="'.Util::getBaseUrl().'ativar?id={UID}">'.Util::getBaseUrl().'ativar?id={UID}</a>';
        $logo = Util::getBaseUrl().'robin/img/logo.png';
    
        $message = file_get_contents(dirname(__FILE__).'/../database/email_template/bemvindo.html');
    
        $message = utf8_decode($message);
    
        $message = str_replace("{PRODUTONOME}", "Robin", $message);
        $message = str_replace("{PRODUTOURL}", Util::getBaseUrl(), $message);
        $message = str_replace("{LINK}", $link, $message);
        $message = str_replace("{LOGO}", $logo, $message);
        $message = str_replace("{NOME}", ucwords($user['nome']), $message);
        $message = str_replace("{UID}", md5( $user['email']  ), $message);
    
        // Efetua o envio do email
        Robin_Model::sendmail($user['email'], $subject, $message, Robin_Model::$emailSuporte, Robin_Model::$emailFromText);
    }

    /**
     * Faz a ativação do email do usuário
     */
    public function ativarAction(){
    
        if( isset($_REQUEST['id']) ){
    
            Table::getDefaultAdapter()->getConnection()->sqliteCreateFunction('md5', 'md5', 1);
            
            $user = Model::factory("user")->prepareSelect()->where('md5(email) = ?', $_REQUEST['id'])->query()->fetch();
            
            if(is_array($user) && empty($user['email_validated'])){
                $user['email_validated'] = new Zend_Db_Expr("NOW()");
                Model::factory("user")->update($user);
                
            }
            
            header("Location: ".Util::getBaseUrl()."user/record?id=".$user['id']);
            die();
    
        } else {
            header("Location: ".Util::getBaseUrl());
            die();
        }
    
        header("Location: ".Util::getBaseUrl());
        die();
    }
    
    
}























