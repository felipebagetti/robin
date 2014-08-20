//@include 'app/fenix/view/js/jquery.js'

/**
 * Define a classe console para evitar problemas em alguns browsers
 */
if (typeof console == "undefined") {
    window.console = {
        log: function () {}
    };
}

/**
 * Uma coleção de métodos simples para facilitar o uso de alguma funções do Bootstrap e jQuery
 * @type 
 */
Fenix = {};


// Placeholder para o evento atual
Fenix.event = null;

/**
 * Salva informações no localStorage
 * 
 * @param {} key
 * @param {} value Se não definido somente obtém o valor atualmente salvo
 * @return value
 */
Fenix.localStorage = function(key, value){
    if(typeof(Storage) !== "undefined"){
        if(typeof value != "undefined"){
            localStorage[key] = value;
        }
        return localStorage[key];
    }
}

/**
 * Salva informações no sessionStorage
 * 
 * @param {} key
 * @param {} value Se não definido somente obtém o valor atualmente salvo
 * @return value
 */
Fenix.sessionStorage = function(key, value){
    if(typeof(Storage) !== "undefined"){
        if(typeof value != "undefined"){
            sessionStorage[key] = value;
        }
        return sessionStorage[key];
    }
}

/**
 * Define o foco para um elemento
 * 
 * @param {} el Id ou objeto jquery do elemento
 * @return {Boolean} true se a seleção funcionar, false caso contrário
 */
Fenix.focus = function(el){
    if(typeof el == "string"){
        el = $('#'+el);
    }
    if( el.data('select2') ){
        window.setTimeout(function(){ el.select2('focus') }, 100);
        return true;
    }
    if( el.is(":visible") ){
        window.setTimeout(function(){ el.focus() }, 100);
        return true;
    } 
    return false;
}

// Extende os métodos hide e show do jQuery para realizar ajustes nas ações de ocultar/mostrar campo
!(function($){
    
	var _oldhide = $.fn.hide;
	$.fn.hide = function(speed, callback) {
	    var e = $(this);
        if(this[0] && this[0].tagName && (this[0].tagName == 'SELECT' || this[0].tagName == 'INPUT')){
            e.select2('container').css('display', 'none');
        }
        // Inputs com o append do bootstrap (como o campo de data)
        if(e.parent().hasClass('input-group')){
            e.parent().hide();
        }
        e.trigger('fenix-hide');
	    return _oldhide.apply(this,arguments);
	}
    
	var _oldshow = $.fn.show;
	$.fn.show = function(speed, callback) {
	    var e = $(this);
        if(this[0] && this[0].tagName && (this[0].tagName == 'SELECT' || this[0].tagName == 'INPUT')){
            e.select2('container').css('display', '');
        }
        // Inputs com o append do bootstrap (como o campo de data)
        if(e.parent().hasClass('input-group')){
            e.parent().show();
        }
        e.trigger('fenix-show');
	    return _oldshow.apply(this,arguments);
	}
    
	var _oldfocus = $.fn.focus;
	$.fn.focus = function(fn) {
        if($.type(fn) != "function"){
	        var e = $(this);
	        if(this[0] && this[0].tagName && (this[0].tagName == 'SELECT' || this[0].tagName == 'INPUT') && e.select2 && e.select2('container').length > 0){
	            this.select2('focus');
	        }
        }
        return _oldfocus.apply(this,arguments);
	}
    
})(jQuery);

/**
 * Move o foco do cursor para o próximo campo a ser preenchido no formulário
 * 
 * @param {} form
 * @param {} field
 */
Fenix.nextField = function(form, field){
    
    // Garante que há o form e o field disponíveis
    // para não ter problemas caso se esteja num
    // componente criado em javasript reutilizando
    // os formatters padrão do jquery.form
    if(!form || !form.options || !form.options.fields || !field){
        return;
    }
    
    var fields = form.options.fields;
    
    // Procura o próximo campo do formulário que não tem valor definido
    var passou = false;
    for(var i = 0; i < fields.length; i++){
        var f = fields[i];
        
        if(f.name == field.name){
            passou = true;
            continue;
        }
        
        if(passou == true){
            
            var el = $("#" + f.name, form._container);
            
            var val = el.val();
            
            el = el.data('select2') ? el.data('select2').container : el; 
            
            if(!val && el.is(':visible') && Fenix.focus( el ) == true){
                return;
            }
        }
    }
    
    var btn = false;
    // Move o foco para o botão de Salvar/Confirmar do formulário
    $('button').each(function(){
        var html = this.innerHTML.toString().toLowerCase();
        if(html.indexOf('salvar') != -1 || html.indexOf('confirmar') != -1){
            var that = this;
            window.setTimeout(function(){ that.focus(); }, 100);
            return;
        }
    });
}

/**
 * Inicializa o component de calendário em um input qualquer
 * 
 * @param input
 */
Fenix.datePicker = function(input){
    input.DatePicker({
        locale:Fenix._internals.datePickerLocale,
        format:'d/m/Y',
        date: input.val() || (new Date()).format('dd/mm/yyyy'),
        current: input.val() || (new Date()).format('dd/mm/yyyy'),
        extraWidth: 100,
        starts: 0,
        position: 'r',
        onRender: function(){
            window.clearTimeout( input.data('datepickerTimeout') );
            return {};
        },
        onBeforeShow: function(){
            $('.datepickerContainer').maxZIndex();
            var val = input.val();
            if(val.replace(/([^0-9]+)/g, '').length != 8){
                val = false;
            }
            input.DatePickerSetDate(val || (new Date()).format('dd/mm/yyyy'), true);
        },
        onChange: function(formated, dates){
            input.val(formated);
            input.trigger('change');
            input.DatePickerHide();
        }
    });
    input.bind('blur', function(){
        var that = this;
        $(that).data('datepickerTimeout', window.setTimeout(function(){ $(that).DatePickerHide(); }, 100));    
    });
}

/**
 * Inicializa o component de calendário em um input qualquer
 * 
 * @param input
 */
Fenix.timePicker = function(input){
    input.timePicker({
        startTime: '00:00',
        endTime: '23:00',
        step: 60
    });
}

/**
 * Inicializa o component de select2 num select qualquer
 * 
 * @param select
 */ 
Fenix.select2 = function(select, form, field){
    
    // Remove o select que foi criado por padrão no formatter e cria um novo
    // elemento oculto para guardar o valor a ser enviado na requisição, isso
    // é feito pois o select2 só permite o uso do método query quando criado
    // a partir de input[type="hidden"]
    var selectToHidden = function(select, field){
        
        var td = $(select.get(0).parentNode);
        
        select.remove();
        
        select = $('<input type="hidden" id="'+field.name+'" name="'+field.nameDatabase+'" value="" />')
                    .css('width', select.css('width'))
                    .data('field', field);
        
        td.append( select );
        
        // Inicia o elemento select (feito aqui pois é necessário colocar o evento select2-close depois
        select.select2(defaults);
        
        // Faz com que o título fique vazio inicialmente
        $(".select2-chosen", select.select2('container')).text('');
        
        return select;
    }
    
    // Objeto de configurações
    var defaults = { "field": field };
    
    // Desabilita a busca do select2 no caso de dispositivos móveis
    if( "ontouchend" in document ){
	    defaults.minimumResultsForSearch = -1;
    }
    
    if(field && field.remote) {
        
        var optionSelected = $("option:selected", select);
        
        // A caixa de pesquisa é sempre habilitada
        defaults.minimumResultsForSearch = 0;
        
        // Só faz a pesquisa com 3 caracteres
        defaults.minimumInputLength = 3;
        
        defaults.query = function (query) {
            
            window.clearTimeout(defaults._queryTimeout);
            
            defaults._queryTimeout = window.setTimeout(function(){
	            $.getJSON(
	                    Fenix_Model.createUrl("fk-search", form.options, ["field="+field.name,"q="+query.term]),
	                    function(data){
	                        query.callback( {results: data} );
	                    });
            }, 200);
            
        };
        
        defaults.initSelection = function(element, callback) {
            if (optionSelected.length > 0) {
                callback({"id": optionSelected.val(), "text": optionSelected.text()});
            }
        }
        
        select = selectToHidden(select, field);
        
    } else if(field && field.insert){
        
        // Faz com que ao sair do foco o item selecionado seja ativado no select2
        defaults.selectOnBlur = true;
        
        // Formatação do resultado da query (ver método defaults.query) 
        defaults.formatResult = function (val) {
            var text = val.text ? val.text.replace(" (adicionar novo)", " ( <i class='glyphicon glyphicon-plus'> adicionar novo)") : '';
            return text;
        };

        // Formatação do valor da ser mostrado no combo quando o usuário seleciona
        defaults.formatSelection = function (val) {
            var text = val.text ? val.text.replace(" (adicionar novo)", "") : '';
            return text;
        };
        
        // Cria um método query do select2 para localizar o elemento sendo digitado
        // e caso seja ele selecionado incluí-lo na lista dos elementos do combo
        defaults.query = function (query) {
            
            var data = {results: []},
                field = query.element.data('field'),
                select = query.element,
                currentValue = select.val(),
                currentValueExists = false;
            
            if(field.data && field.data.length > 0){
                for (var i = 0; i < field.data.length; i++) {
	                var item = field.data[i];
	                
	                if(currentValue == item.id || currentValue == item.text){
	                    currentValueExists = true;
	                }
	                
	                if(query.term.length == 0 || query.matcher(query.term, item.text)){
	                    data.results.push(item);
	                }
	            }
            }
            
            if(query.term.length > 0){
                data.results.push({id: query.term, text: query.term + ' (adicionar novo)'});
            }
            
            if(currentValueExists == false && currentValue.length > 0){
                data.results.push({id: currentValue, text: currentValue});
            }
            
            query.callback(data);
        };
        
        // Cria o método initSelection para permitir que o select2 saiba como
        // referenciar os id com o title dos valores digitados no combo
        defaults.initSelection = function(element, callback){
            var data = null;
            // Localiza o elemento pela chave value
            $(element.data('field').data).each(function(k, item){
                if(item.id == element.val()){
                    data = item;
                }
            });
            // Caso não tenha localizado pelo value localiza pelo title
            if(data == null){
                $(element.data('field').data).each(function(k, item){
                    if(item.text == element.val()){
                        data = item;
                    }
                });
            }
            // Caso nem value nem title tenham sido localizados cria um novo
            // elemento com o value igual ao title
            if(data == null && element.val()){
                data = {id: element.val(), text: element.val()};
            }
            callback(data);
        };
        
        // Transforma o <select /> em <input type="hidden" />
        select = selectToHidden(select, field);
        
        // Adiciona um evento no close do select2 para que caso seja um novo valor selecionado
        // insira um elemento oculto para informar ao backend que um novo registro deve ser inserido
        // e referenciado antes da inclusão/alteração do registro atual. Isso foi feito pois o
        // valor a inserido poderá ser um número também, dificultado a distinção
        select.on('select2-close', function(){
            if(this.value){
                
                var field = $(this).data('field');
                
                var found = false;
                if(field.data && field.data.length > 0){
	                for (var i = 0; i < field.data.length; i++) {
	                    var item = field.data[i];
	                    if(item.id == this.value){
	                        found = true;
	                    }
	                }
                }
                
                var insertFlag = field.name+"__insert";
                
                if(found == false){
                    if($("#"+insertFlag).length == 0){
                        $('<input type="hidden" name="'+insertFlag+'" id="'+insertFlag+'" value="1" />').insertBefore( this );
                    }
                } else {
                    $("#"+insertFlag).remove();
                }
                
            }
        });
        
    } else {
        if($("option", select).length > 0 && $("option", select).length <= 3){
            defaults.minimumResultsForSearch = -1;
        }
        select.select2(defaults);
    }
    
    select.on('select2-close', function(){ if(this.value) Fenix.nextField(form, field) });
    
    select.on('select2-opening', function(){
        var offset = $('#s2'+this.name).offset();
        if(!offset){
            offset = $('#s2id_'+this.id).offset();
        }
        if( offset ){
            var fixedH = 70;
            var offsetTop = offset.top;
            var maxHeight = $(window).height() - offsetTop - fixedH;
            if(offsetTop - (2*fixedH) > maxHeight){
                maxHeight = offsetTop -2*fixedH;
            }
            $('.select2-results').css('max-height', maxHeight+'px');
        } else {
            $('.select2-results').css('max-height', '200px');
        }
    });
    
}

/**
 * Faz o download de um arquivo utilizando um iframe oculto
 * 
 * @param URL
 */
Fenix.download = function(url){
    var iframe = $('<iframe style="width: 0px; height: 0px;" src="">');
    $('body').append(iframe);
    iframe.attr('src', url);
}

Fenix._internals = {
    
    // Desabilita a mensagem padrão de erro ajax
    ajaxErrorDisable: false,
    
    // Timeout de exibição do layer de carregamento 
    loadingLayerTimeout: null,
    
    // Armazenamento dos layers em carregamento
    loadingLayer: [],
    
    /**
     * Localização do datepicker com nomes em pt_BR
     * @type String{}[]
     */
    datePickerLocale: {
        days: [ 'domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo' ],
        daysShort: [ 'dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'],
        daysMin: [ 'D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D' ],
        months: [ 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro' ],
        monthsShort: [ 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez' ],
        weekMin: 'wk'
    },
    
    /**
     * Método que serve para avaliar se um atributo do objeto JS é uma função.
     * É util no recebimento de ponteiros de funções via JSON 
     * 
     * @param {} element
     * @param String functionName
     * @param boolean defaultFunction Define o functionName com uma função anônima vazia 
     * @return {Boolean} True caso seja uma função
     */
    evalFunction: function(element, functionName, defaultFunction){
        
        var fn = $.type(element[functionName]) === 'function';
        
        if($.type(element[functionName]) === 'string' && element[functionName].length > 0){
            try {
                eval( "var fn = " + element[functionName] );
            } catch(e){}                
            if( $.isFunction( fn ) ){
                element[functionName] = fn;
                return true;
            } else {
                fn = false;
                console.log(element[functionName] + ' is not a function.');
            }
        }
        
        if(fn == false){
            
            if(typeof defaultFunction != "undefined" && defaultFunction == true ){
                element[functionName] = fn = function(){};
            } else {
                delete element[functionName];
            }
            
        }
        
        return fn;
    },
    
    /**
     * Método adicionado ao onload da página automaticamente quando o sisteme encontra erros de PHP
     * 
     * @param {} list
     */
    serverCheckErrorShow: function(list){
        try {
            var errors = [];
            for(var i = 0; i < list.length; i++){
                var msg = 'Fenix Server Error: ' + list[i];
                errors.push(msg);
                console.log(msg);
            }
            Fenix.alert('<span style="color: red; font-weight: bold;">'+errors.join("<br>")+'</span>', 'Erros do PHP', {width: 900});
        } catch(e){
            try {
                console.log(e);
            } catch(e){
                
            }
        }
    },
    
    /**
     * Método que verifica erros de PHP num ajax
     * 
     * @param {} list
     */
    serverCheckError: function(event, xhr, options){
        var error = xhr.getResponseHeader('Fenix-Error');
        if(error){
            Fenix._internals.serverCheckErrorShow(error.split(', E_').join(',<br>E_').split(',<br>'));
        }
    },
    
    /**
     * Método adicionado ao onload da página automaticamente quando o sisteme encontra erros de PHP
     * 
     * @param {} list
     */
    serverCheckLogShow: function(list){
        try {
            var errors = [];
            for(var i = 0; i < list.length; i++){
                var msg = 'Log: ' + list[i];
                errors.push(msg);
                console.log(msg);
            }
            Fenix.alert('<span style="color: green; font-weight: bold;">'+errors.join("<br>")+'</span>', 'Log do Sistema', {width: 900});
        } catch(e){
            try {
                console.log(e);
            } catch(e){
                
            }
        }
    },
    
    /**
     * Método que verifica erros de PHP num ajax
     * 
     * @param {} list
     */
    serverCheckLog: function(event, xhr, options){
        var log = xhr.getResponseHeader('Fenix-Log');
        if(log){
            console.log(log);
            Fenix._internals.serverCheckLogShow(log.split(','));
        }
    },

    /**
     * Faz o parser do conteúdo JSON ou semelhante ao 
     * 
     * @param {} field Lista de atributos do sistema
     * @return {} data em formato de objeto
     */
    parseFieldSelectData: function(field){
        
        var data = null;
        
        try {
            if($.type(field.data) == "object" || $.type(field.data) == "array"){
                data = field.data;
            } else {
                data = $.parseJSON(field.data);
            }
        } catch(e){
            try {
                data = field.data;
                data = '"' + data.split(",").join('","') + '"';
                data = "{" + data.split(":").join('":"') + "}";
                data = $.parseJSON(data);
            } catch(e){
                try {
                    data = {};
                    $.each( $.parseJSON( '["' + field.data.split(",").join('","') + '"]' ), function(k, v){
                        data[v] = v;    
                    });
                } catch(e){
	                if(field.data && field.data.length > 0){
		                console.log(field.name + ' = "' + field.data + '" data is not a JSON string');
	                }
	                data = null;
                }
            }
        }
        
        return data;
    },

    /**
     * Button html default formatter
     */
    buttonHtml: function(button, record, column, grid, table, tr, td){
        var icon = ''
        
        if(typeof button.icon != "undefined"){
            icon = '<i class="'+button.icon+'"></i>&nbsp;';
        }
        
        var className = button.className ? ' '+button.className : '';
        
        var html = '<button type="button" class="btn '+className+'">'+icon+button.title+'</button>';
        
        return $(html);
    },
    
    /**
     * 
     * 
     * @param {} buttons
     * @param {} container
     */
    createButtonsPage: function(buttons, container, page){
        
        if(buttons.length){
            
            for(var i = 0; i < buttons.length; i++){
                
                var btn = Fenix._internals.buttonHtml(buttons[i]);
                
                buttons[i].action = buttons[i].action.replace('__PAGE__', " page ");

                eval("var fn = function(event){ event.stopPropagation(); Fenix.event = event; " + buttons[i].action + "}")
                btn.click(fn);
                
                container.append(btn);
            }
            
        }
        
    },
    
    ajaxError: function(event, xhr, settings, error){
        
        $('*').css("cursor", "")
        
        // Não mostra a mensagem de erro caso ela seja desabilitada
        if(Fenix._internals.ajaxErrorDisable == true){
            return;
        }
        
        Fenix.closeLoading();
        
        var options = {};
    
        var dev = xhr.getResponseHeader('Fenix-Dev');
        
        var title = 'Erro do Sistema';
        var msg = '';
        
        if(xhr.status == 412){
            title = 'Validação da Requisição';
        }
        
        console.log(error);
        
        if(dev){
            msg = xhr.responseText;
            options.width = 900 < $(window).width() ? 900 : $(window).width() - 30;
        } else {
            msg = xhr.getResponseHeader('Fenix-Exception');
            if(!msg || msg == 'Internal Server Error'){
                msg = '<center>Ocorreu um erro de sistema no processamento da requisição.<br><br>' +
                      'Por favor, tente novamente.<br><br>' +
                      'Caso o problema persista entre em contato com o suporte responsável pela Aplicação.<center>';
            }
            msg = "<center>" + msg + "</center>";
        }
        
        // Evita que mais de uma mensagem se sobreponnha
        $('.modal[id*="'+title.replace(/([^A-Za-z0-9])/g, '')+'"]').modal('hide');
        
        // Aciona o alerta somente se xhr.readyState <> 0 (erro de conexão)
        if(xhr.readyState == 0){
            Fenix.alertHeaderClose();
            Fenix.alertHeader('Erro de conexão com o servidor. Por favor, tente novamente.', 5000, 'important');
        } else {
        	if($().select2){
	        	$('.select2-container').select2('close');
        	}
            Fenix.alert(msg, title, options);
        }
    },
    
    select2LoadStart: function( select ){
	    
	    // Desativa para evitar interações com o usuário
	    select.select2('enable', false);
	    
	    // Adiciona uma mensagem de carregamento ao combo
	    var span = $('.select2-chosen:first', $('#s2'+select.get(0).name));
	    span.data('interval', window.setInterval(function(){
	            var count = span.data('intervalCount') | 0;
	            if(count == 4){
	                count = 0;
	            }
	            span.data('intervalCount', ++count);
	            span.html('carregando dados' + ''.lpad('.', count) );
	        }, 200 )
	    );
	    
	    select.data('select2CurrentValue', select.val());
	},
    
    select2LoadFinish: function( select, data ){
	    
	    // Remove todos os options do combo
	    $('option', select).remove();
	    
	    // Adiciona novos options com os dados carregados
	    select.append( $('<option>', { value : '' }).html('&nbsp;') );
	    
	    for(var i = 0; i < data.length; i++){
	        select.append( $('<option>', { value : data[i].id }).text( data[i].text ) );
	    }
	    
	    // Retira a mensagen de carregamento
	    var span = $('.select2-chosen:first', $('#s2'+select.get(0).name));
	    span.html('');
	    window.clearInterval(span.data('interval'));
	    
	    // Reativa o componente
	    select.select2('enable', true);
	    
	    // Caso haja um valor anterior selecionado tenta preenchê-lo depois de carregados os dados
	    // isso tem que ser feito pois na alteração de registro não necessariamente todos os dados
	    // estarão recarregados ao usuário tentar abrir o select2
	    if( select.data('select2CurrentValue') && select.data('select2CurrentValue') != select.val() ){
	        select.select2('val', select.data('select2CurrentValue') );
	    }
	    
	},
    
    fkDependency: function(e, form, field){
        
        var target = $("#"+field.name, form.element);
        var origin = $("#"+field.depends, form.element);
        
        if( origin.val() && (e.type == 'change' || (e.type == "select2-opening" && !target.data('select2Loaded')))){
            
            // Impede que o select2 seja aberto, no caso do evento select2-opening
            e.preventDefault();
    
            // Parametros do carregamento de Fk Dependency
            var params = $.type(form.options.fkDependencyParams) == 'string' ? form.options.fkDependencyParams.split("&") : [];
            params.push("field="+field.name);
            params.push("value="+origin.val());
            
            // Url da requisição
            var url = Fenix_Model.createUrl("fk-dependency", form.options, params);

            // Callback a ser executado depois do carregamento
            var callback = function(){
                // Fecha qualquer outro select que esteja aberto
                $('select').select2('close');
                
                // Abre o select2 para o usuário selecionar o valor
                target.select2('open');
            };
            
            // Chamada padrão de recarregar um select2
            Fenix.select2Reload(target, url, callback);
	    }
    },
    
    fkDependencyFilter: function(e, page, field){
        
        var target = $("#"+field.name, form.element);
        var value = JSON.stringify(page.filtersGet(field.depends));
    
        if(value != "{}"){
            // Parametros do carregamento de Fk Dependency
	        var params = $.type(page.options.fkDependencyParams) == 'string' ? page.options.fkDependencyParams.split("&") : [];
	        params.push("field="+field.name);
	        params.push("value="+value);
	        
	        // Url da requisição
	        var url = Fenix_Model.createUrl("fk-dependency", page.options, params);
	
	        // Callback a ser executado depois do carregamento
	        var callback = function(){};
	        if(e.type == "select2-opening"){
	            callback = function(){
	                // Fecha qualquer outro select que esteja aberto
	                $('select').select2('close');
	                
	                // Abre o select2 para o usuário selecionar o valor
	                target.select2('open');
	            };
	        }
	        
	        // Chamada padrão de recarregar um select2
	        Fenix.select2Reload(target, url, callback);
        }
        
    }
}

/**
 * Faz o carregamento de um elemento select2 a partir dos resultado de uma requisição à URL
 * @param {} element
 * @param {} url
 * @param {} callback
 */
Fenix.select2Reload = function(element, url, callback){
    // Evita múltiplas chamadas ao recarregamento
	if( !element.data('select2Loading') ) {
	    
	    // Define a flag de carregamento em andamento para evitar múltiplas chamadas
	    element.data('select2Loading', true);
	    
	    // Inicia a animação e desabilita o select2
	    Fenix._internals.select2LoadStart( element );
	    
	    // Faz a chamada para carregamento de dados
	    $.getJSON(
	        url,
	        function(data){
	    
	        // Finaliza a animação, carrega os dados e habilita novamente o select2
	        Fenix._internals.select2LoadFinish( element, data );
	        
	        // Define as flags de carregamento para os valores corretos
	        element.data('select2Loaded', true);
	        element.data('select2Loading', false);
            
            // Callback configurado
            if($.type(callback) == "function"){
	            callback();
            }
	    });
	}
}

/**
 * Retira acentos e outros caracteres especiais de uma string e a torna em Lower Case
 * @param String
 * @return String
 */
Fenix.stripAccents = function(string){
    var r = string.toLowerCase();
    r = r.replace(new RegExp(/[àáâãäå]/g),"a");
    r = r.replace(new RegExp(/æ/g),"ae");
    r = r.replace(new RegExp(/ç/g),"c");
    r = r.replace(new RegExp(/[èéêë]/g),"e");
    r = r.replace(new RegExp(/[ìíîï]/g),"i");
    r = r.replace(new RegExp(/ñ/g),"n");                
    r = r.replace(new RegExp(/[òóôõö]/g),"o");
    r = r.replace(new RegExp(/[ùúûü]/g),"u");
    r = r.replace(new RegExp(/[ýÿ]/g),"y");
    return r;
}

Fenix.layer = function(title, body, footer, options){
    
    var options = $.extend({}, Fenix.layer.defaults, (options ? options : {}));
    
    var id = title.replace(/([^A-Za-z0-9])/g, '');
    id = id + '_' + $("#"+id).length; 
    
    var modal = $('<div id="'+id+'" class="modal fade" tabindex="-1" role="dialog" />');
    
    if( String(options.width).match(/px|%/i) == null ){
        options.width = options.width + "px";
    }
    
	modal.append( $("<div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button><h4 class='modal-title'>"+title+"</h4></div>") );
    
    if(body){
        modal.append( $("<div class='modal-body'></div>").append(body) );
    }
    
    if(footer){
        modal.append( $("<div class='modal-footer'></div>").append(footer) );
    }
    
    $('body').append(modal);
    
    modal.modal(options);
    
    modal.on('shown', function(){ $('textarea', modal).trigger('autosize'); });
    
    // Chama o callback definido na chamada do Fenix.layer
    modal.on('hide', options.callback);
    
    // Sempre remove o modal depois de ter sido oculto
    modal.on('hidden', function(modal){
        
        var that = this;
        
        var fn = function(){
            $(that).remove();
            
            var z = parseInt($('.modal-scrollable').last().css('z-index'));
            $('.modal-backdrop').each(function(k, v){
                if( parseInt($(v).css('z-index')) >= z){
                    $(v).remove();
                }
            });
            
            if( $('.modal-backdrop').length == 0 ){
                $('body').removeClass('modal-open').removeClass('page-overflow');
            }
            
            $('.modal-scrollable:empty').remove();
        }
        
        window.setTimeout(fn, 300);
        fn();
    } );
    
    return modal;
}

Fenix.layer.defaults = {
    width: 300,
    backdrop: true,
    keyboard: true,
    show: true,
    height: 'free',
    callback: function(){}
};

/**
 * Fecha modais abertos
 * 
 * @param boolean all Define se todos os modais abertos serão fechados
 * @param function callback Define um callbackp para ser executado depois do fechamento
 */
Fenix.layerClose = function(all, callback){
    // Layers de carregamento só podem ser fechados por seu próprio método Fenix.closeLoading
    var modal = $(".modal").not('[id*=Carregando]');
    if(typeof all == "undefined" || all == false){
        modal = modal.filter(":last");
    }
    if(callback && $.type(callback) == 'function'){
	    modal.on('hidden', callback );
    }
    // Fecha a lista de modal
    modal.modal('hide');
    // Retorna a lista para permitir manipulações
    return modal;
}

/**
 * Faz a exibição de uma mensagem de carregamento padrão e opcionalmente uma mensagem personalizada
 * @param String msg
 */
Fenix.showLoading = function(msg){
    
    // Faz a criação do layer de carregamento em um timeout para só mostrar
    // ao usuário caso realmente o tempo seja relevante:
    // http://www.nngroup.com/articles/response-times-3-important-limits/
    // T1055 - Alerts, Layers e Loading - Melhor funcionamento
    Fenix._internals.loadingLayerTimeout = window.setTimeout(function(){
        
	    if(msg){
	        msg = '<p style="text-align: center;">'+msg+'</p>'; 
	    } else {
            msg = "";
        }
	    
	    var html = '<div><br>' +
	                   '<div style="width: 90px; margin: 0 auto;">' +
	                   '<div id="loading_block_1" class="loading-block"></div>' +
	                   '<div id="loading_block_2" class="loading-block"></div>' +
	                   '<div id="loading_block_3" class="loading-block"></div>' +
	                   '<div id="loading_block_4" class="loading-block"></div>' +
	                   '<div id="loading_block_5" class="loading-block"></div>' +
	                   '</div>' +
	               '<p style="text-align: center;"><br>Aguarde equanto o carregamento é realizado...<br>&nbsp;</p>' + msg +
	               '</div>';
	    
        var modal = Fenix.layer('Carregando...', $(html), null, {width: 350, keyboard: false, backdrop: 'static'});
        
        $('.close', modal).remove();
        
	    Fenix._internals.loadingLayer.push( modal );
        
    }, 300);
    
}

/**
 * Fecha o último layer de carregamento que foi aberto
 */
Fenix.closeLoading = function(){
    window.clearTimeout(Fenix._internals.loadingLayerTimeout);
    if(Fenix._internals.loadingLayer.length > 0){
        Fenix._internals.loadingLayer.pop().modal('hide');
    }
}

/**
 * Mostra uma mensagem popover no campo
 * @param DOMElement|jQuery Element a ser
 * @param String msg Mensagem
 * @param object options no formato esperado pelo plugin popover do bootstrap
 */
Fenix.popover = function(element, msg, options){
    var defaults = {
        'content':'<span style="font-style: italic;"><i class="icon-info-sign" style="margin-top: 0px" />&nbsp;&nbsp;'+msg+'</span>',
        'placement' : 'top',
        'html': true
    };
    
    options = $.extend({}, defaults, options);
	
    $(element).popover(options)
              .popover('show');
    
    if(parseInt(options.timeout || 0) > 0){
		window.setTimeout(function(){ $(element).popover('destroy');  }, parseInt(options.timeout));
    }
}

Fenix.alert = function(msg, title, options){
    
    var defaults = {
        callbackOk: function(){},
        width: 300,
        backdrop: 'static',
        keyboard: true
    };
    
    var options = $.extend({}, defaults, options ? options : {});
    
    if(!title){
        title = "Alerta";
    }
    
    var footer = $('<input type="button" class="btn btn-sm btn-primary" data-dismiss="modal" value="Ok" />');
    
    var modal = Fenix.layer(title, $('<p>'+msg+'</p>'), footer, options);
    
    modal.on('shown', function(){
        $("input[value='Ok']", this).focus();
    });
    
    modal.on('hide', options.callbackOk);
    
    return modal;
}

Fenix.alertHeader = function(msg, timeout, type, container){
    
    if(!type){
        type = 'info';
    }
    
    type = 'label-' + type;
    
    var span = $('<span class="alert-header label '+type+' pull-right">'+msg+'</span>').css({'z-index': 99999});
    
    if(!container || container.length == 0){
        span.appendTo( $('body') ).css({position: 'absolute', top: '60px', right: '25px', 'font-size': '90%'});
    } else {
        span.prependTo( container ).css({"margin-top": '5px'});
    }
    
    if(timeout && timeout > 0){
        window.setTimeout(function(){ span.remove(); }, timeout);
    }
    
    return span;
}

Fenix.alertHeaderClose = function(all){
    $('.alert-header' + (typeof all == 'undefined' || all == false ? ':last' : '')).remove();
}

Fenix.confirm = function(title, msg, callbackYes, classNameYes, callbackNo, classNameNo, options){
    
    if(typeof classNameYes == "undefined"){
        classNameYes = "btn-primary";
    }
    
    if(typeof classNameNo == "undefined"){
        classNameNo = "btn-default";
    }
    
    var defaults = {
        callbackYes: function(){},
        callbackNo: function(){},
        width: 300
    };
    
    options = $.extend({}, defaults, options ? options : {});
    
    if(typeof callbackYes == "function"){
        options.callbackYes = callbackYes;
    }
    
    if(typeof callbackYes == "function"){
        options.callbackNo = callbackNo;
    }
    
    if(!title){
        title = "Alerta";
    }
    
    var btnYes = $('<input type="button" class="btn btn-sm '+classNameYes+'" data-dismiss="modal" value="Sim" />').bind('click', options.callbackYes);
    var btnNo = $('<input type="button" class="btn btn-sm '+classNameNo+'" data-dismiss="modal" aria-hidden="true" value="Não" />').bind('click', options.callbackNo);
    
    var footer = $('<div></div>').append(btnYes).append(btnNo);
    
    var body = $('<p style="text-align: center;"></p>');
    
    if(typeof msg == "object"){
        body.append(msg);
    } else {
        body.html(msg);
    }
    
    return Fenix.layer(title, body, footer, options);
}

$(document).ajaxError( Fenix._internals.ajaxError );

$(document).ajaxSuccess( Fenix._internals.serverCheckError );
$(document).ajaxSuccess( Fenix._internals.serverCheckLog );

// Adiciona um evento de before unload para evitar 
$(window).bind('beforeunload', function() {
        Fenix._internals.ajaxErrorDisable = true; 
    } 
);

// Funções extras para String

String.prototype.lpad = function(padString, length) {
    var str = this;
    while (str.length < length)
        str = padString + str;
    return str;
}
 
String.prototype.rpad = function(padString, length) {
    var str = this;
    while (str.length < length)
        str = str + padString;
    return str;
}
 
String.prototype.stripAccents = function() {
    return Fenix.stripAccents(this);
}

String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g,"");
}

String.prototype.ltrim = function() {
    return this.replace(/^\s+/,"");
}

String.prototype.rtrim = function() {
    return this.replace(/\s+$/,"");
}

String.prototype.formatDate = function() {
    var ret = this;
    // Formato de banco
    if( this.match(/([0-9]{4})-([0-9]{2})-([0-9]{2})/) ){
        ret = this.replace(/([0-9]{4})-([0-9]{2})-([0-9]{2})/, "$3/$2/$1");
    }
    if( this.match(/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/) ){
        ret = this.replace(/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/, "$3-$2-$1");
    }
    return ret.substr(0, 19);
}

String.prototype.htmlEntities = function() {
    return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function number_format (number, decimals, dec_point, thousands_sep) {
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function (n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}

try {
    global.number_format = number_format;
} catch(e){}
