<?php 

require_once 'Zend/Mail.php';

class Robin_Model extends Model {
    
    /**
     * Remetente padr�o de email
     * @var unknown
     */
    public static $emailFrom = "contato@robin.eramo.com.br";
    
    /**
     * Nome do remetente padr�o
     * @var String
     */
    public static $emailFromText = "Robin";
    
    /**
     * E-mail de suporte (para ser usado como replyTo em algumas mensagens)
     * @var unknown
     */
    public static $emailSuporte = "suporte@robin.eramo.com.br";
    
    /**
     * Nome do destinat�rio padr�o (para ser usado como replyTo em algumas mensagens)
     * @var String
     */
    public static $emailSuporteText = "Suporte Robin";
    
    /**
     * E-mail padr�o que n�o aceita resposta
     * @var String
     */
    public static $emailNaoResponder = "naoresponda@robin.eramo.com.br";
    
    /**
     * Envia um e-mail usando o servi�o de e-mail da Eramo Software
     *
     * @param String $to Destinat�rio do e-mail
     * @param String $subject Assunto do e-mail
     * @param String $message Mensagem a ser enviada
     * @param String $from Endere�o de e-mail do remetente
     * @param String $fromText Nome do remetente
     * @param string $replyTo Endere�o de e-mail de Reply-To
     * @param string $replyToText Nome do Reply-To
     * @throws Exception
     * @return boolean
     */
    public static function sendmail($to, $subject, $message, $from, $fromText, $replyTo = null, $replyToText = null, $attachments = array()){
    
        if(!isset($to) || !$to){
            throw new Exception(__METHOD__ . ' - destinat�rio de e-mail n�o definido.');
        }
    
        if(!isset($subject) || !$subject){
            throw new Exception(__METHOD__ . ' - assunto de e-mail n�o definido.');
        }
    
        if(!isset($message) || !$message){
            throw new Exception(__METHOD__ . ' - mensagem de e-mail n�o definida.');
        }
    
        if(!isset($from) || !$from){
            $from = Robin_Model::$emailNaoResponder;
        }
    
        if(!isset($fromText) || !$fromText){
            throw new Exception(__METHOD__ . ' - nome do remetente de e-mail n�o definido.');
        }
    
        $toAddressList = array();
    
        foreach( explode(",", $to) as $toAddress ){
    
            $toAddress = trim($toAddress);
    
            if (!preg_match("/^[A-Za-z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/",$toAddress)){
                throw new Fenix_Exception("N�o foi poss�vel enviar o email. O email do destinat�rio <strong>".$toAddress."</strong> � inv�lido.<br><br>Por favor, verifique o endere�o digitado e tente novamente.");
            }
    
            $mail = new Zend_Mail();
            
            $bodyHtml = null;
            
            // Se h� tags HTML
            if(strip_tags($message) != $message){
                $bodyHtml = $message;
                
                // Remove quebrar \n e tab e espa�os em excesso no come�o das linhas
                $message = str_replace(array("\n", "\t"), "", $message);
                $message = preg_replace("([\s]{2,})", "", $message);
                $message = preg_replace("(^[\s\t]+)", "", $message);
                $message = preg_replace("(^[\r]+)", "", $message);
                // Transforma par�grafos em duas quebras de linhas
                $message = str_replace(array("</p>", "</P>"), "<br><br>", $message);
                // Remove as tags de t�tulo, JS e CSS
                $message = preg_replace('/\\<title\>(.*)\<\/title\>/i', "", $message);
                $message = preg_replace('/\\<style([^\>]*)\>(.*)\<\/style\>/i', "", $message);
                $message = preg_replace('/\\<script([^\>]*)\>(.*)\<\/script\>/i', "", $message);
                // Converte os BR em \n
                $message = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $message);
                $message = strip_tags($message);
    
            }
    
            if(isset($replyTo) && $replyTo){
                if(isset($replyToText) && $replyToText){
                    $mail->setReplyTo($replyTo, $replyToText);
                } else {
                    $mail->setReplyTo($replyTo);
                }
            }
    
            $mail->setFrom($from, $fromText);
            
            $mail->setBodyText(strip_tags(str_replace("<br>", "\n", $message)));
            
            if($bodyHtml){
                $mail->setBodyHtml($bodyHtml);
            }
            
            $mail->setSubject($subject);
            
            $mail->addTo($toAddress);
            
            if(is_array($attachments) && count($attachments) > 0){
                $email['attachments'] = array();
                foreach ($attachments as $attachment){
                    if( is_array($attachment) ){
                        if(isset($attachment['name']) && isset($attachment['base64'])){
                            $email['attachments'][] = $attachment;
                        } else {
                            throw new Exception(__METHOD__ . ' - o anexo "'.var_export($attachment, true).'" precisa ter os atributos \'name\' e \'bas64\' definidos.');
                        }
                    } else if(is_file($attachment)) {
                        $mail->createAttachment( file_get_contents($attachment), Zend_Mime::TYPE_OCTETSTREAM, Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64,  substr($attachment, strrpos($attachment, "/")+1 ) );
                    }
                }
            }
            
            
            $mail->send();
            
        }
    
        return true;
    }
    
    
}



















