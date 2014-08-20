//@include "/app/fenix/view/js/Fenix.js"

if(typeof Fenix_Model == "undefined"){
    Fenix_Model = {};
    Fenix_Model.grids = $();
    Fenix_Model.forms = $();
}

Fenix_Model.createUrl = function(action, options, params){
    var url = options.baseUri + action;
    
    var query = [];
    
    // Só adiciona informações do model a ser usado caso seja uma requisição
    // para o controller genérico do sistema
    if(url.indexOf("/fenix/model/") !== -1){
        query.push("_model=" + options.model);
    }
    
    // Só adiciona informações da section caso ela não seja a padrão
    // (que tem o mesmo nome do model).
    if(options.model && options.model.split(".").pop() != options.section){
        query.push("_section=" + options.section);
    }
    
    if(options && options.params){
        if($.type(options.params) == "string"){
            query.push(options.params);
        }
        if($.type(options.params) == "array"){
            query.push(options.params.join("&"));
        }
    }
    if(params && params.length > 0){
        if($.type(params) == "string"){
            query.push(params);
        }
        if($.type(params) == "array"){
            query.push(params.join("&"));
        }
    }
    
    if(query && query.length > 0){
        if(url.indexOf("?") == -1){
            url += '?'; 
        }
        url += '&' + query.join('&');
    }
    
    return url.replace("?&", "?");
}

Fenix_Model.record = function( page, params, view ){
    
    var action = "record"; 
    
    if(typeof view != "undefined" && view == true){
        action = "view"; 
    }
    
    var url = Fenix_Model.createUrl(action, page.options, params);
    
    if(!page.options.editOnLayer){
        
        window.location = url;
        
    } else if(Fenix.event != null && (Fenix.event.shiftKey == true || Fenix.event.metaKey == true || Fenix.event.altKey == true)){
        
        window.open(url);
        
    } else {
        
        var title = page.options.title;
        
        if(action == 'view'){
            title += ": Visualização";
        } else {
            title += (title ? ": " : '');
            title += (url.indexOf("id=") == -1 ? 'Novo Registro' : 'Alterar Registro');
        }
        
        Fenix_Model.page(url, title); 
        
    }
}

if(!Fenix_Model.pageOnload){
    Fenix_Model.pageOnload = function(){}
}

Fenix_Model._pageWidth = 750;

Fenix_Model.pageNumber = 0;
Fenix_Model.pageLock = {};
Fenix_Model.pageLoadingTimeout = null;

Fenix_Model.page = function( url, title, width ){
    
    // Só faz uma requisição se a lock de página estiver livre (evita
    // mais de uma requisição ao mesmo tempo para abrir uma página)
    if(JSON.stringify(Fenix_Model.pageLock) == "{}"){
	    
        Fenix_Model.pageLoadingTimeout = window.setTimeout(function(){ Fenix.alertHeader('Carregando dados...', null); }, 300);
        
        Fenix_Model.pageLock = {container: "fenix_r_" + Fenix_Model.pageNumber,
                                containerBtn: "fenix_r_" + Fenix_Model.pageNumber+"_btn",
                                title: $.type(title) == "undefined" ? "" : title,
                                width: (width ? width : Fenix_Model._pageWidth)};
	    
        Fenix_Model.pageNumber++;
                                
	    $(Fenix_Model.pageLock.container).remove();
	    $(Fenix_Model.pageLock.containerBtn).remove();
	    
	    $('body').append( $('<div id="'+Fenix_Model.pageLock.container+'" style="display: none"></div>') );
	    $('body').append( $('<div id="'+Fenix_Model.pageLock.containerBtn+'" style="display: none"></div>') );
	    
	    if(url.indexOf("?") == -1){
	        url += "?";
	    } else {
	        url += "&";
	    }
	    
	    url += ['_container='+Fenix_Model.pageLock.container, '_containerBtn='+Fenix_Model.pageLock.containerBtn].join("&");
    
	    $.getScript(url).fail(function( jqxhr, settings, exception ) {
	        Fenix_Model.pageLock = false;
		    console.log( jqxhr );
		    console.log( settings );
		    console.log( exception );
	    });
    }
}

Fenix_Model._pageCallback = function(){
    
    window.clearTimeout(Fenix_Model.pageLoadingTimeout);
    Fenix.alertHeaderClose();
    
    var callback = function(){
	    Fenix_Model.pageNumber--;
    };
    
    var options = {show: false, width: (Fenix_Model.pageLock.width ? Fenix_Model.pageLock.width : 750), callback: callback};
    
    var modal = Fenix.layer(Fenix_Model.pageLock.title, $('#'+Fenix_Model.pageLock.container), $('#'+Fenix_Model.pageLock.containerBtn), options);

    $('#'+Fenix_Model.pageLock.container).css('display', '');
    $('#'+Fenix_Model.pageLock.containerBtn).css('display', '');
    
    // T1490 - Tarefas/Solicitações - Salvar e Continuar - Na segunda abertura os botões do rodapé são duplicados
    // Correção para o problema mas sem localizar ao certo a fonte do problema
    modal.on('shown', function(){
        $('#'+Fenix_Model.pageLock.container).css('display', '');
        $('#'+Fenix_Model.pageLock.containerBtn).css('display', '');
    });
    
    // Executa o método Fenix_Model.pageOnload com a referencia do modal para
    // permitir que o onload de um formulário faça ações antes/depois da exibição
    Fenix_Model.pageOnload( modal );
    
    // Remove a trava depois da exibição do modal
    modal.on('shown', function(){ Fenix_Model.pageLock = {}; });
    
    // Redimensiona o modal para centralizá-lo
    modal.on('shown', function(){ $('body').modalmanager('layout'); });
    
    // Remove o footer caso não haja botões
    modal.on('shown', function(){ 
	    var footer = $('.modal-footer:last', modal);
	    if( $('.btn', footer).length == 0 ){
	        footer.remove();
	    }
    });
    
    // Define um método de fechar o modal para 'limpar' o conteúdo do layer
    modal.on('hidden', function(){
        var modal = $(this);
        // Fecha os select2 que possam estar abertos
        if($().select2){
            $('select', modal).select2('close');
        }
        // Remove da lista de grids os que estejam dentro do modal sendo fechado
        Fenix_Model.grids.each(function(){
            
            var grid = $('[id="'+this.id+'"]', modal);
            
            if(grid.length > 0){
                grid.remove();
                Fenix_Model.grids = Fenix_Model.grids.not( grid ) ;
            }
            
        });
        // Remove o hash de alguma aba do formulário
        Fenix_Model.forms.each(function(){
             
            var form = $('[id="'+this.id+'"]', modal);
            
            $(".nav a[href^=#]", form).each(function(){
                if(window.location.hash == "#"+this.href.split("#")[1]){
                    window.location.hash = '#';
                }
            });
            
        });
        // Remove da lista de forms os que estejam dentro do modal sendo fechado
        Fenix_Model.forms.each(function(){
            
            var form = $('[id="'+this.id+'"]', modal);
            
            if(form.length > 0){
                form.remove();
                Fenix_Model.forms = Fenix_Model.forms.not( form ) ;
            }
            
        });
    });
    
    modal.modal('show');
}

Fenix_Model["new"] = function( page ){
    Fenix_Model.record( page );
}

Fenix_Model.edit = function(record, page){
    Fenix_Model.record( page, ["id=" + record.id] );
}

Fenix_Model.editRecord = function( page ){
    if(page && page.options && page.options.record && page.options.record.id){
        
        // Visualização está sendo feita num layer, usa dados da grid se ela for
        // do mesmo model e section
        if(page.options.xhr == 1 && Fenix_Model.grids.length > 0){
            $.each(Fenix_Model.grids, function(index, div){
                var grid = $(div).grid('getObject');
                if(grid.options.model == page.options.model && grid.options.section == page.options.section){
                    // Fecha o layer de visualização
                    Fenix.layerClose();
                    // Edição padrão do grid
                    window.setTimeout(function(){
                        Fenix_Model.edit( page.options.record, grid );
                    }, 150);
                }
            });
        }
        // Caso contrário redireciona para a tela de edição completa  
        else {
            Fenix_Model.record( page, ["id=" + page.options.record.id] );
        }
        
    } else {
        console.log('Não foi possível editar o registro da página:');
        console.log(page);
    }
}

Fenix_Model.back = function(){
    window.history.go(-1);
}

Fenix_Model.close = function(){
    Fenix.layerClose();
}

Fenix_Model.view = function(record, page){
    Fenix_Model.record( page, ["id=" + record.id], true );
}

Fenix_Model["delete"] = function(record, page){
    
    if(record == null){
        record = page.options.record;
    }
    
    var callback = function(){
        
        var success = function(data, textStatus, xhr){
            if(page.options.submitCallback){
                page.options.submitCallback(page);
            } else {
	            Fenix_Model.submitCallback(page);
            }
            // Move o foco para o botão novo registro da grid
            $('button:contains(Novo)', Fenix_Model.grids).focus();
        }
        
        var url = Fenix_Model.createUrl('delete', page.options, ['id='+record.id]);
        
        $.get(url, success);
    }
    
    return Fenix.confirm('Excluir Registro', 'Deseja realmente excluir o registro?', callback, 'btn-danger' );
}

Fenix_Model.submitCallback = function(page){
    
    // Define a quantidade de modais abertos
    var isModal = $('.modal').has( page.element ).length > 0;
    
    // Procedimento sendo realizado através do Grid
    if(page.type == 'grid'){
        Fenix_Model.grids.grid('load');
    }
    // Procedimento sendo realizado através de um formulário avulso que não está aberto num layer
    else if(page.type == 'form' && isModal == false){
        window.location = Fenix_Model.createUrl( "", page.options );
    }
    // Procedimento sendo realizado através de um formulário dentro de um layer
    else if(page.type == 'form' && isModal == true){
        // Fecha o modal que tem o elemento como filho 
        $( page.element ).closest( '.modal' ).modal('hide'); 
        // Move o foco para o botão novo registro da grid
        $('button:contains(Novo)', Fenix_Model.grids).focus();
        // Recarrega por padrão todos os grids
        Fenix_Model.grids.grid('load');
    }
}

Fenix_Model.save = function(form){
    if( form.validate() == true ){
        form.submit();
    }
}

Fenix_Model.cancel = function(){
    if($('.modal').length > 0){
        Fenix.layerClose();
    } else if(window.history.length == 1) {
        window.close();
    } else {
        Fenix_Model.back();
    }
}


