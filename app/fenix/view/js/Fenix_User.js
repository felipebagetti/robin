//@include "/app/fenix/view/js/Fenix.js"

Fenix_User = {};

Fenix_User.generateTfaSecret = function(record){

    var generate = function(){
	    var url = Fenix_Model.grids.last().grid('getOptions').baseUri+'generate-tfa-secret?id='+record.id;
	    
	    Fenix.alert('<center>Escanei o código a seguir com o aplicativo:<br><br>Google Authenticator<br>ou<br>Duo Mobile<br><br><img src="'+url+'"></center>', 'Código de Autenticação');
    }
    
    if(record.tfa_secret == 'Sim'){
        Fenix.confirm('Novo Código', '<strong>Atenção</strong><br><br>Um código de autenticação já existe para esse usuário, deseja gerar um novo e eliminar o antigo?<br><br>Depois disso não será possível utilizar o código antigo para acessar o sistema.', generate);
    } else {
        generate();
    }
    
    
}