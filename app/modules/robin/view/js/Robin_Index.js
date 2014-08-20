
Robin_Index = {};

Robin_Index.show = function(id){
    $('div.center').has('.label').remove();
    $('.widget-box.visible').removeClass('visible');
    $('#'+id).addClass('visible');
}

Robin_Index.formSubmit = function(form){
    
    var ret = true;
    
    var inputs = $('input', this);
    
    inputs.each(function(k,v){
        if ($(v).is(':visible') == true
        && ($(v).attr('type') == 'text' || $(v).attr('type') == 'email' || $(v).attr('type') == 'password')
        && !$(v).val()){
            if(ret == true){
	            $(v).focus();
	            ret = false;
            }
        }
    });
    
    if(ret == true){
//	    $('[type=submit]').attr('disabled', true);
    } else {
        var plural = inputs.length > 1 ? 's' : '';
        Robin_Index.alert('Prencha o'+plural+' campo'+plural+' para prosseguir!').addClass('label-warning');
    }
    
    return ret;
}

Robin_Index.alert = function(text){
    
    $('[type=submit]').attr('disabled', false);
    
    $('div.center').has('.label').slideToggle( function(){ $(this).remove(); } );
    
    var div = $('<div class="center" style="display: none; margin-bottom: 14px;"><div class="label" style="height: auto;"> '+text+' </div></div>')
	            .prependTo( $('form') )
	            .slideToggle();

    return $('div', div);
}

Robin_Index.redefinirSenhaFalha = function(){
    
    Robin_Index.alert('Email não cadastrado!').addClass('label-danger');
    
    $('[name=email]').focus();
}

Robin_Index.redefinirSenhaSucesso = function(){
    
    Robin_Index.alert('Um email foi enviado ao seu endereço!').addClass('label-info');
    
    $('[name=email]').val("").focus();
    
}

Robin_Index.redefinirSenhaFinal = function(){
    
    Robin_Index.alert('Sucesso!<br>Você está sendo redirecionado ao sistema...').addClass('label-info');
            
    window.setTimeout(function(){ window.location = './'; }, 3000);
}

Robin_Index.redefinirSenhaValidar = function(){
    
    if(!$("#password").val()){
        Robin_Index.alert('Senha inválida.').addClass('label-warning');
        $("#password").val("").focus();
        return false;
    }
    
    if($("#password").val().length < 6){
        Robin_Index.alert('Escolha uma senha com pelo menos 6 caracteres.').addClass('label-warning');
        $("#password").val("").focus();
        $("#password-confirm").val("");
        return false;
    }
    
    if( $("#password").val() != $("#password-confirm").val() ){
        Robin_Index.alert('A confirmação da senha não é válida.').addClass('label-warning');
        $("#password-confirm").val("").focus();
        return false;
    }
    
    return Robin_Index.formSubmit($('form'));
}

Robin_Index.cadastroErro = function(){

    Robin_Index.alert('Usuário já cadastrado!').addClass('label-danger');
    
    
}

window.logonCallback = function(data){
    
    $('div.center').has('.label').slideToggle( function(){ $(this).remove(); } );
    
    if(data == "-1"){
        
        Robin_Index.alert('Email ou senha incorretos!').addClass('label-danger');
        
        $('#login').focus();
        
    } else if(data == "tfa"){
        
        Robin_Index.alert('Digite seu código de autenticação!').addClass('label-info');
                
        $('#tfa').closest('div').slideToggle( function(){ $('#tfa').focus(); } );
        
    } else {
        
        window.location = data;
        
    }
    
}
