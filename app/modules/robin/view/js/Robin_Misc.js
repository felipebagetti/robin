
Robin_Misc = {};

Robin_Misc.codigoMunicipio = function(campoCidade, campoCep){
    // Caso seja carregado diretamente pelo CEP tenta localizar o código do município automaticamente
    $("#"+campoCidade).on('change', function(){
        // Somente faz o procedimento se não  há valor definido para a cidade
        if(!this.value){
	        var lastResponse = $("#"+campoCep).data('lastResponse');
            if(lastResponse && lastResponse.cidade){
                var that = $(this);
                that.select2('open');
                $('.select2-input:visible').val(lastResponse.cidade + " " + lastResponse.uf).trigger('keyup-change');
                // A cada 100ms checa se os resultados foram carregados e se existir somente uma
                // opção já a seleciona
                that.data('select2-interval', window.setInterval(function(){
                    if( $('.select2-result').length > 0 ){
                        if( $('.select2-result').length == 1 ){
                            var e = jQuery.Event("keydown"); e.which = 13;
                            $('.select2-input:visible').trigger( e );
                        }
                        window.clearInterval( that.data('select2-interval') );
                    }
                }, 100));
            }
        }
    });
    
}

Fenix.alertHeader = function(msg, timeout, type, container){
    
    if(!type){
        type = 'info';
    }
    
    type = 'label-' + type;
    
    var span = $('<span class="alert-header label '+type+' pull-right">'+msg+'</span>').css({'z-index': 99999});
    
    // Caso não haja um container definido explicitamente
    if(!container || container.length == 0){
        
        var modalTitle = $('.modal-title').not( $('.modal-title', $('.modal[id*=Carregando]')) );
        
        // Tem modal aberto
        if( modalTitle.length > 0 ){
	        container = modalTitle.last();
            span.css({'margin-top': '3px'});
        }
        // Não tem modal aberto
        else {
            container = $('.page-header:last');
            span.css({position: 'fixed', top: '55px', right: '20px'});
        }
        
    }
        
    span.appendTo( container );
    
    if(timeout && timeout > 0){
        window.setTimeout(function(){ span.remove(); }, timeout);
    }
    
    return span;
}

Robin_Misc.gridFilterAdd = function(span){
	
	var div = $('#filter-bar');
	
	if(!div.length){
		div = $('<div id="filter-bar" style="float:left; margin-top: 8px; margin-bottom: 8px;"></div>');
		
		$('#buttons-page').prepend(div);
	}
	
	div.append(span);
}

Robin_Misc.gridSelect = function(title, name, data, type, width, defaultValue){
    
    if(!type){
        type = "select";
    }
    
    if(typeof defaultValue == "undefined"){
        defaultValue = "";
    }

    var span = $('<span style="margin-top: 6px; text-align: left;">&nbsp;&nbsp;'+title+': </span>');
    
    $.fn.form.formatter[type]('', {}, {data: data, name: name, width: (width ? width : '140px')}, null, null, null, span);
    
    $('select', span).find("option[value='']").html(' -- Mostrar Todos -- ');
    
    $('select', span).change(function(){
        Fenix_Model.grids.grid('setUrlParam', name, this.value);
        Fenix.sessionStorage(window.location.toString()+'_'+name, this.value);
    });
    
    var val = Fenix.sessionStorage(window.location.toString()+'_'+name) || defaultValue;
    
    $('select', span).valChange( val );
    
    if( $('select', span).val() != val ){
        $('select', span).append( $('<option value="'+val+'">'+val+'</option>') );
        $('select', span).valChange( val );
    }
    
    $('select', span).change(function(){
        Fenix_Model.grids.grid('load');
    });
    
    Robin_Misc.gridFilterAdd(span);
    
    return $('select', span);
}

Robin_Misc.gridData = function(title, name){
    
    var span = $('<span style="margin-top: 6px; text-align: left;">&nbsp;&nbsp;'+title+' </span>');
    
    $.fn.form.formatter.date('', {}, {name: name}, null, null, null, span);
    
    $('input', span).change(function(){
        Fenix_Model.grids.grid('setUrlParam', name, this.value);
        Fenix.sessionStorage(window.location.toString()+'_'+name, this.value);
    });
    
    $('input', span).val( Fenix.sessionStorage(window.location.toString()+'_'+name) || '' ).trigger('change');
    
    $('input', span).change(function(){
        Fenix_Model.grids.grid('load');
    });
    
    Robin_Misc.gridFilterAdd(span);
    
    return $('input', span);
}

Robin_Misc.gridPeriodo = function(defaultValue, data){
    
    if( typeof defaultValue == "undefined" || isNaN(defaultValue) ){
        defaultValue = 12;
    }
    
    if( typeof data == "undefined"  ){
	    
        data = {
	        'Último mês':-2,
	        'Últimos 3 meses':3,
	        'Últimos 6 meses':6,
	        'Últimos 12 meses':12,
	        'Mês Anterior':-3,
	        'Mês Atual':-2,
	        'Ano Atual':-1,
	        'Tudo':0
	    };
        
    }
    
    var periodo = Robin_Misc.gridSelect('Período', 'periodo', data, 'select', '14em', defaultValue);
    $('option[value=""]', periodo).remove();
    
    // Período padrão é o de 12 meses
    if( periodo.val() == "" ){
        periodo.val( defaultValue );
    }
    
    periodo.trigger('change');
    
    periodo.append( $('<option value="-"></option>').html('Escolher Datas') );
    
    periodo.on('select2-selecting', function(e){
        
        // Marcador especial de escolher datas
        if(e.val == "-"){
            e.preventDefault();
            
            // Chama o layer de definição das datas
            var body = $('<div class="center"></div>');
            
		    body.append('Data de Início ');
		    $.fn.form.formatter.date('', {}, {name: 'data_inicio'}, null, null, null, body);
		    
		    body.append('&nbsp;&nbsp;Data de Fim ');
		    $.fn.form.formatter.date('', {}, {name: 'data_fim'}, null, null, null, body);
		    
		    var button = $('<button class="btn btn-sm btn-primary">Confirmar</button>').on('click', function(){
		        Fenix.alertHeaderClose();
		        
		        if($('#data_inicio').val() || $('#data_fim').val()){
		            
	               $('option[value*="a"]', periodo).remove();
	                var value = $('#data_inicio').val()+' a '+$('#data_fim').val();
	                periodo.append( $('<option value="'+value+'">'+value.trim().replace(/^a/, "").replace(/a$/, "")+'</option>') );
	                periodo.select2('val', value).trigger('change');
                    
		            Fenix.layerClose();
		        } else {
		            Fenix.alertHeader('Digite uma das datas!', null, 'warning')
		        }
		        
		    });
		    
		    Fenix.layer('Selecione as Datas', body, $('<div>').append(button), {width: '500px'});
            
            periodo.select2('close');
        }
       
    });
    
}

Robin_Misc.buttonsFormatter = function(text, record, column, grid, table, tr, td, removeButtons){

    $.fn.grid.formatter.buttons(text, record, column, grid, table, tr, td, removeButtons);

	$('button', td).each(function(pos, btn){
		var btn =$(btn);
		var title = btn.text();
		var icon = $('i', btn).remove().prop('class');
        
        // Substitui especificamente os botoes de excluir e editar dos glyphicon
        icon = icon.replace(/glyphicon\-(edit|trash)/g, 'icon-$1').replace(/ glyphicon /g, ' ');
        icon = icon.replace("icon-trash", 'icon-trash-o');
        
		btn.attr("data-rel","tooltip").attr("data-placement","top").attr("title", title);
        
        btn.removeClass('btn-xs');
        btn.removeClass('btn-default').addClass('btn-white no-hover');
        
        btn.addClass(icon);
        
		btn.html('');
		btn.tooltip();

	});
}

Robin_Misc.notification = function(notification){
    
    if( notification ){
        
        // Item principal
        var li = $('<li id="notification-center">');
        
        // Botão principal com a quantidade
        var dropdownToggle =  $('<a data-toggle="dropdown" class="dropdown-toggle" href="#" />').appendTo( li );
        var bell = $('<i class="icon-bell"></i>').appendTo( dropdownToggle );
        
        // Determina se há notificações não lidas
        // Cria o badge com a contagem de notificações não lidas
        if(notification.unread > 0){
	        bell.addClass( "icon-animated-bell" );
            dropdownToggle.append( '<span class="badge badge-important">'+notification.unread+'</span>' );
        }
        
        // Cria o layer do dropdown
        var dropdown = $( '<ul class="pull-right dropdown-navbar navbar-pink dropdown-menu dropdown-caret dropdown-close" />' ).appendTo( li );
        
        var dropdownHeader = $( '<li class="dropdown-header"><i class="icon-warning-sign"></i></li>' ).appendTo( dropdown );
        
        // Contagem de notificacações
        if(notification.data.length == 0){
            dropdownHeader.append( 'Nenhum pedido pendente' );
        } else if(notification.data.length == 1){
            dropdownHeader.append( '1 pedido pendente' );
        } else if(notification.data.length > 1){
            dropdownHeader.append( notification.data.length + ' pedidos pendentes' );
        }
        
        // Cria cada uma das notificações
        for(var i = 0; i < notification.data.length; i++){
            
            var record = notification.data[i];
            var profile = notification.url + 'user/profile?id=' + record.id_user_1;
            
            var picture;
            if (record.user_picture){
            	picture = notification.url + 'user/download?w=50&h=50&' + JSON.parse(record.user_picture).hash;
            } else {
            	picture = 'https://i.imgur.com/NT01cTg.png';
            }
            
            $( '<li>' +
                    '<a href="' + profile + '">' +
                        '<div class="clearfix">' +
                            '<span class="pull-left bolder">' +
                                '<img style="margin-right: 5px; border-radius: 2px;" src="'+ picture +'">' +
                                record.user_1.substr(0, 25) + (record.user_1.length > 25 ? '...' : '') +
                            '</span>' +
                        '</div>' +
                    '</a>' +
               '</li>'
            ).appendTo( dropdown );
             
        }
        
        dropdown.append( $('<li><a href="#">Ver todos os pedidos de amizade <i class="icon-arrow-right"></i></a></li>')
                            .click(function(e){ e.preventDefault(); window.location = Fenix.getBaseUrl()+'friendship/?pending'; return false; })
                        );
        
        $('.ace-nav').prepend( li );
        
    }
    
}

Robin_Misc.help = function(help, autostart){
    
    if(help){
    
        var step = 1;
        
	    for(var i in help){
	        
            var attr = {
                'data-step': step++,
                'data-intro': help[i].message,
                'data-position': help[i].position
            };
            
            if(help[i].tolltipClass){
                attr['data-tooltip-class'] = help[i].tolltipClass; 
            }
            
            $(i).attr(attr);
	    }
        
        $('#breadcrumbs').append( $('<a href="#"><i class="icon-info-sign"></i></a>').on('click', function(){ introJs().start(); return false; }) );
        
        if(autostart){
            introJs().start();
        }
        
    }
    
}

Robin_Misc.formatterOption = function(text, record, field, form, table, tr, td){
    $.fn.form.formatter.option(text, record, field, form, table, tr, td);

    var label = $('label', td).addClass('middle').css('margin-left', '-1em');
    var input = $('input', td).addClass('ace');
    
    label.html(input);
    
    if(field.description){
	    label.append( $("<span class='lbl'> "+field.description+"</span>") );
    }

}

// Adiciona o evento de construção da lista de filtros (labels) para toda vez que o grid for recarregado
Robin_Misc.gridShowFilters = function(fields){
    
    Fenix_Model.grids.last().off('grid-loaded.show-filters');
    Fenix_Model.grids.last().on('grid-loaded.show-filters', function(){
        
        var grid = $(this).grid('getObject');
        var filtersList = grid._filtersList;
        
        // Remove os elementos que já existam
        $('[data-field]').remove();
        
        // Adiciona os elementos que são filtros
        for( var field in filtersList ){
            
            for(var operator in filtersList[field] ){
                
                var values = filtersList[field][operator];
                
                for( var i = 0 ; i < values.length; i++ ){
                    
                    var column = grid.getColumn( field.replace(/^id_/, "")  );
                    
                    var dataField = column.type == 'fk' ? column.key+'_'+column.name : column.name;
                    var dataValue = values[i];
                    
                    var span = $( '<span class="label label-info" data-field="'+dataField+'" data-value="'+dataValue+'"></span>' );
                    
                    // Caso já haja uma lista pré-definda de valores
                    if(fields[column.name]){
	                    var span = $( fields[column.name][ values[i] - 1 ] ).prepend( column.title + ": " );
                    }
                    // Caso não haja definição 
                    else {
                        span.text( Robin_Misc.filterTitleGet( dataField, dataValue ) );
                    }
                    
                    // Botão de fechar com o evento correspondente
                    span.append(
                        $('<i class="icon-times label-close"></i>')
                        .on('click', function(){
                            // 1. Remove o elemento span (label) da página
                            var parent = $(this).parent().remove();
                            // 2. Remove o filtro da grid
                            grid.filtersDelete(parent.attr('data-field'), 'eq', parent.attr('data-value'));
                        }) 
                    )
                    .css('margin-right', '5px')
                    .insertAfter( $( '.page-header' ) );
                    
                }
            }
        }
    });
    
}

Robin_Misc.prepareFilters = function(fields){
    
    // Clicando fora do popover fechá-lo
    Robin_Misc.popoverHide();
    
    // Evento de filtros da grid
    Robin_Misc.gridShowFilters(fields);
    
    // Adiciona nas colunas que serão filtradas os eventos de adicionar filtro
    for(var field in fields){
        
        if( $('.icon-filter' , $('#element-container-' + field)).length == 0 ){
            
	        $('#element-container-' + field).prepend(
	            $('<i class="icon-filter" style="color: black; padding: 0.3em 0.5em 0.3em 0.5em;"/>')
	            .on('click', function(e){
	                e.preventDefault();
	                return false;
	            })
	            .attr('data-rel', 'popover')
	            .attr('data-placement', 'bottom')
	            .attr('data-title', 'Adicionar Filtro')
	            .attr('data-content', "<center>"+fields[field].join("<br>")+"</center>")
	            .attr('data-container', 'body')
	            .popover({html: true})
	            .on('show.bs.popover', function(){
	                $('[data-rel=popover]').popover('hide');
	            })
	            .on('hidden.bs.popover', function(){
	                $('.popover').css('display', 'none');
	            })
	            .on('shown.bs.popover', function(){
	                $('span', '.popover').css({'margin-top': '4px', 'cursor': 'pointer'});
	                $('span', '.popover').on('click', function(){
	                    $('[data-rel=popover]').popover('hide');
	                    Fenix_Model.grids.last().grid('filtersAdd', $(this).attr('data-field'), 'eq', $(this).attr('data-value'));
	                });
	            })
	        );
            
        }
        
    };
    
}

Robin_Misc.popoverHide = function(){
    $('body').off('click.popoverhide');
    $('body').on('click.popoverhide', function (e) {
        if ($(e.target).attr('data-rel') !== 'popover' && $(e.target).parents('.popover.in').length === 0) { 
            $('[data-rel="popover"]').popover('hide');
        }
    });
}

Robin_Misc.filterTitleLoad = function(){
    if(!Robin_Misc.filterTitleCache){
        if(window.sessionStorage["Robin_Misc.filterTitleCache"] && window.sessionStorage["Robin_Misc.filterTitleCache"].length > 0){
            try {
                Robin_Misc.filterTitleCache = JSON.parse(window.sessionStorage["Robin_Misc.filterTitleCache"]);
            } catch(e){
	            Robin_Misc.filterTitleCache = {};
            }
        } else {
            Robin_Misc.filterTitleCache = {};
        }
    }
}

Robin_Misc.filterTitleSave = function(){
    window.sessionStorage["Robin_Misc.filterTitleCache"] = JSON.stringify(Robin_Misc.filterTitleCache);
}

Robin_Misc.filterTitleSet = function(field, value, title){
    Robin_Misc.filterTitleLoad();
    
    if(!Robin_Misc.filterTitleCache[field]){
        Robin_Misc.filterTitleCache[field] = {};
    }
    
    Robin_Misc.filterTitleCache[field][value] = title;
}

Robin_Misc.filterTitleGet = function(field, value){
    
    Robin_Misc.filterTitleLoad();
    
    var ret = value;
    
    if(Robin_Misc.filterTitleCache[field] && Robin_Misc.filterTitleCache[field][value]){
        ret = Robin_Misc.filterTitleCache[field][value];
    }
    
    Robin_Misc.filterTitleSave();
    
    return ret;
}

Robin_Misc.novoRegistroFocus = function(){
	$('.btn:contains(Novo)').focus();
	$('.btn:contains(Nova)').focus();
}

Robin_Misc.changePaginator = function(){
	$('li', Fenix_Model.grids).each(
		function(k,v){
			$(v).html($(v).html().replace(/anterior|primeira|próxima|última/, ''));
		}
	);
}

Robin_Misc.urlAbsoluta = function(url){
    
    if(url.match(/https?:\/\//) === null){
        url = "http://" + url;
    }
    
    return url;
}









