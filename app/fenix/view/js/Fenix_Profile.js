//@include "/app/fenix/view/js/Fenix.js"

Fenix_Profile = {};

Fenix_Profile.formatterName = function(text, record, column, grid, table, tr, td){
    if(record._level > 0){
        text = "".lpad("&nbsp;&nbsp;&nbsp;", record._level*18) + " &rarr; " + text;
    }
    return text;
}

Fenix_Profile.formatterCheckboxSetHeaderEvent = function(grid, column){
    var th = $('#'+grid.element.id+"-"+column.name);
    if( ! th.data('headerEventSet') ){
        
        th.data('headerEventSet', true);
        
        // � uma edi��o de registro
        if( $('#name').hasClass('required') ){
	        
	        th.on('click', function(){
                $('input[type="checkbox"]').filter( "input[name*='"+column.name+"']" ).prop('checked', false);
	            $('input[type="checkbox"]').filter( "input[name*='"+column.name+"']" ).click();
	        });
            
	        th.css('cursor', 'pointer');
        }
    }
}

// Formatter de cria��o dos checkbxo
Fenix_Profile.formatterCheckbox = function(text, record, column, grid, table, tr, td){

    // Define o evento de cabe�alho para marcar/desmarcar todos os itens da coluna
    Fenix_Profile.formatterCheckboxSetHeaderEvent(grid, column);
    
    // Cria os inputs
    var key = record.id_model ? record.id_model : record.name;
    
    // Quando n�o h� um model associado impede que os checkbox al�m de none e view sejam criados
    if(!record.id_model && column.name != 'none' && column.name != 'view'){
        return;
    }
    
    var input = $('<input type="checkbox" id="'+column.name+'['+key+']" name="'+column.name+'['+key+']" value="'+column.data+'" />');
    
    // Define o elemento como marcado
    if(record.value & column.data || record.value === column.data){
        input.prop('checked', true);
    }
    
    // Remove itens j� existentes na c�lula (no caso de atualiza��o da grid)
    $('input', td).remove();
    
    // Insere o checkbox na c�lula
    td.append(input);

    // � uma edi��o de registro
    if( $('#name').hasClass('required') ){
        
        // Evento de clique para marcar/desmarcar outros itens quando o checkbox � clicado
	    input.on('click', function(e){
	        e.stopPropagation();
	        
	        if(this.checked == true){
		        var id = this.id.toString().match(/([^\[\]]+)/g);
		        
                // Quando se clica num elemento com permiss�o (diferente de none) desmarca none
		        if(this.value > 0){
		            $('input[id*=\'none['+id[1]+']\']').prop('checked', false);
		        }
                // Quando se clica em none desmarca todos os outros itens
                else {
		            $('input[id*=\'['+id[1]+']\']').not(this).prop('checked', false);
		        }
		        
                // Quando se clica num elemento > que view marca sempre a permiss�o view
		        if(this.value > 2){
		            $('input[id*=\'view['+id[1]+']\']').prop('checked', true);
		        }
	        }
	        
	    });
	    
        // Remove eventos que j� estejam na c�lula (atualiza��o da grid)
	    td.off('click');
        
        // Quando se clica na �rea da c�lula marca a permiss�o de todo jeito
	    td.on('click', function(){
	        $('input[type="checkbox"]', this).click();
	    });
        
        // Mouse pointer
	    td.css('cursor', 'pointer');
        
    }
    // Somente visualiza��o do registros
    else {
        
        // Desabilita o checbox
        input.attr('disabled', true);
        
    }
    
}

