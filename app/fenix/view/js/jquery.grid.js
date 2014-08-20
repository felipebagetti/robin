//@include "/fenix/app/fenix/view/js/Fenix.js"

/*
 *  Project: jQuery Grid
 *  Description: Grid component that creates a table inside a container
 *  Author: Eramo Software
 */

;(function ( $, window, document, undefined ) { 

    // Create the defaults once
    var defaults = {
            title: "",
            search: false,
            searchAutofocus: true,
            searchString: '',
            searchPlaceHolder: 'digite o conteúdo a ser pesquisado...',
            columns: [],
            buttons: [],
            buttonsPage: [],
            buttonsPageContainer: null,
            data: [],
            dataUrl: "data",
            autoLoad: true,
            loadCallback: "",
            loadTimeout: 350,
            pagination: true,
            paginationPerPage: 10,
            paginationPage: 0,
            sortCol: '',
            sortDir: ''
        };

    // The actual plugin constructor
    function Grid( element, options ) {
        this.element = element;

        // jQuery has an extend method which merges the contents of two or objects
        this.options = $.extend( {}, defaults, options );
        
        // Prepares the loadCallback
        Fenix._internals.evalFunction(this.options, "loadCallback", true);
        
        // Load sessionStorage saved data
        var optionName = ['sortCol', 'sortDir', 'paginationPage', 'searchString'];
        for(var i = 0; i < optionName.length; i++){
            var oName = optionName[i];
            var oValue = Fenix.sessionStorage(this.getUniqueId()+oName);
            if(typeof oValue != 'undefined' && oValue != ''){
                this.options[oName] = oValue;
            }
        }
        
        // Save the default for possible future access
        this._defaults = defaults;
        
        try {
            Fenix_Model.grids = Fenix_Model.grids.add( $(this.element) );
        } catch(e){}
        
        this.init();
    }
    
    Grid.prototype = {

        type: 'grid',
        _numberRows: 0,
        _data: [],
        _header: {},
        _container: null,
        _table: null,
        _requestId: null,
        _requestUrl: null,
        _requestUrlLast: null,
        _lockHighlightChanged: false,
        _filtersContainer: null,
        _filtersListContainer: null,
        _filtersReloadTimeout: null,
        _filtersList: {},
        
        init: function() {
            
            // Faz processamentos relativos às colunas (para otimizar a exibição dos dados na grid)
            this.processColumns();
            
            // Faz processamentos relativos ao botões (para otimizar a exibição dos dados na grid)
            this.processButtons();
            
            // Carrega os filtros salvos em sessionstorage
            if(window.sessionStorage && window.sessionStorage[this.options.id]){
                this._filtersList = JSON.parse(window.sessionStorage[this.options.id]);
            }
            
            // Cria a estrutura HTML da grid
            this.create();
            
            // Inicializa os dados da grid ou a consulta para obter os dados
            if(this.options.autoLoad == true){
	            this.load()
            }
            
            $(this.element).trigger('grid-filters-add');
        },
        
        // Faz o processamento de cada botão
        processButtons: function(){
            for(var i = 0; i < this.options.buttons.length; i++){
                var button = this.options.buttons[i];
                
                // Executa os eval de cada uma das action
                Fenix._internals.evalFunction(button, "action");
            }
        },
        
        // Faz o processamento de cada coluna colocando num formato ótimo de criação de grid
        processColumns: function(){
            for(var i = 0; i < this.options.columns.length; i++){
                var column = this.options.columns[i];
                
                // Executa os eval de cada um dos formatters se existirem
                Fenix._internals.evalFunction(column, "formatter");
            }
        },

        createPaginator: function(response){
            
            this._header = $.extend({}, {count:0, page:0, offset:0}, (response && response.header ? response.header : {}));
            
            if(this.options.pagination == false){
                return;
            }
            
            var header = this._header;
            
            var div = $('#pagination', this._container);
            
            if(div.length == 0){

            	$('<div id="pagination"></div>').appendTo(this._container)
            		.append(
            				$('<div class="pull-left"></div>').append('<span style="float: left; margin-top: 6px;"></span>')
            		).append(
            				$('<div class="pull-right"></div>').append('<ul class="pagination pagination-sm">')
            		);

            }
            
            // Informações de paginação no SPAN
            var firstRecord = parseInt(this._header.offset) + 1;
            var lastRecord = parseInt(this._header.offset) + parseInt(this.options.paginationPerPage);
            var totalRecords = parseInt(this._header.count);
            var page = parseInt(this.options.paginationPage) + 1;
            var totalPages = Math.ceil(parseInt(this._header.count)/parseInt(this.options.paginationPerPage));

            if(lastRecord > totalRecords){
                lastRecord = totalRecords;
            }
            
            // Construção do paginador
            $('li', div).remove();
            
            var grid = this;
            
            var ul = $('ul', div);

            var primeira = $('<li><a>&#171; primeira</a></li>' );
            var anterior = $('<li><a>&lsaquo; anterior</a></li>' );
            
            ul.append( primeira );
            ul.append( anterior );
            
            if(this._header.offset != 0){
                primeira.click( function(){ grid.paginate(0); } )
                anterior.click( (function(){ return function(){ grid.paginate(page-2); } })(page) )
            } else {
                primeira.addClass('disabled');
                anterior.addClass('disabled');
            }
            
            var maxPages = 4;
            
            var firstPage = (page - maxPages/2 < 1 ? 1 : page - maxPages/2);
            var lastPage = (page + maxPages/2 >= totalPages ? totalPages : page + maxPages/2);
            
            if(lastPage < maxPages && totalPages < maxPages){
                lastPage = totalPages;
            } else if(lastPage < maxPages && totalPages >= maxPages) {
                lastPage = maxPages;
            }
            
            if(lastPage - firstPage < maxPages){
                firstPage = lastPage - maxPages;
                if(firstPage < 1){
                    firstPage = 1;
                }
            }
            
            for(var i = firstPage; i <= lastPage; i++){
                var html = $('<li><a>'+i+'</a></li>');
                html.click(function(){
                    grid.paginate( parseInt( $('a', this).text() , 10) - 1 );
                });
                if(page == i){
                    html.addClass('current');
                }
                ul.append( html );
            }
            
            var proxima = $('<li><a>próxima &rsaquo;</a></li>' );
            var ultima = $('<li><a>última &#187;</a></li>' );
            ul.append( proxima );
            ul.append( ultima );
            
            if(totalPages > 0 && page != totalPages){
                proxima.click( (function(){ return function(){ grid.paginate(page); } })(page) )
                ultima.click( function(){ grid.paginate(totalPages-1); } )
            } else {
                proxima.addClass('disabled');
                ultima.addClass('disabled');
            }
            
            // Mostra as informações de paginação no SPAN
            if(header.count > 0){
                $('span', div).html('Mostrando <strong>' + firstRecord + ' &ndash; ' + lastRecord + '</strong> de <strong>' + totalRecords + '</strong> ' );
            } else {
                $('span', div).html('Nenhum registro localizado');
            }
            
        },
        
        createFilters: function(){
            
            var grid = this;
            
            if($.makeArray(grid.options.filters).length == 0){
                return;
            }
            
            // Redefine o atributo name de todos os filtros para serem terem o prefixo
            // filter_ no início. Isso evitará conflitos com telas que sejam abertas
            // e tenham campos com o mesmo id/name
            $.each(grid.options.filters, function(k, field){
                grid.options.filters[k] = $.extend({}, field, {'name':'filter_'+field.name});
            });
		    
		    var span = $('<span> Filtrar: </span>');
		    
		    var data = [];
		    $.each(grid.options.filters, function(k, field){
		        data.push((field.title || field.name)+":"+field.name);
		    });
		    
		    $.fn.form.formatter.select('', {}, {data: data.join(","), name: 'filter_field', width: '140px'}, null, null, null, span);
		    
		    $.fn.form.formatter.select('', {}, {name: 'filter_operator', width: '140px'}, null, null, null, span);
		    
            // Método que cria o campo para um filtro
            var createField = function(k, field){
                // Pré-avalia as funcões de formatter e formatterView
                Fenix._internals.evalFunction(field, "formatter");
                Fenix._internals.evalFunction(field, "formatterView");
                delete field["default"];
                // Executa o formatter
                field.formatter('', {}, field, grid, null, null, span);
                // Adiciona um evento no Enter
                var fieldElement = $("#"+field.name, span);
                fieldElement.on('keyup', function(e){
                    if(e.keyCode == 13){
                        $('button', span).click();
                    }
                });
                // Se for um select2 adiciona um evento no fechamento
                if(fieldElement.select('container')){
                    fieldElement.on('select2-close', function(){
                        if(this.value){
                            $('button', span).click();
                        }    
                    });
                }
                // Só mostra o primeiro filtro do operador normal
                if(k > 0 || field.name.indexOf("_between") != -1){
	                fieldElement.hide();
                }
                
	            // Adiciona os eventos de dependencia de FK
	            if(field.depends){
	                
	                // Adiciona um evento para recarregar a dependencia
	                $(grid.element).on('grid-filters-add', function(e){ Fenix._internals.fkDependencyFilter(e, grid, field); });
	                $(grid.element).on('grid-filters-delete', function(e){ Fenix._internals.fkDependencyFilter(e, grid, field); });
	            }
	            
            };
            
		    // Cria o campo de de cada um dos filtros a serem exibidos
		    $.each(grid.options.filters, createField);
		    
		    // Texto a ser mostrado entre os dois campos do operador entre
		    span.append('<span class="filter-bar-and" style="display: none">&nbsp;e&nbsp;&nbsp;&nbsp;</span>');
		    
		    // Cria o segundo campo de cada formatter (para o operador entre)
            $.each(grid.options.filters, function(k, field){
                if($.inArray(field.type, ["fk", "select"]) == -1){
	                createField(k, $.extend({}, field, {'name':field.name+"_between"}));
                }
            });
		    
		    span.append('<button type="button" class="btn btn-default btn-xs"><i class="glyphicon glyphicon-plus-sign"></i>&nbsp;Incluir</button>');
		    
		    // Evento de mudança do operador
		    $('#filter_operator', span).on('change', function(){
		        
                var field = $("#filter_field", span).val();
                
                var input = $('#'+field, span);
                var inputBetween = $('#'+field+"_between", span);
                
                var filterBarAnd = $(".filter-bar-and", span);
                
                input.show();
                filterBarAnd.hide();
                inputBetween.hide();
                
		        if(this.value == "isnotnull" || this.value == "isnull"){
		            input.hide();
		        } else if(this.value == "between"){
		            filterBarAnd.show();
		            inputBetween.show();
		        }
		    });
            
		    $('#filter_operator', span).on('select2-close', function(){
                $("#filter_field", grid._filtersContainer).trigger('select2-close');
            });
		    
		    // Evento de mudança do elemento selecionado no combo de seleção de filtros (campos)
		    $('#filter_field', span).on('change', function(){
                
		        var field = grid.getFilter(this.value);
		        
		        // Redefine a situação de visualidade do campo correspondente ao filtro
		        $.each(grid.options.filters, function(k, f){
		            $("#"+f.name).hide().val("");
		            $("#"+f.name+"_between").hide().val("");
		        });
		        
		        var options = [];
		        var optionDefault = "contains";
		        
		        if( $.inArray(field.type, ["fk", "select", "int", "currency", "percent", "bigint", "decimal", "date", "datetime", "date"]) == -1 ){
		            options.push({'value':'contains','title':'contém'});
		            options.push({'value':'notcontains','title':'não contém'});
		        }
		        
		        if( $.inArray(field.type, ["fk", "select", "int", "currency", "percent", "bigint", "decimal", "date", "datetime", "date"]) != -1 ){
		            
		            optionDefault = "eq";
                    
		            options.push({'value':'eq','title':'igual a'});
		            options.push({'value':'neq','title':'diferente de'});
		            
		            if($.inArray(field.type, ["fk", "select"]) == -1){
		                options.push({'value':'between','title':'entre'});
		                options.push({'value':'gt','title':'maior que'});
		                options.push({'value':'lt','title':'menor que'});
		            }
		            
		        }
		        
		        options.push({'value':'isnotnull','title':'está definido'});
		        options.push({'value':'isnull','title':'não está definido'});
		        
                // Redefine a lista de opções do campo operator
                $("#filter_operator", span)
                    .empty()
                    .append( $.map(options, function(v){ return $('<option value="'+v.value+'">'+v.title+'</option>');  }) )
                    .valChange( optionDefault );
		    });
            
            // Quando define um novo valor para o combo de seleção de campos foca no campo de seleção do filtro
            $('#filter_field', span).on('select2-close', function(e){
                window.setTimeout(function(){
                    $("#"+$("#filter_field", grid._filtersContainer).val(), grid._filtersContainer).focus();
                }, 100);
            });
		    
		    // Ação de inclusão de um novo filtro na lista
		    $('button', span).on('click', function(){
                
		        var field = $('#filter_field', grid._filtersContainer).val();
		        var operator = $('#filter_operator', grid._filtersContainer).val();
                
                var input = $('#'+field, grid._filtersContainer);
                var inputBetween = $('#'+field+"_between", grid._filtersContainer);
                
                var inputPopover = input.select2('container') || input;
                var inputBetweenPopover = inputBetween.select2('container') || inputBetween;
                
                inputPopover.popover('destroy');
                inputBetweenPopover.popover('destroy');
                
		        var value = input.val();
		        var valueBetween = inputBetween.val();
                
                // No caso do operador ser isnull ou isnotnull faz com que o value seja igual ao título do operador
                if($.inArray(operator, ["isnull", "isnotnull"]) !== -1){
                    value = $('#filter_operator option[value="'+operator+'"]').text();
                }
                
                // Mostra um popover para alertar o usuário
                if(!value){
                    Fenix.popover(inputPopover, 'Informe um valor para incluir um filtro.', {timeout: 3000});
                    inputPopover.focus();
                    return false;
                }
		        
		        if(operator == 'between'){
	                // Mostra um popover para alertar o usuário
                    if(!valueBetween){
                        Fenix.popover(inputBetweenPopover, 'Informe um valor para incluir um filtro.', {timeout: 3000});
                        inputBetweenPopover.focus();
	                    return false;
	                }
		            value = [value, valueBetween];
		        }
                
                if( grid.filtersAdd( grid.getFilter(field).nameDatabase , operator, value) !== true ){
                    Fenix.popover(input, 'Um filtro com o mesmo valor já existe.', {timeout: 3000});
                    input.focus();
                    input.get(0).select();
                    return false;
                }
                
		        // Redefine os valores dos campos
                input.valChange("");
                inputBetween.valChange("");
                
                // Foco para o campo do filtro ativo
                window.setTimeout(function(){
                    input.focus();
                }, 100);
		    });
		    
	        grid._filtersContainer = grid._filtersContainer ? grid._filtersContainer : $('<div class="filter-container"></div>').appendTo( grid._container );
	        
            // Limpa o conteúdo e adiciona o span com os componentes do filtro
            grid._filtersContainer.empty().append(span)
            
            // Seleciona o primeiro campo disponível nos filtros
		    $('#filter_field', grid._filtersContainer).valChange( $('#filter_field > option[value!=]:first', grid._filtersContainer).val() );
            
            // Mostra os filtros existentes (para o caso de já estarem definidos no carregamento da grid)
            grid.filtersShow();
        },
        
        /**
         * Adiciona um novo filtro à lista da página
         */
        filtersAdd: function(field, operator, value, preventReload){

            var ret = false;
            
            // Cria o objeto do field, caso necessário
            if(!this._filtersList[field]){
                this._filtersList[field] = {};
            }
            
            // Cria o objeto do operator, caso necessário
            if(!this._filtersList[field][operator]){
                this._filtersList[field][operator] = [];
            }
            
            if(value && $.inArray(value, this._filtersList[field][operator] ) == -1){
                
                // Inclui no array do operador
                this._filtersList[field][operator].push(value);
                
                // Mostra os filtros atualizados
                this.filtersShow();
                
                // Recarrega o grid
                if(!preventReload){
	                this.filtersReload();
                }
                
                // Marca o sucesso da operação
                ret = true;
                
                // Trigger
                $(this.element).trigger('grid-filters-add');
            }
            
            return ret;
        },
        
        createHeader: function(){

            // Criação do cabeçalho e da primeira linha dele
            var thead = $('<thead></thead>');
            var row = $('<tr></tr>');
                
            for(var j = 0; j < this.options.columns.length; j++){
                
                // Coluna sendo trabalhada
                var column = this.options.columns[j];
                
                var cell = $('<th id="'+this._container.get(0).id+'-'+column.name+'"></th>');
                
                // Definição do estilo de cabeçalho
                if( $.type(column.styleHeader) == 'string' ){
                    cell.addClass( column.styleHeader );
                }
                
                // Definição da largura padrão da coluna
                if( $.type(column.width) != 'undefined' ){
                    cell.css('width', column.width);
                }
                
                // Definição do texto do cabeçalho
                cell.html( $.type(column.title) == "string" ? column.title : '' );
                
                // Define o símbolo de ordenação
                var span = $('<span></span>');
                cell.append( span );
                
                cell.prop('nowrap', 'nowrap');
                
                if(this.options.sortCol == column.name){
                    if(this.options.sortDir == 'DESC'){
                        span.html('&nbsp;&#8595;');
                    } else {
                        span.html('&nbsp;&#8593;');
                    }
                }

                // Adiciona o evento de ordenação ao clicar na coluna caso seja ordenável
                if(column.sortable){
                    var _grid = this;
                    cell.get(0).column = column;
                    cell.click(function(){ _grid.sort(this.column.name); });
                    cell.css({'cursor': 'pointer'});
                }
                
                // Inclusão da célula a linha e cabeçalho
                row.append(cell);
            }
            
            thead.append(row);
            
            this._table.append(thead);
        },
        
        filtersReload: function(){
            window.clearTimeout(this._filtersReloadTimeout);
            var grid = this;
		    this._filtersReloadTimeout = window.setTimeout(function(){ grid.load(); }, 100);
        },
        
        filtersGet: function(fieldName, operatorName){
            
            var grid = this;
            var fieldName = fieldName ? "filter_" + fieldName : null;
            
            var ret = {};
            
            $.each(grid._filtersList, function(field, operatorList){
                
                // Obtém a definição do filtro
                fieldObj = grid.getFilter(field);
                
                if(fieldName == fieldObj.name || fieldName == fieldObj.name_database){
                    ret = {};
                    ret[field] = grid._filtersList[field];
                }
                
                $.each(operatorList, function(operator, valueList){
                    
	                if(operatorName == operator){
                        ret = {};
                        ret[field] = {};
                        ret[field][operator] = grid._filtersList[field][operator];
	                }
                    
                });
                
            });
            
            return ret;
        },
        
        filtersShow: function(){
            
            var grid = this;
            
            grid._filtersListContainer = grid._filtersListContainer ? grid._filtersListContainer : $('<div class="filter-list"></div>').insertBefore( grid._filtersContainer );
            grid._filtersListContainer.empty();
            	    
		    var lineNumber = 0;
            
		    $.each(grid._filtersList, function(field, operatorList){
                
                // Obtém a definição do filtro
	            field = grid.getFilter(field);
                
                // Caso não exista o filtro ignora
                if(!field){
                    return;
                }
                
		        $.each(operatorList, function(operator, valueList){
		            
		            // Só imprime a lista caso existam valores
		            if(valueList.length > 0){
                        
		                var fieldTitle = $('#filter_field option[value="'+field.name+'"]', grid._filtersContainer).text();
		                var operatorTitle = $('#filter_operator option[value="'+operator+'"]', grid._filtersContainer).text();
		                
		                var line = $('<div class="line" id="line-'+lineNumber+'"><div>');
		                
		                line.append( $('<span class="line-number">'+(lineNumber+1)+'. </span>') );
		                line.append( $('<span class="line-field">'+fieldTitle+' </span>') );
                        
                        // Só mostra o título para operadores diferentes de isnull e isnotnull
                        // pois nesses casos o próprio titulo é o perador na lista
                        if($.inArray(operator, ["isnull", "isnotnull"]) == -1){
			                line.append( $('<span class="line-operator">'+operatorTitle+' </span>') );
                        }
		                
		                var lineValues = $('<span class="line-values"></span>');
                            
		                $.each(valueList, function(k, value){
		                    
		                    if(lineValues.children().length > 0){
		                        lineValues.append(", ");
		                    }
		                    
		                    var a = $('<a href="#"></a>');
		                    
		                    var valueFormatted = $.makeArray(value);
		                    
		                    // Tenta reutilizar o formatter para exibição do campo num formulário
                            // Mas caso seja um dos operadores: isnull e isnotnull não utiliza formatter
		                    if($.inArray(operator, ["isnull", "isnotnull"]) == -1 && field.formatterView !== null){
		                        $.each(valueFormatted, function(k, v){
		                            var record = {};
		                            record[field.name] = v;
		                            var tmp = $('<td></td>');
		                            var formatterRet = field.formatterView(v, record, field, grid, null, null, tmp);
		                            // O formatter pode retornar o conteúdo
		                            if(formatterRet){
		                                valueFormatted[k] = formatterRet;
		                            }
		                            // Ou definir diretamente o valor no elemento temporário repassado
		                            else {
		                                valueFormatted[k] = tmp.html();
		                            }
		                        });
		                    }
		                    
		                    a.html(valueFormatted.join(" e "));
		                    
		                    a.css('font-weight', '');
		                     
		                    // Elemento a com ação padrão clicar de remover o filtro para esse valor
		                    lineValues.append( a.on('click', function(e){
		                        e.preventDefault();
		                        grid.filtersDelete(field.nameDatabase, operator, value);
		                        return false;
		                    }) );
		                    
		                });
                        
		                line.append( lineValues );
                        
		                // Ação do botão de remover a linha inteira de filtros

		                line.append( $('<button class="close">×</button>').on('click', function(){ grid.filtersDeleteOperator(field.nameDatabase, operator); }) );

		                grid._filtersListContainer.append( line );
		                
		                lineNumber++;
		            }
		            
		        });
		    });  
            
        },
        
        /**
         * Remove todos os filtros para o campo/operador/valor
         */
        filtersDelete: function(field, operator, value, preventReload){
            
            // Remove o valor definido
            this._filtersList[field][operator] = $.grep(this._filtersList[field][operator], function(v){
                return v != value;
            });
            
            // Se o operador estiver vazio remove o objeto
            if(this._filtersList[field][operator].length == 0){
                delete this._filtersList[field][operator];
            }
            
            if(JSON.stringify(this._filtersList[field]) == "{}"){
                delete this._filtersList[field];
            }
            
            // Mostra novamente os filtros
            this.filtersShow();
            
            // Recarrega o grid
            if(!preventReload){
	            this.filtersReload();
            }
            
            // Foco para o campo do filtro ativo
            $('#filter_operator', this._filtersContainer).trigger('change');
            
            // Trigger
            $(this.element).trigger('grid-filters-delete');
        },
        
        /**
         * Remove todos os filtros para o campo/operador
         */
        filtersDeleteOperator: function(field, operator, preventReload){
            
            // Remove todos os filtros para o campo no operador
	        delete this._filtersList[field][operator];
	        
	        // Caso não haja mais filtros para o campo remove também o campo da estrutura de dados
	        if(JSON.stringify(this._filtersList[field]) == "{}"){
	            delete this._filtersList[field];
	        }
	        
	        // Mostra novamento os filtros
	        this.filtersShow();
	        
            // Recarrega o grid
            if(!preventReload){
                this.filtersReload();
            }
	        
	        // Foco para o campo do filtro ativo
	        $('#filter_operator', this._filtersContainer).trigger('change');
        },
        
        /**
         * Remove todos os filtros para o campo
         */
        filtersDeleteField: function(field, preventReload){
            
            // Remove todos os filtros para o campo
	        delete this._filtersList[field];
	        
	        // Mostra novamento os filtros
	        this.filtersShow();
	        
            // Recarrega o grid
            if(!preventReload){
                this.filtersReload();
            }
	        
	        // Foco para o campo do filtro ativo
	        $('#filter_operator', this._filtersContainer).trigger('change');
        },
        
        createSearch: function(){
            if( this.options.search == true ){
            	var search = $('<form class="navbar-form" role="search" onsubmit="return false" autocomplete="off">' +
            			'<div class="input-group">'+	 
            			'<span class="input-icon input-icon-search">'+
            			'<input type="text" class="form-control" name="grid-q" id="grid-q" placeholder="'+this.options.searchPlaceHolder+'" value="'+this.options.searchString+'" >'+
            			'<i class="glyphicon glyphicon-search search"></i>'+
            			'</span>'+'</div>'+'</form>'         			
            	);
            	
                var _grid = this;
                               
                $('#grid-q', search).bind('keyup', function(){
                    
                    // Remove o botão de remover o conteúdo da busca
                    if( $(this).data('_gridLastValue') != this.value ){
                        $('button.close', $('#grid-q', _grid._container).closest('form') ).remove();
                    }
                    
                    $(this).data('_gridLastValue', this.value);
                    
                    // Faz o procedimento de timeout para a busca
                    var value = this.value;
                    
                    window.clearTimeout($.fn.grid.loadTimeout);
                    
                    $.fn.grid.loadTimeout = window.setTimeout( function(e){
                            
                        _grid.search(value);
                        
                        }, _grid.options.loadTimeout
                    );
                    
                });
                
                $('i', search).on('click', function(){ $('#grid-q', search).trigger('keyup'); });
                               
                this._container.append(search);
                
                if( this.options.searchAutofocus == true ){
	                $('#grid-q', search).focus();
                }
            }
            
        },
        
        createButtonsPage: function(){
            
            if(this.options.buttonsPageContainer == null){
                this.options.buttonsPageContainer = $('<div id="buttons-page"></div>');
                
                this.options.buttonsPageContainer.css({'text-align': 'right'}).html("&nbsp;");
                
                this._container.append(this.options.buttonsPageContainer);
            }
            
            Fenix._internals.createButtonsPage(this.options.buttonsPage, this.options.buttonsPageContainer, this);
            
        },
        
        create: function(){
            
            this._container = $( this.element );
            
            if( this.options.title.length > 0 ){
                var header = $('<h4></h4>').html( this.options.title.replace(/\\n/g, "<br>") );
                
                this._container.append(header);
            }
            
            this.createSearch();
            
            this.createFilters();
            
            this.createButtonsPage();
            
            this.createPaginator();
            
            this._table = $('<table></table>').addClass('table table-bordered table-condensed table-striped table-hover');
            
            this.createHeader();
            
            this._table.append( $('<tbody></tbody>') );
            
            this._container.append(this._table);
        },
        
        search: function(searchString){
            if(searchString != this.options.searchString){
                this.options.searchString = searchString;
                
                Fenix.sessionStorage(this.getUniqueId()+'searchString', this.options.searchString);
                
                this.load();
            }
        },
        
        createBody: function(response){
            
            if(response && typeof response.data != 'undefined' && $.isArray(response.data)){
                
                this._data = response.data;
                
                var data = this._data;
                
                var tbody = $("tbody:first", this._table);
                
                for (var i = 0; i < data.length; i++) {
                    
                    // Tenta a reutilização da linha
                    var row = null;
                    var rowExists = (this._numberRows > i) ? true : false;
                    
                    if(rowExists){
                        row = $("tbody:first > tr:nth-child("+(i+1)+")", this._table);
                    }
                    // Caso a linha não exista cria e adiciona ao tbody
                    else {
                        row = $(document.createElement('TR'));
                        tbody.append(row);
                        this._numberRows++;
                    }
                    
                    // Registro sendo preenchido
                    var record = data[i];
                    
                    for(var j = 0; j < this.options.columns.length; j++){
    
                        // Coluna sendo trabalhada
                        var column = this.options.columns[j];
    
                        // Tenta a reutilização da célula
                        var cell = null;
                        var cellExists = false;
                        
                        if(rowExists == true){
                            cell = $("td:nth-child("+(j+1)+")", row);
                        }
                        
                        // Caso a linha não exista cria e adiciona ao tbody
                        if(cell == null || cell.length == 0) {
                            cell = $(document.createElement('TD'));
                            row.append(cell);
                        } else {
                            cellExists = true;
                        }
                        
                        // Texto a ser colocado na grid
                        var text = typeof record[column.name] != "undefined" ? record[column.name] : '';
                        
                        // Verifica a necessidade de definição do estilo do conteúdo
                        if( $.type(column.styleContent) == 'string' ){
                            cell.addClass( column.styleContent );
                        }
                        
                        var contentBefore = cell.html();
                        
                        // Faz o processamente do formatter, caso necessário
                        if( $.isFunction( column.formatter ) ){
                            text = column.formatter(text, record, column, this, this._table, row, cell);
                        }
                        
                        // Define o texto da célula depois dele ter passado pelo formatter
                        // somente caso o formatter não retorne undefined (caso onde o próprio)
                        // formatter pode ter manipulado o componente cell
                        if($.type(text) != "undefined"){
                            cell.html( text );
                        }
                        
                        // Definição da largura padrão da coluna depois de ter passado pelo formatter
                        // para permitir que seja fixada uma largura
                        if( $.type(column.width) != 'undefined' ){
                            cell.css('width', column.width);
                        }
                        
                        if(cellExists == true && !this._lockHighlightChanged && contentBefore != cell.html()){
                            cell.addClass('updated');
                            
                            (function(c){
                                window.setTimeout(function(){
                                    c.removeClass('updated');
                                }, 2000);
                            })(cell);
                        }
                    }
                    
                }
                
                // Remove as linhas que não foram utilizadas
                var row = $("tbody:first > tr", this._table).slice(data.length);
                row.remove();
                
                // Se o conjunto de dados é vazio o slice deixa um elemento sendo mostrado
                // no grid ainda
                if(data.length == 0){
                    $("tbody:first > tr", this._table).remove();
                }
                
                this._numberRows = data.length;
                
            }
            
        },
        
        count: function(row){
            return this._data.length;
        },
        
        getRecord: function(row){
            var ret = null;
            if(this._data[row]){
                ret = this._data[row];
            }
            return ret;
        },
        
        _searchDismiss: function(){
            // Input com o conteúdo da busca
            var input = $('#grid-q', this._container);
            
            // Insere o botão de dismiss ao lado do texto da pesquisa
            var form = $('#grid-q').closest('form');
            
            // Cria um span para calcular o tamanho do texto digitado no input
            var span = $('<span></span>').html( input.val() );
            span.css( {border: '1px solid black', position: 'absolute', top: '-1000px', left: '-1000px'} );
            span.css( 'font', input.css('font') );
            span.css( 'padding', input.css('padding') );
            span.css( 'margin', input.css('margin') );
            $('body').append(span);
            
            var width = span.width() + 40;
            span.remove();
            
            if(input.width() < width - 40 ){
                width = input.width() + 60;
            }
            
            if($('button.close', form).length == 0){
            	
                // Criação do botão
                var dismiss = $('<button class="close">&times;</button>').css({ 'left': width+'px'});

                // Enquanto insere caracteres não aparecerá o X
                input.on('keypress', function(){
                	dismiss.css({display:'none'});
                });
                
                dismiss.on('click', function(){
                     input.val('').trigger('keyup');
                     input.focus();
                });
                
                // Inserção no navbar
                $('.input-icon-search', form).append(dismiss);
                
            }
            else {
            	var dismiss = $('button.close', form);
            	dismiss.css({ 'left': width+'px'});
            	dismiss.css({display:''});
            }
        },
        
        _loadUrl: function(){
            
            var url = this.options.baseUri + this.options.dataUrl;
            
            if(url.indexOf("?") == -1){
                url += "?";
            }
            
            var params = [];
            
            if(this.options.params){
                params = this.options.params.split("&");
            }
            
            // FiltersList
            params.push('_filters=' + JSON.stringify(this._filtersList));
            
            // Saves current filters list on session storage when available
            if(window.sessionStorage){
                window.sessionStorage[this.options.id] = JSON.stringify(this._filtersList);
            }
            
            // Search string
            if(this.options.searchString){
                params.push(  "q=" + encodeURIComponent(this.options.searchString) );
                
                // Mostra o botão de dismiss da busca realizada
                this._searchDismiss();
            }
            
            // Request id (avoid cache and and always display last request and not last received data) 
            this._requestId = parseInt(Math.random()*100000);
            
            params.push('_requestId=' + this._requestId);
            
            // Pagination
            params.push('_limit=' + this.options.paginationPerPage);
            params.push('_offset=' + parseInt(this.options.paginationPage) * parseInt(this.options.paginationPerPage));
            
            // Order
            if(this.options.sortCol){
                params.push('_sortCol=' + this.options.sortCol);
                if(this.options.sortCol){
                    params.push('_sortDir=' + this.options.sortDir);
                }
            }
            
            // Finalizing
            url = url + '&' + params.join("&");
            
            return url;
        },
        
        _loadData: function(){
            return {};
        },
        
        getUniqueId: function(){
            return (window.location.toString() + '_' + this.options.container).replace(/([^0-9A-Za-z])/g, '_');
        },
        
        sort: function(name){
            
            this._lockHighlightChanged = true;
            
            if(this.options.sortCol == name){
                this.options.sortDir = this.options.sortDir == 'DESC' ? 'ASC' : 'DESC'; 
            } else {
                this.options.sortDir = 'ASC';
            }
            
            $('span', $('th', this._container)).html('');
            
            var span = $('span', $('#'+this._container.get(0).id+'-'+name));
            
            if(this.options.sortDir == 'DESC'){
                span.html('&nbsp;&darr;');
            } else {
                span.html('&nbsp;&uarr;');
            }
            
            this.options.sortCol = name;
            
            Fenix.sessionStorage(this.getUniqueId()+'sortCol', this.options.sortCol);
            Fenix.sessionStorage(this.getUniqueId()+'sortDir', this.options.sortDir);
            
            this.load();
        },
        
        paginate: function(page){
            this._lockHighlightChanged = true;
            this.options.paginationPage = page;
            
            Fenix.sessionStorage(this.getUniqueId()+'paginationPage', this.options.paginationPage);
            
            this.load();
        },
        
        getColumn: function(name){
            var ret = null;
            
            var columns = this.options.columns;
            for(var i = 0; i < columns.length; i++){
                if(columns[i].name == name){
                    ret = columns[i];
                }
            }
            
            return ret;
        },
        
        getFilter: function(name){
            var ret = null;
            
            var filters = this.options.filters;
            for(var i = 0; i < filters.length; i++){
                if(filters[i].name == name || filters[i].nameDatabase == name){
                    ret = filters[i];
                }
            }
            
            return ret;
        },
        
        setUrlParam: function(key, value){
            var url = this.options.dataUrl;
            
            if(url.indexOf("?") === -1){
                url += "?";
            }
            
            url = url.replace(new RegExp("&"+key+"\=([^&]*)", "g"), '');
            
            if( value ){
                url = url + "&"+key+"="+value;
            }
            
            this.options.dataUrl = url;
        },
        
        load: function(){
            
            var _grid = this;
            
            // Quando já se recebe os dados da grid na própria construção da mesma
            if( $.type(_grid.options.data) == "array" && _grid.options.data.length > 0 ) {
                _grid.createBody( {'data':_grid.options.data} );
            }
            // Quando o objeto recebido está no padrão esperador
            else if( $.type(_grid.options.data) == "object" ) {
                _grid.createBody( {data: _grid.options.data} );
            }
            // Quando há URL de dados definida
            else if(typeof _grid.options.dataUrl != 'undefined') {
                
	            var _loadUrl = _grid._loadUrl();
	            
                Fenix.alertHeaderClose();
	            Fenix.alertHeader('Carregando dados...');
	            
	            var success = function(response, textStatus, xhr){
	                
	                // T1202 - Grid - Impedir que requisições dessincronizadas carreguem dados atrasados no grid
	                if(response && response.header && parseInt(response.header.requestId) > 0){
	                    // Se não for o requestId mais recente ignora o resultado
	                    if(response.header.requestId != _grid._requestId){
	                        return;
	                    }
	                }
	                
	                _grid._requestUrlLast = _grid._requestUrl;
	                _grid._requestUrl = _loadUrl.replace(/\&\_requestId\=([0-9]+)/, '');
	                
	                // T0012 - Grid - Ao carregar um grid em página > 0 que não tenha registro voltar automaticamente para a página 0
	                if(response && response.header && parseInt(response.header.offset) > 0 && parseInt(response.header.offset) >= parseInt(response.header.count)){
	                    _grid.paginate(0);
	                    return;
	                }
	                
	                _grid.createPaginator(response)
	                
	                if(xhr.status == 200){
	                    _grid.createBody(response);
	                }
	                
	                _grid.options.loadCallback();
	                
	                $(_grid.element).trigger('grid-loaded');
	                
	                _grid._lockHighlightChanged = false;
	                
	                Fenix.alertHeaderClose(true);
	                
	                $('body').modalmanager('layout'); 
	            }
	            
	            $.getJSON( _loadUrl, _grid._loadData(), success );
                
            }
            
        }
        
    };
        
    var methods = {
        
        getOptions: function(){
            var ret = null;
            
            this.each(function(){
                ret = $.data(this, "grid").options;
            });
            
            return ret;
        },
        
        getObject: function(){
            var ret = null;
            
            this.each(function(){
                ret = $.data(this, "grid");
            });
            
            return ret;
        },
        
        getUrl: function(){
            var ret = null;
            
            this.each(function(){
                ret = $.data(this, "grid").options.dataUrl;
            });
            
            return ret;
        },
        
        setUrl: function(dataUrl){
            return this.each(function(){
                $.data(this, "grid").options.dataUrl = dataUrl;
            });
        },
        
        /**
         * Método genérico de repasse das chamadas de método do objeto grid
         * permitindo que todos os métodos do objeto interno Grid do plugin
         * sejam executados através $().grid('...') 
         */
        __methodCall: function( method, args ){
         
            var ret = undefined;
            
            this.each(function(){
                var grid = $.data(this, "grid");
                if(grid[method]){
	                ret = grid[method].apply( grid, args );
                } else {
                    throw 'Method ' +  method + ' does not exist on jQuery.grid';
                }
            });
            
            return ret;
        }
        
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn.grid = function ( method ) {
        
        var args = Array.prototype.slice.call( arguments, 1 );
        
        // Method calling logic
        if ( methods[method] ) {
            
            return methods[ method ].apply( this, args );
            
        } else if ( typeof method === 'object' || ! method ) {

            return this.each(function () {
                if (!$.data(this, "grid")) {
                    $.data(this, "grid", new Grid( this, method ));
                }
            });
            
        } else {
            // Tries to execute the method on the object 
            try {
                return methods.__methodCall.apply(this, [method, args] );
            } catch(e){
	            $.error( e );
            }
        }   
    };
    
    // Constant definitions, specially formatters
    $.fn.grid.formatter = {
        
        /**
         * Option column default formatter
         */
        option: function(text, record, column, grid, table, tr, td){
            
            td.css('text-align', 'center');
            
            if(text == 'Yes' || text == 'Sim' || text == '1'){
                if(text == '1'){
                    text = 'Sim';
                }
                return text;
            }
            
            return '-';
        },
        
        /**
         * Date column default formatter
         */
        date: function(text, record, column, grid, table, tr, td){
            
            if(text){
                text = text.substr(0, 10).split('-').reverse().join('/');
            }
            
            if(td && td.css){
                td.css('width', '6em');
            }
            
            return text;
        },
        
        /**
         * Datetime column default formatter
         */
        datetime: function(text, record, column, grid, table, tr, td){
            
            if(text){
                text = text.substr(0, 10).split('-').reverse().join('/') + text.substr(10, 9);
            }
            
            if(td && td.css){
                td.css('width', '10em');
            }
            
            return text;
        },
        
        /**
         * Select column default formatter
         */
        bigtext: function(text, record, column, grid, table, tr, td, formatter){
            
            if($.type(text) === "string"){
                text = text.split("\n").join("<br>");
            }
            
            return text;
        },
        
        /**
         * Select column default formatter
         */
        select: function(text, record, column, grid, table, tr, td, formatter){
            
            // Tenta Fazer o parser dos dados do campo de definir o valor
            var data = Fenix._internals.parseFieldSelectData(column);
            
            if(data != null){
                for(var title in data){
                    if(data[title] == text){
                        text = title;
                    }
                }
            }
            
            return text;
        },
        
        /**
         * Radio column default formatter
         */
        radio: function(text, record, column, grid, table, tr, td, formatter){
            
            td.css('text-align', 'center');
            
            // Tenta Fazer o parser dos dados do campo de definir o valor
            var data = Fenix._internals.parseFieldSelectData(column);
            
            if(data != null){
                for(var title in data){
                    if(data[title] == text){
                        text = title;
                    }
                }
            }
            
            return text;
        },
        
        /**
         * Currency column default formatter
         */
        currency: function(text, record, column, grid, table, tr, td, formatter){
            if(text){
                return number_format(text, 2, ",", ".");
            } else {
                return "";
            }
        },
        
        /**
         * Buttons columns default formatter
         */
        buttons: function(text, record, column, grid, table, tr, td, removeButtons){
            
            var buttons = $.type(grid.options.buttons) == 'array' ? grid.options.buttons : [];
            var removeButtons = $.type(removeButtons) == 'array' ? removeButtons : [];
            
            // Clean cell content in case of a update
            td.empty();
            
            // Remove o evento de clique que pode ter sido colocado
            tr.off('click.grid');
            
            for(var i = 0; i < buttons.length; i++){
                
                if($.inArray(buttons[i].title, removeButtons) >= 0){
                    continue;
                }
                
                // Se for o botão de visualizar faz com que ao clicar na
                // linha se abra o formulário de visualização
                if(buttons[i].title == 'Visualizar'){
                    tr.css('cursor', 'pointer');
                    tr.attr('title', 'Clique para visualizar o registro.');
                    tr.on('click.grid', function(){
                        Fenix_Model.view( record, grid ); 
                    });
                    continue;
                }
                
                var button = Fenix._internals.buttonHtml(buttons[i], record, column, grid, table, tr, td);
                
                button.get(0).fenixBtn = buttons[i];
                
                button.click(function(event){
                    event.stopPropagation();
                    Fenix.event = event; 
                    this.fenixBtn.action(record, grid);
                });
                
                td.append(button);
                td.append(' ');
            }
        }
        
    }

})( jQuery, window, document );