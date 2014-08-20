//@include "/fenix/app/fenix/view/js/Fenix.js"

/*
 *  Project: jQuery Form
 *  Description: Form component that creates a form inside a container
 */

;(function ( $, window, document, undefined ) {

    // Create the defaults once
    var defaults = {
            title: "",
            fields: [],
            fieldsHidden: [],
            buttonsPage: [],
            buttonsPageContainer: null,
            autofocus: true,
            record: {}
        };

    // The actual plugin constructor
    function Form( element, options ) {
        this.element = element;

        // jQuery has an extend method which merges the contents of two or objects
        this.options = $.extend( {}, defaults, options );
        
        // Updates the element container and ID
        this.options.container = "#" + this.element.id;
        this.options.id = this.options.container;
        
        // Save the default for possible future access
        this._defaults = defaults;
        
        try {
            Fenix_Model.forms = Fenix_Model.forms.add( $(this.element) );
        } catch(e){}
        
        this.init();
    }
    
    Form.prototype = {

        type: 'form',
        _container: null,
        _form: null,
        _table: null,
        submitLock: false,
        
        init: function() {
            
            this.processFields();
            
            Fenix._internals.evalFunction(this.options, "submitCallback", true);
            
            this.create();
        },
        
        // Faz o processamento de cada coluna colocando num formato ótimo de criação de form
        processFields: function(){
            for(var i = 0; i < this.options.fields.length; i++){
                var field = this.options.fields[i];
                
                // Executa os eval de cada um dos formatters se existirem
                if($.type(field.formatter) === 'string' && field.formatter.length > 0){
                    Fenix._internals.evalFunction(field, "formatter");
                }
                
                if(!$.isFunction( this.options.fields[i].formatter )){
                    this.options.fields[i].formatter = $.fn.form.formatter.input;
                }
                
                // Avalia as funcões dos eventos
                if(field.on && $.type(field.on) === 'object'){
                    for(var eventType in field.on){
                        for(var j = 0; j <= field.on[eventType].length; j++){
                            var element = field.on[eventType];
                            Fenix._internals.evalFunction(element, j);
                        }
                    }
                }
            }
        },
        
        create: function(){
            
            this._container = $( this.element );
            
            if( this.options.title.length > 0 ){
                var header = $('<h4></h4>').html( this.options.title.replace(/\\n/g, "<br>") );
                
                this._container.append(header);
            }

            this._form = $('<form autocomplete="off" onsubmit="return false;"></form>');
            
            // https://groups.google.com/a/chromium.org/forum/#!topic/chromium-dev/zhhj7hCip5c
            // T2199 - Formulários - Chrome não está obedecendo mais o autocomplete=off
            if( navigator.userAgent.match(/chrome/gi) !== null ){
	            this._form.append( $('<input type="password" name="___passwordChrome" id="___passwordChrome" style="display: none;">') );
            }
            
            if(this.options.view == true){
                this._form.addClass('view');
            }
            
            this._container.append(this._form);
            
            this.createBody();
            
            if(this.options.buttonsPageContainer == null){
                this.options.buttonsPageContainer = $('<div id="buttons-page">&nbsp;</div>').appendTo(this._container);
            } else if($.type(this.options.buttonsPageContainer) == "string"){
                this.options.buttonsPageContainer = $(this.options.buttonsPageContainer);
                if(!this.options.buttonsPageContainer.length){
                    this.options.buttonsPageContainer = $("#"+this.options.buttonsPageContainer);
                }
            }
            
            Fenix._internals.createButtonsPage(this.options.buttonsPage, this.options.buttonsPageContainer, this);
        },
        
        createBody: function(){
            
            var record = this.options.record;
            
            // T0543 - Form - Opções de criação do formulário são sobrescritas pelo objeto prejudicando o uso futuro
            var fieldsHidden = $.type(this.options.fieldsHidden) == "array" ? this.options.fieldsHidden.slice(0) : [];
            
            // Se há um registro adiciona o id do registro como um campo oculto do formulário
            if(record && record.id && record.id > 0){
                fieldsHidden.push({'name':'id','value':record.id});
            }
                        
            for(var i = 0; i < fieldsHidden.length; i++){
                var f = fieldsHidden[i];
                var hidden = $('<input type="hidden" name="'+f.name+'" id="'+f.name+'" />').val(f.value);
                this._container.append( hidden );
            }
            
            var focus = null;
            
            // Cria um objeto com o agrupamento de linhas do formulário
            var lines = {};
            // Mantém a quantidade máxima de campos numa linha
            var lineFieldsMax = 1;
            for(var i = 0; i < this.options.fields.length; i++){
                var field = this.options.fields[i];
                var key = field.row && field.type != 'tab' ? 'line-' + field.row: 'default-' + i;
                if(typeof lines[key] == "undefined"){
                    lines[key] = [];
                }
                lines[key].push( field );
                
                lineFieldsMax = lines[key].length > lineFieldsMax ? lines[key].length : lineFieldsMax;  
            }
            
            // Cria a primeira tabela do form (pode haver mais caso haja abas)
            this.createBodyNewTable();
            
            // Percorre cada linha do objeto e vai criando os fields
            var firstLine = true;
            
            for(var lineKey in lines){
                
                var lineFields = lines[lineKey].length;
                
                // Cria uma nova linha exceto se o primeiro campo for uma tab
                // e não deve haver situações onde uma tab e outro campo estejam
                // na mesma linha
                if(lines[lineKey][0].type != 'tab'){
	                var row = $(document.createElement('TR'));
	                this._tbody.append(row);
                }
                
                // Cada field que está numa linha
                for(var i = 0; i < lines[lineKey].length; i++){
                    
	                // Coluna sendo trabalhada
	                var field = lines[lineKey][i];
	
                    // O campo do tipo tab serve somente para criação de abas
                    if(field.type == 'tab'){
                        
                        // Cria uma nova tabela para o formulário caso não seja a primeira linha
                        // para poder separar o conteúdo do formulário em abas
                        if(firstLine == false){
	                        this.createBodyNewTable();
                        }
	                        
                        // Obtém o navegador de abas
                        var formTab = $('#formTab', this._container);
                        
                        // Se ainda não existir cria o navegador de abas
                        if(formTab.length == 0){
                            formTab = $('<ul class="nav nav-tabs" id="formTab"></ul>');
                            
                            formTab.insertBefore($('form', this._container));

                            $('table:first', this._container).addClass('active');
                            
                            this._form.addClass('tab-content');
                            
                            // Quando não há uma aba no começo do modelo e há uma definição de aba depois
                            // cria um aba com o conteúdo do formulário até a primeira definição de aba 
                            if(firstLine == false){
                                var li = $('<li class="active"><a href="#principal" data-toggle="tab">Dados Gerais</a></li>');
                                
                                $('a', li).on('click', function(){
                                    window.location.hash = this.href.split("#")[1];
                                });
                                
                                formTab.append(li);
                            }
                            
                        }
                        
                        var li = $('<li><a href="#'+field.name+'" data-toggle="tab">'+field.title+'</a></li>');
                        
                        $('a', li).on('click', function(){
                            window.location.hash = this.href.split("#")[1];
                        });
                        
                        formTab.append(li);
                        
                        if(firstLine == true && !window.location.hash){
                            $('a', li).click();
                        }
                        
                        this._table.get(0).setAttribute('id', field.name);
                        
                        this._table.addClass('tab-pane');
                      
                        // Se há uma aba definida na URL faz com que ela seja mostrada
                        if(window.location.hash){
                            $("a[href='"+window.location.hash+"']").click();
                        }
                        
                        // Adiciona o evento de verificação de mudança do hash da página
                        if(!$.fn.form.onHashChange){
                            $(window).on('hashchange', function() {
                                if(location.hash){
                                    $("a[href='"+window.location.hash+"']").click();
                                }
                            });
                            $.fn.form.onHashChange = true;
                        }
                      
                        // Ignora o restante do procedimento de criação do campo
                        continue;
                    }
	                
	                var hasTitleCell = false;
	                
	                // Cria a célula de título
	                if(field.title && field.title.length > 0){
	                    var cell = $(document.createElement('TD'));
	                    var title = field.title;
	                    if($.type(field.required) != "undefined" && field.required == "1"){
	                        title += " <span style='color: red'>*</span>";
	                    }
	                    cell.append($("<span id='"+field.name+".title'>"+title+"</span>"));
	                    row.append(cell);
	                    
	                    if(this.options.labelColumnWidth){
	                        if(this.options.labelColumnWidth.replace(/([^0-9])/g, '') == this.options.labelColumnWidth){
	                            this.options.labelColumnWidth = this.options.labelColumnWidth+"px";
	                        }
	                        cell.css('width', this.options.labelColumnWidth);
	                    }
	                    
	                    hasTitleCell = true;
	                }
	                
	                // Cria a célula de conteúdo
	                var cell = $(document.createElement('TD'));
	                
	                if(!hasTitleCell){
	                    cell.attr('colspan', '2');
	                }
                    
                    // Caso a linha atual possua menos campos que o máximo do formulário
                    // e seja o último campo da linha faz com que o colspan se adapte a
                    // preencher o restantes das colunas
                    if(lineFields < lineFieldsMax && i == lines[lineKey].length -1){
                        var colspanCurrent = parseInt(cell.attr('colspan') ? cell.attr('colspan') : 1, 10);
                        var colspanOther = ((2*lineFieldsMax)-i);
                        cell.attr('colspan', colspanCurrent + colspanOther );
                    }
	                
	                row.append(cell);
	                
	                // Texto a ser colocado na form
	                var text = typeof record[field.name] != "undefined" ? record[field.name] : '';
	                
	                // Faz o processamento do formatter do campo
	                try {
	                    field.formatter(text, record, field, this, this._table, row, cell);
	                } catch(e){
	                    console.log('Erro ao executar formatter '+field.formatter + ' = '+ e);
	                }
	                
	                if(this.options.view){
	                    cell.css('font-weight', 'bold');
	                }
	                
	                var input = $("[name=" + field.nameDatabase +"]:last", this._container);
	                
	                // Marcação de obrigatoriedade do campo
	                if($.type(field.required) != "undefined" && field.required == "1"){
	                    input.addClass('required');
	                    cell.append($('<div id="'+field.name+'-error-container" />'));
	                    input.attr('parsley-error-container', '#'+field.name+'-error-container');
	                }
	                
	                // Definição dos eventos do campo
	                if(field.on && $.type(field.on) === 'object'){
	                    for(var eventType in field.on){
	                        for(var j = 0; j <= field.on[eventType].length; j++){
	                            input.on(eventType, field.on[eventType][j]);
	                        }
	                    }
	                }
	                
	                // Definição do valor do campo caso o record possua este field
	                if(record && record[field.name] && !input.val()){
	                    
	                    input.valChange( record[field.name] );
	                    
	                    // Se for um elemento do tipo select e não tiver conseguido definir o valor
	                    // significa que o elemento não está na lista de possíveis valores e por isso
	                    // cria uma nova option para definir o valor e evitar que numa alteração o
	                    // valor do campo seja perdido
	                    if(input.val() != record[field.name]){
	                        if(input.get(0) && input.get(0).tagName == 'SELECT'){
	                            var value, title;
                                value = title = record[field.name];
                                if(field.type == "fk"){
	                                value = record[field.key + "_" + field.name]; 
	                            }
	                            var option = $('<option value="'+value+'">'+title+'</option>');
	                            input.append(option);
                                input.val(value).trigger('change');
	                        }
	                    }
	                }
	                
	                // Define o foco para o primeiro elemento do formulário não preenchido caso a opção
	                // autofocus esteja ativa (por padrão é ativada)
	                if(this.options.autofocus == true && focus == null && !record[field.nameDatabase]){
	                    focus = input;
	                }
	           }
               
               // Finalização da primeira linha
               firstLine = false;
           }
           
           // Define o foco para o elemento do autofocus
           if(this.options.autofocus == true){
               if(focus != null){
                    _that = this;
                    window.setTimeout(function(){
                       _that.focus( focus );
                    }, 400);
               } else if(focus == null){
                    $(".btn-primary", $("#buttons-page")).focus();
               }
           }
           
        },
        
        createBodyNewTable: function(){
            this._table = $('<table></table>').addClass('table table-condensed table-striped tab-pane');
                        
            this._table.get(0).setAttribute('id', 'principal');
    
            this._tbody = $('<tbody></tbody>');
            
            this._table.append( this._tbody );
            
            this._form.append( this._table );  
        },
        
        getRecord: function(){
            return this.options.record;
        },
        
        /**
         * Move o foco para um elemento do formulário caso seja possível
         */
        focus: function(element){
            
            var isVisible = function(el){
                el = $(el);
                if(el.data("select2")){
                    el = el.data("select2").container
                }
                return el.is(":visible");
            }
            
            // Determina se o elemento está numa aba não visível
            if( isVisible(element) == false ){
                
                $.each($('.nav a[href^=#]', Fenix_Model.forms.last()), function(){
                    
                    // Se uma das abas contém a tabela que contém o elemento faz com que a aba seja clicada
                    if( $('table[id='+this.href.split("#")[1]+']').has( element ).length > 0 ){
                        
                        $(this).click();
                        
                    }
                    
                });
                
            }
            
            // Se o elemento agora for visível chama o focus()
            if( isVisible(element) ){
                
                 if(element.get(0) && element.get(0).tagName == 'SELECT'){
                    element.select2('focus');
                } else {
                    element.focus();
                }
                
                return true;
            }
            
            return false;
        },
        
        validate: function(){
            
            var ret = false;
            
            var evt = $.Event("form-validating"); 
            this._container.trigger( evt );
            
            if( !evt.isDefaultPrevented() ){
                
	            ret = true;
	            var focus = false;
	            
	            for(var i = 0; i < this.options.fields.length; i++){
	
	                // Coluna sendo trabalhada
	                var field = this.options.fields[i];
	                
	                var fieldObj = $( '[name=' + field.nameDatabase +']:last', this._container );
	                
	                if(field.type == 'file'){
	                    fieldObj = $( '#' + field.name+'__file', this._container );
	                }
	                
	                // Campo não localizado marcado com form = 'e' é por causa de um possível bug
	                // alerta o desenvolvedor nesse caso
	                if(fieldObj.length == 0 && field.form == 'e' && field.type != 'separator' && field.type != 'tab') {
	                	console.log('Field não localizado: '+ JSON.stringify(field));
	                }
	                
	                if(fieldObj.length > 0){
	                    var status = fieldObj.parsley( 'validate' );
	                    
	                    ret = (status === null || status === true ? true : false) && ret;
	                    
	                    if(ret == false && focus == false){
	                        // Move o foco para o elemento obrigatório se possível
	                        focus = this.focus(fieldObj);
	                    }
	                }
	            }
	            
	            if(ret === false){
	                Fenix.alertHeaderClose();
	                Fenix.alertHeader('Os campos em vermelho são obrigatórios!', 6000, 'danger');
	            }
                
                var uploadReady = true;
                
                $('input[type=file]', this._container ).each(function(k, v){
                    if($(v).upload){
                        uploadReady = uploadReady && $(v).upload('isReady');
                    }
                });
                
                if(uploadReady == false){
                    Fenix.alertHeaderClose();
                    Fenix.alertHeader('Upload em andamento, aguarde a conclusão!', 6000, 'warning');
                    ret = false;
                }
                
            }
            
            return ret;
        },
        
        serialize: function(){
            var params = $('*', this._container).serialize();
            
            $.each(this.options.fields, function(k, field){
                if(field.type == 'richtext'){
                    params += "&"+field.name+"="+encodeURIComponent($('#'+field.name).cleanHtml());
                }
            });
            
            return params;
        },
        
        serializeObject: function(){
            var ret = {};
            
            var array = $('*', this._container).serializeArray();
            
            $.each(this.options.fields, function(k, field){
                if(field.type == 'richtext'){
                    array.push(  {"name":field.name, "value":encodeURIComponent($('#'+field.name).cleanHtml()) } );
                }
            });
            
            $.each(array,
		        function(i, v) {
		            ret[v.name] = v.value;
		    });
            
            return ret;
        },
        
        submit: function(){
            
            if(this.submitLock === false){
                
	            var form = this;
                
                form.submitLock = true;
                
	            Fenix.showLoading();
	            
	            var callback = form.options.submitCallback;
	            
	            var success = function(data, textStatus, xhr){
	                
                    form.submitLock = false;
                    
	                Fenix.closeLoading();
	                
	                if(xhr.status == 200){
	                    callback(form, data, xhr);
	                } else {
	                    Fenix._internals.ajaxError(null, xhr, null, data);
	                }
	            }
	            
	            $.post( form.options.baseUri + this.options.action , form.serialize() , success )
                        .always(function(){ form.submitLock = false;  });
                
            }
            
        },
        
        requiredAdd: function(name){
            $.each(this.options.fields, function(k, field){
                if(field.name == name){
                    // Define o * no título do campo
                    var title = $("[id='" + field.name + ".title']:last", this._form);
                    
                    $('span[style*=red]:contains(*)', title).remove();
                    title.html( title.html() + " <span style='color:red'>*</span>" );
                    
                    // Define as configurações do parsley e reinicializa o componente
                    var input = $("[name=" + field.nameDatabase + "]:last", this._form);
                    
	                if(field.type == 'file'){
	                    input = $( '#' + field.name+'__file', this._form );
	                }
                    
                    // Se não houver o error_container insere-o
                    if( $("#"+field.name+'-error-container', this._form).length == 0 ){
                        input.closest('td').append($('<div id="'+field.name+'-error-container" />'));
	                    input.attr('parsley-error-container', '#'+field.name+'-error-container');
                    }
                    
                    input.parsley('destroy');
                    input.attr('data-required', 'true');
	                input.addClass('required');
                    input.parsley().isRequired = true;
	                input.parsley().options.required = true;
                    
                    // Caso já tenha sido validado o formulário pelo parsley ativa a validação
                    // para que o elemento fique 'vermelho' ou 'verde' como os outros do formulário
                    if( $('.parsley-success').length || $('.parsley-error').length ){
                        input.parsley('validate');
                    }
                }
            });
        },
        
        requiredRemove: function(name){
            $.each(this.options.fields, function(k, field){
                if(field.name == name){
                    
                    // Define as configurações do parsley e reinicializa o componente
                    var input = $("[name=" + field.nameDatabase + "]:last", this._form);
                    
                    if(field.type == 'file'){
                        input = $( '#' + field.name+'__file', this._form );
                    }
                    
                    input.parsley('destroy');
	                input.removeAttr('data-required');
	                input.removeClass('required parsley-validated parsley-error');
	                input.parsley().isRequired = false;
	                input.parsley().options.required = false;
                    
                    // Remove o * do título do campo
                    var title = $("[id='" + field.name + ".title']:last", this._form);
                    
                    $('span[style*=red]:contains(*)', title).remove();
                }
            });
        }
    };
        
    var methods = {
        
        getRecord: function(){
            var ret = null;
            this.each(function(){
                ret = $.data(this, "form").getRecord();
            });
            return ret;
        },
               
        getObject: function(){
            var ret = null;
            
            this.each(function(){
                ret = $.data(this, "form");
            });
            
            return ret;
        },
        
        getOptions: function(){
            var ret = null;
            
            this.each(function(){
                ret = $.data(this, "form").options;
            });
            
            return ret;
        },
        
        validate: function(){
            var ret = false; 
            this.each(function(){
                ret = $.data(this, "form").validate();
            });
            return ret;
        },
        
        serialize: function(){
            var ret = ""; 
            this.each(function(){
                ret = $.data(this, "form").serialize();
            });
            return ret;
        },
        
        serializeObject: function(){
            var ret = ""; 
            this.each(function(){
                ret = $.data(this, "form").serializeObject();
            });
            return ret;
        },
        
        submit: function(){
            return this.each(function(){
                var form = $.data(this, "form");
                if(form.validate() == true){
                    form.submit();
                }
            });
        },
        
        getField: function(name){
            var ret = null;
            
            this.each(function(){
                var fields = $.data(this, "form").options.fields;
                
                for(var i = 0; i < fields.length; i++){
                    if(fields[i].name == name || fields[i].nameDatabase == name){
                        ret = fields[i];
                    }
                }
            });
            
            return ret;
        },
           
        requiredAdd: function(name){
            return this.each(function(){
                $.data(this, "form").requiredAdd(name);
            });
        },
           
        requiredRemove: function(name){
            return this.each(function(){
                $.data(this, "form").requiredRemove(name);
            });
        }
        
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn.form = function ( method ) {
        
        // Method calling logic
        if ( methods[method] ) {
            
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
            
        } else if ( typeof method === 'object' || ! method ) {

            return this.each(function () {
                if (!$.data(this, "form")) {
                    $.data(this, "form", new Form( this, method ));
                }
            });
            
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.form' );
        }   
    };
    
    // Constant definitions, specially formatters
    $.fn.form.formatter = {
        
        /**
         * Auxiliar method to set a element's width based on the width field's attribute
         */
        _cssWidth: function(el, field, width){
            // Definição da largura do campo
            if($.type(width) == "undefined"){
                width = 200;
            }
            if($.type(field.width) != "undefined" && parseInt(field.width) > 0){
                if($.type(field.width) == "string" && field.width.indexOf("em") == -1 && field.width.indexOf("px") == -1 && field.width.indexOf("%") == -1){
                    width = parseInt(field.width) + 'px';
                } else {
                    width = field.width;
                }
            }
                
            el.css('width', width);
        },
        
        /**
         * Default form view formatter
         */
        defaultView: function(text, record, field, form, table, tr, td){
            td.css('font-weight', 'bold');
            td.html(text);
        },
        
        /**
         * Default form input formatter
         */
        input: function(text, record, field, form, table, tr, td){
            
            var input = $('<input type="text" autocomplete="off" id="'+field.name+'" name="'+field.name+'" value="" maxlength="'+field.size+'" autocomplete="off"></input>');
            
            if($.type(field.size) != "undefined" && parseInt(field.size) > 0){
                input.attr('maxlength', field.size);
            }
            
            $.fn.form.formatter._cssWidth(input, field);
            
            return input;
        },
        
        /**
         * Text formatter
         */
        text: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);

            td.append(input);
        },
        
        /**
         * Int formatter
         */
        "int": function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);
            
            var replace = function(){
                var tmp = this.value.replace(/([^0-9]+)/g, '');
                if(this.value != tmp){
	                this.value = tmp;
                }
            };
            
            input.on('keyup', replace);
            input.on('blur', replace);
            
            input.css('text-align', 'right');

            td.append(input);
        },
        
        /**
         * Date formatter
         */
        date: function(text, record, field, form, table, tr, td){
            
            var div = $('<div class="input-group input-group-calendar input-group-sm" ><input class="form-control" type="text"  id="'+field.name+'" name="'+field.name+'"><span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span></div>');

            td.append(div);
            
            var input = $("#" + field.name, div);
            
            input.css({'width':'7.2em', 'cursor': 'default'});
            
            if(record && record[field.name]){
                var value = record[field.name];
                value = value.substr(0, 10).split('-');
                input.val(value.reverse().join("/"));
            }
            
            Fenix.datePicker(input);
            
            $('span', div).css('cursor', 'pointer');
            $('span', div).bind('click', function(){
                input.DatePickerShow();
            });
            
            var validateDate = function(){
                var date = this.val().split("/");
                
                var obj = new Date( parseInt(date[2], 10), parseInt(date[1], 10)-1, parseInt(date[0], 10) );
                
                if(obj.format('dd/mm/yyyy') != this.val()){
                    var that = this;
                    Fenix.alert('A data digitada não é válida.', 'Data Inválida', {callbackOk: function(){ window.setTimeout( function(){ that.val(''); that.focus(); }, 300 ); }});
                }
            }
            
            input.mask("99?/99/9999", {completed: validateDate });
            
            input.on('blur', function(){
                if(this.value.length != 10 && this.value.length > 0){
                    var date = this.value.split("/");
                    
                    // Current values
                    var monthCurrent = (new Date()).getMonth()+1;
                    var yearCurrent = (new Date()).getYear()+1900;
                    
                    // Has day (always)
                    var day = parseInt(date[0], 10);
                    var month = null;
                    var year = null;
                    
                    // Has day and month (maybe)
                    if(typeof date[1] != "undefined" && date[1].length > 0){
                        month = parseInt(date[1], 10);
                    }
                    
                    // Has year also but not complete (ie: 13 instead of 2013)
                    if(typeof date[2] != "undefined" && date[2].length > 0){
                        year = parseInt(date[2], 10);
                        if(date[2].length == 2){
                            if(year > 30){
                                year += 1900;
                            }
                            if(year < 30){
                                year += 2000;
                            }
                        } else {
                            year = null;
                        }
                    }
                    
                    // If there is not month calculates based on the closest to the day given
                    if(month == null){
                        // current month by default
                        month = monthCurrent;
                        // next month
                        var diff = ((new Date()).getDate())-day;
                        if(Math.abs(diff) >= 20){
                            if(diff < 0){
                                month -=1;
                            } else {
                                month +=1;
                            }
                        }
                    }
                    
                    // If there is no year is always the current one
                    if(year == null){
                        year = yearCurrent;
                    }
                    
                    if( !(day >= 1 && day <= 31) || !(month >= 1 && month <= 13) ){
                        var that = $(this);
                        Fenix.alert('A data digitada não é válida.', 'Data Inválida', {callbackOk: function(){ window.setTimeout( function(){ that.val(''); that.focus(); }, 300 ); }});
                        return;
                    }
                    
                    var value = (new Date( year, month-1, day )).format('dd/mm/yyyy');
                    $(this).val( value );
                }
            });
            
            input.on('change', function(){ Fenix.nextField(form, field) });
            input.on('keyup', function(e){ if(e.keyCode == 13) Fenix.nextField(form, field) });
            
            return input;
        },
        
        /**
         * Date formatter
         */
        dateView: function(text, record, field, form, table, tr, td){
            
            td.css("font-weight", 'bold');
            
            if(record && record[field.name]){
                var value = record[field.name];
                value = value.substr(0, 10).split('-');
                td.html( $('<span>'+value.reverse().join("/")+'</span>') ); 
            }
            
        },
        
        /**
         * Time formatter
         */
        timeView: function(text, record, field, form, table, tr, td){
            
            td.css("font-weight", 'bold');
            
            if(record && record[field.name]){
                var value = record[field.name];
                value = value.substr(11, 5);
                td.append( $('<span>'+value+'</span>') ); 
            }
            
        },
        
        /**
         * Datetime formatter
         */
        time: function(text, record, field, form, table, tr, td){
            
            var div = $('<div class="input-group input-group-sm input-group-calendar datetime-inline" ><input class="form-control" type="text" id="'+field.name+'" name="'+field.name+'"></input><span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span></div>');
            
            td.append(div);
            
            var input = $("#" + field.name, div);
            
            input.css({'width':'4.5em', 'cursor': 'default'});
            
            if(record && record[field.name]){
                var value = record[field.name];
                value = value.substr(11, 5);
                input.val(value);
            }
            
            Fenix.timePicker(input);
            
            $('span', div).css('cursor', 'pointer');
            $('span', div).bind('click', function(){
                window.setTimeout(function(){ input.focus() }, 100);
            });
            
            input.on('change', function(){ Fenix.nextField(form, field) });
            
            var validateTime = function(){
                var time = this.val().split(":");
                
                var h = parseInt(time[0]);
                var m = parseInt(time[1]);
                
                if( !(h >= 0 && h <= 23 && m >= 0 && m <= 59) ){
                    var that = this;
                    Fenix.alert('A hora digitada não é válida.', 'Hora Inválida', {callbackOk: function(){ window.setTimeout( function(){ that.val(''); that.focus(); }, 300 ); }});
                }
            }
            
            input.mask("99:99", {completed: validateTime });
            
            return input;
        },
        
        /**
         * Datetime formatter
         */
        datetime: function(text, record, field, form, table, tr, td){
            
        	var div = $('<div class="input-group-calendar" style="float:none;">');
        	td.append(div);
        	
            // Cria um elemento oculto para manter a data e hora completa
            var datetime = $('<input type="hidden" name="'+field.name+'" id="'+field.name+'"/>');
            div.append(datetime);
            
            var recordInternal = record;
            if(record[field.name]){
                recordInternal[field.name+'_date'] = record[field.name];
                recordInternal[field.name+'_time'] = record[field.name];
            }
            
            // Campo de data padrão
            var date = $.fn.form.formatter.date(text, recordInternal, $.extend({}, field, {name: field.name+'_date'}), form, table, tr, div);
            
           // td.append('&nbsp;');
            
            // Campo de hora padrão
            var time = $.fn.form.formatter.time(text, recordInternal, $.extend({}, field, {name: field.name+'_time'}), form, table, tr, div);
            
            var setDatetime = function(){
                var v = '';
                if(date.val()){
                    v = date.val() + (time.val() ? ' ' + time.val() : '');
                }
                datetime.val(v);
                if(datetime.parsley){
	                datetime.parsley('validate');
                }
            }
            
            date.bind('change', setDatetime);
            time.bind('change', setDatetime);
            
            datetime.bind('fenix-hide', function(){ date.hide(); time.hide(); });
            datetime.bind('fenix-show', function(){ date.show(); time.show(); });
            
            datetime.bind('change', function(){
                var v = this.value;
                if(v.trim().length >= 10){
                    date.val(v.trim().substr(0, 10));
	                if(v.trim().length > 11){
	                    date.val(v.trim().substr(11));
                    } else {
                        time.val("");
                    }
                } else {
                    date.val("");
                    time.val("");
                }
            });
        },
        
        /**
         * Datetime formatter
         */
        datetimeView: function(text, record, field, form, table, tr, td){
            
            td.css("font-weight", 'bold');
            
            var recordInternal = record;
            if(record[field.name]){
                recordInternal[field.name+'_date'] = record[field.name];
                recordInternal[field.name+'_time'] = record[field.name];
            }
            
            // Campo de data padrão
            $.fn.form.formatter.dateView(text, recordInternal, $.extend({}, field, {name: field.name+'_date'}), form, table, tr, td);
            
            td.append(' ');
            
            // Campo de hora padrão
            $.fn.form.formatter.timeView(text, recordInternal, $.extend({}, field, {name: field.name+'_time'}), form, table, tr, td);
            
        },
        
        /**
         * Select formatter
         */
        select: function(text, record, field, form, table, tr, td){
            
            var select = $('<select id="'+field.name+'" name="'+field.name+'">');
            
            var option = $('<option value=""></option>');
            select.append(option);
            
            td.append(select);
            
            $.fn.form.formatter._cssWidth(select, field);
            
            var data = Fenix._internals.parseFieldSelectData(field);
            
            if(data != null){
                field.data = data;
                
                for(var title in data){
                    
                    var option = $('<option value="'+data[title]+'">'+title+'</option>');
                    
                    if((record && record[field.name] == data[title]) || (!record[field.name] && data[title] == field["default"]) ){
                        option.attr('selected', true);
                    }
                    
                    select.append(option);
                }
            }
            
            Fenix.select2(select, form, field);
        },
        
        
        /**
         * Select Text formatter
         */
        selectText: function(text, record, field, form, table, tr, td){
            
            // Colocar field.data no mesmo padrão do data do campo FK
            // para que o insert funcione corretamente
            var data = Fenix._internals.parseFieldSelectData(field);
            
            if(data != null){
                var dataNew = [];
                for(var text in data){
                    dataNew.push( {id: data[text], text: text} );
                }
                
                field.data = dataNew;
            }
            
            var select = $.fn.form.formatter.select(text, record, field, form, table, tr, td);
            
        },
        
        /**
         * Select formatter
         */
        selectView: function(text, record, field, form, table, tr, td){
            
            var data = Fenix._internals.parseFieldSelectData(field);
            
            if(data != null){
                for(var title in data){
                    
                    if((record && record[field.name] == data[title]) || (!record[field.name] && data[title] == field["default"]) ){
                        td.html( title );
                    }
                    
                }
            }
            
        },
        
        /**
         * Fk form input formatter
         */
        fk: function(text, record, field, form, table, tr, td){
            
            var select = $('<select id="'+field.name+'" name="'+field.nameDatabase+'">');
            
            var option = $('<option value=""></option>');
            select.append(option);
            
            td.append(select);
            
            // Definição da largura do campo
            $.fn.form.formatter._cssWidth(select, field);
            
            // Faz o preenchimento do combo com os dados recebidos através do atributo data 
            if(field.data != null){
                for(var i = 0; i < field.data.length; i++){
                    var line = field.data[i];
                    
                    var option = document.createElement('OPTION');
                    option.value = line.id ? line.id : (line[field.key] ? line[field.key] : '');
                    option.innerHTML = line.text ? line.text : '';
                    
                    if(!option.innerHTML){
                        console.log("Field '" + field.name + "' data attribute is not valid: " + JSON.stringify(field));
                    }
                    
                    if(record[field.nameDatabase] && record[field.nameDatabase] == line.id){
                        option.selected = true;
                    }
                    
                    $(option).data('record', line);
                    
                    select.get(0).appendChild(option);
                }
            }
            // Caso não haja dados para o campo e tenha um valor definido cria
            // um novo option e define ele como selected
            else {
                
                if(record && record[field.nameDatabase]){
                    
                    var option = document.createElement("OPTION");
                    option.value = record[field.nameDatabase];
                    option.innerHTML = record[field.name];
                    option.selected = true;
                    
                    select.get(0).appendChild(option);
                }
                
            }
            
            Fenix.select2(select, form, field);
            
            // Adiciona os eventos de dependencia de FK
            if(field.depends){
                // Adiciona um evento na abertura do select2 do campo dependente para recarregar os itens.
		        // No caso se uma alteração por padrão o select2 só mostrará um item já que o atributo
		        // data do campo não é carregado junto com o form)
                select.on('select2-opening', function(e){ Fenix._internals.fkDependency(e, form, field); });
                
                // Adiciona o evento ao item da lista de serviços para recarregar a dependencia
	            $("#"+field.depends).on('change', function(e){ Fenix._internals.fkDependency(e, form, field); });
            }
            
        },
        
        
        /**
         * Fk form input formatter
         */
        fkView: function(text, record, field, form, table, tr, td){
            
            // No caso da utilização desse formatter nos filtros faz o carregamento
            // do text correto a ser mostrado a partir do valor sendo exibido no select2
            if(text.toString().match(/^([0-9]+)$/g) !== null){
                form._fkViewCache = form._fkViewCache || {};
                form._fkViewCache[field.name] = form._fkViewCache[field.name] || {};
                // Método primário (usando o próprio text sendo exibido no select2)
                if(!form._fkViewCache[field.name][text]){
	                form._fkViewCache[field.name][text] = $('.select2-chosen', $('#'+field.name).select2('container')).text();
                }
                // Método secundário (usando as informações do atributo data do field)
                for(var i = 0; i < $.makeArray(field.data).length; i++){
                    if(field.data[i].id == text){
                        form._fkViewCache[field.name][text] = field.data[i].text;
                        break;
                    }
                }
                record[field.name] = form._fkViewCache[field.name][text];
            }
            
            if(record && record[field.name]){
                text = record[field.name] ;
            }
            
            $.fn.form.formatter.defaultView(text, record, field, form, table, tr, td);
        },
        
        /**
         * bigtext, textarea - formatter
         */
        bigtext: function(text, record, field, form, table, tr, td){
            
            var textarea = $('<textarea id="'+field.name+'" name="'+field.name+'"></textarea>');
            
            // Definição da largura do campo
            $.fn.form.formatter._cssWidth(textarea, field, "50em");
            
            textarea.css('resize', 'none');
            
            td.append(textarea);
            
            window.setTimeout(function(){
                textarea.autosize({callback: function(){ $('body').modalmanager('layout'); }});
            }, 400);
            
        },
        
        
        /**
         * bigtext, textarea - formatter
         */
        richtext: function(text, record, field, form, table, tr, td){
            
            var toolbar = '';
            toolbar += '<div class="btn-toolbar" data-role="editor-toolbar" data-target="#'+field.name+'">';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn dropdown-toggle" data-toggle="dropdown" title="" data-original-title="Font"><i class="glyphicon glyphicon-font"></i><b class="caret"></b></a>';
            toolbar += '      <ul class="dropdown-menu">';
            toolbar += '      <li><a data-edit="fontName Serif" style="font-family:\'Serif\'">Serif</a></li><li><a data-edit="fontName Sans" style="font-family:\'Sans\'">Sans</a></li><li><a data-edit="fontName Arial" style="font-family:\'Arial\'">Arial</a></li><li><a data-edit="fontName Arial Black" style="font-family:\'Arial Black\'">Arial Black</a></li><li><a data-edit="fontName Courier" style="font-family:\'Courier\'">Courier</a></li><li><a data-edit="fontName Courier New" style="font-family:\'Courier New\'">Courier New</a></li><li><a data-edit="fontName Comic Sans MS" style="font-family:\'Comic Sans MS\'">Comic Sans MS</a></li><li><a data-edit="fontName Helvetica" style="font-family:\'Helvetica\'">Helvetica</a></li><li><a data-edit="fontName Impact" style="font-family:\'Impact\'">Impact</a></li><li><a data-edit="fontName Lucida Grande" style="font-family:\'Lucida Grande\'">Lucida Grande</a></li><li><a data-edit="fontName Lucida Sans" style="font-family:\'Lucida Sans\'">Lucida Sans</a></li><li><a data-edit="fontName Tahoma" style="font-family:\'Tahoma\'">Tahoma</a></li><li><a data-edit="fontName Times" style="font-family:\'Times\'">Times</a></li><li><a data-edit="fontName Times New Roman" style="font-family:\'Times New Roman\'">Times New Roman</a></li><li><a data-edit="fontName Verdana" style="font-family:\'Verdana\'">Verdana</a></li></ul>';
            toolbar += '    </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn dropdown-toggle" data-toggle="dropdown" title="" data-original-title="Font Size"><i class="glyphicon glyphicon-text-height"></i>&nbsp;<b class="caret"></b></a>';
            toolbar += '      <ul class="dropdown-menu">';
            toolbar += '      <li><a data-edit="fontSize 5"><font size="5">Gigante</font></a></li>';
            toolbar += '      <li><a data-edit="fontSize 4"><font size="4">Grande</font></a></li>';
            toolbar += '      <li><a data-edit="fontSize 3"><font size="3">Normal</font></a></li>';
            toolbar += '      <li><a data-edit="fontSize 2"><font size="2">Pequena</font></a></li>';
            toolbar += '      <li><a data-edit="fontSize 1"><font size="1">Minúscula</font></a></li>';
            toolbar += '      </ul>';
            toolbar += '  </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn" data-edit="bold" title="" data-original-title="Bold (Ctrl/Cmd+B)"><i class="glyphicon glyphicon-bold"></i></a>';
            toolbar += '    <a class="btn" data-edit="italic" title="" data-original-title="Italic (Ctrl/Cmd+I)"><i class="glyphicon glyphicon-italic"></i></a>';
            toolbar += '  </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn" data-edit="insertunorderedlist" title="" data-original-title="Bullet list"><i class="glyphicon glyphicon-list"></i></a>';
            toolbar += '    <a class="btn" data-edit="insertorderedlist" title="" data-original-title="Number list"><i class="glyphicon glyphicon-list"></i></a>';
            toolbar += '    <a class="btn" data-edit="outdent" title="" data-original-title="Reduce indent (Shift+Tab)"><i class="glyphicon glyphicon-indent-left"></i></a>';
            toolbar += '    <a class="btn" data-edit="indent" title="" data-original-title="Indent (Tab)"><i class="glyphicon glyphicon-indent-right"></i></a>';
            toolbar += '  </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn btn-info" data-edit="justifyleft" title="" data-original-title="Align Left (Ctrl/Cmd+L)"><i class="glyphicon glyphicon-align-left"></i></a>';
            toolbar += '    <a class="btn" data-edit="justifycenter" title="" data-original-title="Center (Ctrl/Cmd+E)"><i class="glyphicon glyphicon-align-center"></i></a>';
            toolbar += '    <a class="btn" data-edit="justifyright" title="" data-original-title="Align Right (Ctrl/Cmd+R)"><i class="glyphicon glyphicon-align-right"></i></a>';
            toolbar += '    <a class="btn" data-edit="justifyfull" title="" data-original-title="Justify (Ctrl/Cmd+J)"><i class="glyphicon glyphicon-align-justify"></i></a>';
            toolbar += '  </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn" title="" id="pictureBtn" data-original-title="Insert picture (or just drag &amp; drop)"><i class="glyphicon glyphicon-picture"></i></a>';
            toolbar += '    <input type="file" data-role="magic-overlay" data-target="#pictureBtn" data-edit="insertImage" style="opacity: 0; position: absolute; top: 0px; left: 0px; width: 36px; height: 30px;">';
            toolbar += '  </div>';
            toolbar += '  <div class="btn-group">';
            toolbar += '    <a class="btn" data-edit="undo" title="" data-original-title="Undo (Ctrl/Cmd+Z)"><i class="glyphicon glyphicon-step-backward"></i></a>';
            toolbar += '    <a class="btn" data-edit="redo" title="" data-original-title="Redo (Ctrl/Cmd+Y)"><i class="glyphicon glyphicon-repeat"></i></a>';
            toolbar += '  </div>';
            toolbar += '</div>';
            
            td.append(toolbar);
            
            var textarea = $('<div class="textarea" id="'+field.name+'"></div>');
            
            if(record && record[field.name]){
                textarea.html( record[field.name] );
            }
            
            textarea.css('width', '630px');
            textarea.css('height', '250px');
            
            td.append(textarea);
            
            textarea.wysiwyg();
        },
        
        /**
         * bigtext, textarea - formatter
         */
        richtextView: function(text, record, field, form, table, tr, td){
            td.html( "<div style='font-weight: normal'>"+text+"</div>" );
        },
        
        /**
         * bigtext, textarea - formatter
         */
        bigtextView: function(text, record, field, form, table, tr, td){
            td.html( text.split("\n").join("<br>") );
        },
        
        /**
         * Password form input formatter
         */
        password: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);

            input.attr('type', 'password');
            
            td.append(input);
        },
        
        /**
         * Option form input formatter
         */
        option: function(text, record, field, form, table, tr, td){
            
            var label = $('<label class="checkbox"><input type="checkbox" id="'+field.name+'" name="'+field.name+'" value="1" />'+(field.description ? field.description : '')+'</label>');
            
            $('input', label).on('change', function(){
                var unsetName = this.name+'__unset';
                if($(this).prop('checked') == false){
                    label.append( $('<input type="hidden" name="'+unsetName+'" id="'+unsetName+'" value="1" />') )
                } else {
                    $("#"+unsetName).remove();
                }
            });
            
            if(record && record[field.name] && record[field.name] == 1){
                $('input', label).prop('checked', true);
            }
            
            td.append(label);
        },
        
        /**
         * Option form input formatter
         */
        optionView: function(text, record, field, form, table, tr, td){
            
            var label = $('<label class="checkbox"><input type="checkbox" id="'+field.name+'" name="'+field.name+'" disabled="disabled" /">'+(field.description ? field.description : '')+'</label>');
            
            if(record && record[field.name] && record[field.name] == 1){
                $('input', label).prop('checked', true);
            }
            
            td.append(label);
        },
        
        /**
         * Radio form input formatter
         */
        radio: function(text, record, field, form, table, tr, td){
            
            var data = Fenix._internals.parseFieldSelectData(field);
            
            var c = 0;
            for(var title in data){
                var label = $('<label class="radio-inline"><input type="radio" id="'+field.name+'_'+data[title]+'" name="'+field.name+'" value="'+data[title]+'" />'+title+'</label>');
                td.append(label);
            }
            
            if(record && record[field.name]){
                $('input[value="'+record[field.name]+'"]', td).prop('checked', true);
            }
        },
        
        /**
         * Option form input formatter
         */
        radioView: function(text, record, field, form, table, tr, td){
            
            $.fn.form.formatter.radio(text, record, field, form, table, tr, td);
            
            $('input', td).attr('disabled', 'disabled');
        },
        
        /**
         * CPF form input formatter
         */
        cpf: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);
            
            input.css('width', '8em');
            
            var validateCpf = function(){
                
                var validateCpfDo = function(cpf) {
                    var numeros, digitos, soma, i, resultado, digitos_iguais;
                    
                    digitos_iguais = 1;
                    
                    cpf = cpf.replace(/([^0-9]+)/g, '');
                    
                    if (cpf.length < 11)
                        return false;
                    
                    for (i = 0; i < cpf.length - 1; i++)
                        if (cpf.charAt(i) != cpf.charAt(i + 1)) {
                            digitos_iguais = 0;
                            break;
                        }
                    if (!digitos_iguais) {
                        numeros = cpf.substring(0, 9);
                        digitos = cpf.substring(9);
                        soma = 0;
                        for (i = 10; i > 1; i--) {
                            soma += numeros.charAt(10 - i) * i;
                        }
                
                        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                
                        if (resultado != digitos.charAt(0)) {
                            return false;
                        }
                        numeros = cpf.substring(0, 10);
                        soma = 0;
                
                        for (i = 11; i > 1; i--) {
                            soma += numeros.charAt(11 - i) * i;
                        }
                
                        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                
                        if (resultado != digitos.charAt(1))
                            return false;
                
                        return true;
                    } else
                        return false;
                };
                
                if(validateCpfDo(this.val()) == false){
                    var that = this;
                    Fenix.alert('CPF inválido, por favor, verifique o número digitado.', 'CPF Inválido', {callbackOk: function(){ window.setTimeout( function(){ that.val(''); that.focus(); }, 300 ); }});
                }
            }
            
            input.mask("999.999.999-99", {completed: validateCpf });

            
            td.append(input);
        },
        
        /**
         * CNPJ form input formatter
         */
        cnpj: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);
            
            var validateCnpj = function(){
                
                var validateCnpjDo = function(cnpj) {
                    var numeros, digitos, soma, i, resultado, pos, tamanho, digitos_iguais;
                    digitos_iguais = 1;
                    
                    cnpj = cnpj.replace(/([^0-9]+)/g, '');
                    
                    if (cnpj.length < 14 && cnpj.length < 15)
                        return false;
                    for (i = 0; i < cnpj.length - 1; i++)
                        if (cnpj.charAt(i) != cnpj.charAt(i + 1)) {
                            digitos_iguais = 0;
                            break;
                        }
                    if (!digitos_iguais) {
                        tamanho = cnpj.length - 2
                        numeros = cnpj.substring(0, tamanho);
                        digitos = cnpj.substring(tamanho);
                        soma = 0;
                        pos = tamanho - 7;
                        for (i = tamanho; i >= 1; i--) {
                            soma += numeros.charAt(tamanho - i) * pos--;
                            if (pos < 2)
                                pos = 9;
                        }
                        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                        if (resultado != digitos.charAt(0))
                            return false;
                        tamanho = tamanho + 1;
                        numeros = cnpj.substring(0, tamanho);
                        soma = 0;
                        pos = tamanho - 7;
                        for (i = tamanho; i >= 1; i--) {
                            soma += numeros.charAt(tamanho - i) * pos--;
                            if (pos < 2)
                                pos = 9;
                        }
                        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                        if (resultado != digitos.charAt(1))
                            return false;
                
                        return true;
                    } else
                        return false;
                }
                
                if(validateCnpjDo(this.val()) == false){
                    var that = this;
                    Fenix.alert('CNPJ inválido, por favor, verifique o número digitado.', 'CNPJ Inválido', {callbackOk: function(){ window.setTimeout( function(){ that.val(''); that.focus(); }, 300 ); }});
                }
                
            }
            
            input.mask("99.999.999/9999-99", {completed: validateCnpj });

            td.append(input);
        },
        
        /**
         * CEP form input formatter
         */
        cep: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);
            
            input.css('width', '6em');

            var validateCep = function(){
                
                var v = this.value.replace(/([^0-9]+)/g, "");
                
                if(v.length == 8 && $('#cep').data('lastRequest') != v){
                    
                    input.data('lastRequest', v);
                    
                    var success = function(obj){
                        input.data('lastResponse', obj);
                        
                        if(obj && obj.cidade){
                            for(var i in obj){
                                $('#' + i).valChange(obj[i]);
                                if(i == "uf") $('#estado').valChange(obj[i]);
                            }
                            // Move o foco para o número caso tenha sido localizado o endereço
                            // caso contrário move o foco para o endereço
                            if($('#endereco').val()){
	                            $('#numero').focus();
                            } else {
                                $('#endereco').focus();
                            }
                        } else {
                            Fenix.alertHeader('CEP <strong>'+input.val()+'</strong> não encontrado!', 5000);
                        }
                    }
                    
                    var https = window.location.toString().indexOf("https") != -1 ? true : false;
                    var url = "http"+(https?"s":"")+"://servicos.eramo.com.br/cep/"+v;
                    if( navigator.userAgent.match(/MSIE\s9/) !== null ){
	                    url = "cep?cep="+v;
                    }
                    $.getJSON(url, null, success);
                }
            };
            
            input.mask("99.999-999");
            
            input.on('keyup', validateCep);
            input.on('change', validateCep);
            
            td.append(input);
        },
        
        /**
         * Telephone form input formatter
         */
        telephone: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);
            
            input.css('width', '8em');

            var oldMask = "(99) 9999-9999?9";
            var newMask = "(99) 99999-9999";
            
            input.mask( oldMask );
            input.data('__mask', oldMask);
            
            input.on('keyup', function(){
                var el = $(this);
                var val = el.val().replace(/([_]*)$/, '');
                if(val.length == 15){
                    if(el.data('__mask') == oldMask){
                        el.mask( newMask );
                        el.data('__mask', newMask);
                    }
                } else {
                    if(el.data('__mask') == newMask){
                        el.mask( oldMask );
                        el.data('__mask', oldMask);
                    }
                }
            });
            
            td.append(input);
        },
        
        /**
         * Currency form input formatter
         */
        currency: function(text, record, field, form, table, tr, td){
            
            var input = $.fn.form.formatter.input(text, record, field, form, table, tr, td);

            input.css({'text-align': 'right'});
            
            // Definição da largura do campo
            $.fn.form.formatter._cssWidth(input, field, '8em');
            
            input.attr('maxlength', 16);
            
            input.keyup(function(){
                    var value = this.value.replace(/([^\-0-9]+)/g, '').replace(/^([0]+)/g, '');
                    if(value.length > 0){
                        value = number_format(parseFloat(value)/100, 2, ",", ".");
                    } else {
                        value = '';
                    }
                    this.value = value;
                }
            );
            
            if(record && record[field.name]){
               input.val( number_format((record[field.name]).replace(/([^\-0-9]+)/g, '').replace(/^([0]+)/g, '')/100, 2, ",", ".") );
               input.keyup();
            }
            
            td.append(input);
        },
        
        /**
         * Currency form input formatter
         */
        currencyView: function(text, record, field, form, table, tr, td){
            
            td.css("font-weight", 'bold');
            
            if(record && record[field.name]){
                // Provavelmente já formatado
                if(record[field.name].indexOf(",") != -1){
                    record[field.name] = record[field.name].replace(/\./g, '').replace(/,/g, ".");
                }
                td.html( number_format(parseFloat(record[field.name]), 2, ",", ".") );
            }
            
        },
        
        /**
         * Form separator formatter
         */
        separator: function(text, record, field, form, table, tr, td){
            var colspan = $(td).attr('colspan') ? $(td).attr('colspan') : 1;
            $(td).remove();
            $('td', tr).attr('colspan', 2 + colspan);
            $('td', tr).addClass('separator');
        },
        
        /**
         * Option form input formatter
         */
        grid: function(text, record, field, form, table, tr, td){
            
            var grid = JSON.parse(field.grid);
            
            var div = $('<div id="'+ grid.container.replace('#', '') + '"></div>').css('font-weight', 'normal');
            
            td.append(div);
            
            div.grid(grid);
        },
        
        /**
         * Buttons fields default formatter
         */
        buttons: function(text, record, field, form, table, tr, td){
            var buttons = $.type(form.options.buttons) == 'array' ? form.options.buttons : [];
            
            // Clean cell content in case of a update
            td.empty();
            
            for(var i = 0; i < buttons.length; i++){
                var button = Fenix._internals.buttonHtml(buttons[i], record, field, form, table, tr, td);
                
                eval("var fn = " + buttons[i].action);
                button.click(function(){ fn(record, form); });
                
                td.append(button);
                td.append(' ');
            }
        }
        
    }

})( jQuery, window, document );