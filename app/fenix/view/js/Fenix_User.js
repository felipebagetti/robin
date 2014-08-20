//@include "/app/fenix/view/js/Fenix.js"

Fenix_User = {};

Fenix_User.generateTfaSecret = function(record){

    var generate = function(){
	    var url = Fenix_Model.grids.last().grid('getOptions').baseUri+'generate-tfa-secret?id='+record.id;
	    
	    Fenix.alert('<center>Escanei o c�digo a seguir com o aplicativo:<br><br>Google Authenticator<br>ou<br>Duo Mobile<br><br><img src="'+url+'"></center>', 'C�digo de Autentica��o');
    }
    
    if(record.tfa_secret == 'Sim'){
        Fenix.confirm('Novo C�digo', '<strong>Aten��o</strong><br><br>Um c�digo de autentica��o j� existe para esse usu�rio, deseja gerar um novo e eliminar o antigo?<br><br>Depois disso n�o ser� poss�vel utilizar o c�digo antigo para acessar o sistema.', generate);
    } else {
        generate();
    }
    
    
}