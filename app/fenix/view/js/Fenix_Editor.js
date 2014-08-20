//@include "/fenix/app/fenix/view/js/Fenix.js"

Fenix_Editor = {};
    
Fenix_Editor._rows = [];

Fenix_Editor.modelCreate = false;
    
Fenix_Editor.getRow = function(tr){
    for(var i = 0; i < this._rows.length; i++){
        if(this._rows[i] == tr){
            return i;
        }
    }
    this._rows[this._rows.length] = tr;
    return this._rows.length-1;
}
    
Fenix_Editor.getName = function(column, tr){
    return column.name+'['+this.getRow(tr)+']';
}
    
Fenix_Editor.loadCallback = function(){
        
    Fenix_Editor._rows = [];
    
    if($("#__model").length > 0){
        return;
    }
    
    var div = $('<div style="padding-bottom: 10px;">Modelo:&nbsp;&nbsp;</div>');
    
    var select = $('<select id="__model" name="__model"></select>');
    
    select.css({'padding':'0px 0px 0px 3px','margin':'0px','width':'300px', 'height':'18px'});
    
    for(var i = 0; i <Fenix_Editor.models.length; i++){
        var model = Fenix_Editor.models[i];
        var title = model.replace("/model/xml", "").replace("app/modules/", "");
        var option = $('<option value="'+model+'">'+title+'</option>');
        if(model == Fenix_Editor.model){
            option.attr('selected', true);
        }
        select.append(option);
    }
    
    select.bind('change', function(){
        window.location = "?model=" + this.value;
    });
    
    div.append(select);
    
    Fenix.select2(select);
    
    var buttonNew = $('<button class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-plus-sign icon-white" /> Novo Modelo</button>')
    
    buttonNew.click(function(){
        var nome = prompt('Nome do novo modelo:');
        
        if(nome){
            var location = window.location.toString().split("/");
            location.pop();
            window.location = location.join("/") + '/' + nome.replace(/\.xml$/, "") + ".xml";
        }
    });
    
    div.append($('<span>&nbsp;&nbsp;</span>'));
    div.append(buttonNew);
    
    div.insertBefore($('#buttons-page'));
}

Fenix_Editor.formatterName = function(text, record, column, grid, table, tr, td){
    
    td.empty();
    
    var record2 = record;
    
    if(record.__type == 'section'){
        record2 = $.extend({}, record, {'__type':'field'});
    }
    
    Fenix_Editor.formatterText(text, record2, column, grid, table, tr, td);
    
    td.append($('<input type="hidden" name="'+Fenix_Editor.getName({name: '__type'}, tr)+'" value="'+record.__type+'">'));
}

Fenix_Editor.formatterTitle = function(text, record, column, grid, table, tr, td){
    
    td.empty();
    
    var record2 = record;
    
    if(record.__type == 'section'){
       record2 = $.extend({}, record, {'__type':'field'});
       td.attr('colSpan', grid.options.columns.length-1);
    } else {
       td.attr('colSpan', 1);
    }
    
    Fenix_Editor.formatterText(text, record2, column, grid, table, tr, td, false);
    
    var name = Fenix_Editor.getName(column, tr);
    
    var title2name = function(v){
        val = Fenix.stripAccents(v).replace(new RegExp(/\s/g), "_");
        val = val.replace(/\_de\_/g, '_');
        val = val.replace(/\_do\_/g, '_');
        val = val.replace(/\_da\_/g, '_');
        val = val.replace(/\_e\_/g, '_');
        val = val.replace(/([\_]+)$/g, '');
        val = val.replace(/^([\_]+)/g, '');
        val = val.replace(/\-/g, '');
        val = val.replace(/([^0-9A-Za-z\s\_]+)/g, '');
        return val;
    }
    
    $('input', td).on('focus', function(){
        var name = $(document.getElementById( this.name.replace('title', 'name') ));
        var val = title2name( this.value.toString().toLowerCase() );
        if(val != name.val()){
            $(this).data('__fenixEditorTitle2Name', false);
        } else {
            $(this).data('__fenixEditorTitle2Name', true);
        }
    });
    
    $('input', td).on('keyup', function(){
        if($(this).data('__fenixEditorTitle2Name') == true){
            var name = $(document.getElementById( this.name.replace('title', 'name') ));
            var val = title2name( this.value.toString().toLowerCase() );
            name.val( val );
        }
    });
    
    if(record.__type == 'section'){
        
        record = $.extend({'schema':'', 'field':'', 'unique':''}, record);
        
        for(var i in record){
            if(i != 'name' && i != 'title' && i != '__type'){
                td.append(' <strong>' + i.charAt(0).toUpperCase() + i.substr(1) + '</strong>: ')
                Fenix_Editor.formatterText(record[i], record2, {"name": i}, grid, table, tr, td, false);
            }
        }
        $('input', td).css('width', '10em');
        
        td.append(' ');
        
        if(record.name){
            var button = $('<button class="btn btn-xs btn-primary"><i class="glyphicon glyphicon-plus-sign icon-white" /> Campos</button>');
            var section = record.name;
            button.click(function(){ Fenix_Editor.newField(section); });
            td.append(button);
        }
        
        $('td', tr).css('background-color', '#CAD0E8');
    }
}

Fenix_Editor.formatterText = function(text, record, column, grid, table, tr, td, empty){
    
    if(typeof empty == "undefined"){
        td.empty();
    }
    
    if(record.__type != 'field'){
        td.remove();
        return;
    }
    
    var name = Fenix_Editor.getName(column, tr);
    var input = $('<input type="text" id="'+name+'" name="'+name+'">');
    
    input.css({'padding':'0px 0px 0px 3px','margin':'0px','width':'98%'});
    
    input.val(text);
    
    td.append(input);
}

Fenix_Editor.formatterType = function(text, record, column, grid, table, tr, td){
    
    td.empty();
    
    if(record.__type != 'field'){
        td.remove();
        return;
    }
    
    var name = Fenix_Editor.getName(column, tr);
    var select = $('<select id="'+name+'" name="'+name+'">');
    
    select.css({'padding':'0px 0px 0px 3px','margin':'0px','width':'98%', 'height':'18px'});
    
    var option = $('<option value=""> </option>');
    select.append(option);
    
    select.on('change', function(){
        var that = this;
        window.setTimeout(function(){
            var tr = $(that.parentNode.parentNode);
            
            var list = $('input', tr).not("[name*='__type']").not("[id*='form']");
            
            // Oculta campos irrelevantes nos tipos separator e tab
            if(that.value == 'separator' || that.value == 'tab'){
                list.not("[id*='name']").not("[id*='title']").css('display', 'none');

                list.filter("[type='checkbox']").prop('checked', false);  
                list.filter("[type='radio']").not("[id*='form']").prop('checked', false);
                list.not("[id*='name']").not("[id*='title']").val('');
                
                $('td', tr).css('background-color', '#CAD0E8');
            } else {
                list.css('display', '');
                
                $('td', tr).css('background-color', '');
            }

            // Só mostra os campos Table, Key e Field, Depends, DependsKey para campos do tipo fk
            var fk = list.filter("[id*='table'],[id*='key'],[id*='field'],[id*='depends'],[id*='dependsKey'],[id^='fk']");
            if(that.value == 'fk'){
                fk.css('display', '');
            } else {
                fk.val('');
                fk.css('display', 'none');
            }
            
            // Só mostra o campo insert para os campos do tipo fk ou select_text
            if(that.value == 'fk' || that.value == 'select_text'){
                list.filter("[id^='insert']").css('display', '');
                list.filter("[id^='remote']").css('display', '');
            } else {
                list.filter("[id^='insert']").css('display', 'none');                
                list.filter("[id^='remote']").css('display', 'none');                
            }
            
            // Só mostra o campo remote para campos do tipo fk
            if(that.value == 'fk'){
                list.filter("[id^='remote']").css('display', '');
            } else {                
                list.filter("[id^='remote']").css('display', 'none');                
            }
        }, 200);
    });
    
    for(var i in Fenix_Editor.types){
        var type = Fenix_Editor.types[i];
        var option = $('<option value="'+type.name+'">'+type.name+'</option>');
        if(record[column.name] == type.name){
            option.attr('selected', true);
        }
        select.append(option);
    }
    
    td.append(select);
    
    select.trigger('change');
}

Fenix_Editor.formatterRadio = function(text, record, column, grid, table, tr, td){
    
    td.empty();
    
    td.css("white-space", "nowrap");
    
    if(record.__type != 'field'){
        td.remove();
        return;
    }
    
    var name = Fenix_Editor.getName(column, tr);
    var radiogroup = $('<radiogroup>');
    
    if(!record[column.name]){
        if(column.name == 'grid'){
            record[column.name] = 'v';
        } else {
            record[column.name] = 'e';
        }
    }
    
    for(var i in {'e':'e', 'v':'v', 'n':'n'}){
        
        var item = $('<input type="radio" name="'+name+'" id="'+name+'" value="'+i+'"></input>');
        if(i != 'n'){
            item.css( {'margin-right':'3px'} );
        }
        if(record[column.name] == i){
            item.attr('checked', true);
        }
        radiogroup.append( item );
    }
    
    td.append(radiogroup);
}
    
Fenix_Editor.formatterCheck = function(text, record, column, grid, table, tr, td){
    
    td.empty();
    
    if(record.__type != 'field'){
        td.remove();
        return;
    }
    
    var name = Fenix_Editor.getName(column, tr);
    var checkbox = $('<input type="checkbox" name="'+name+'" id="'+name+'" value="1"></checkbox>');
    
    if(record[column.name] == '1'){
        checkbox.attr('checked', true);
    }
    
    td.append(checkbox);
}
    
Fenix_Editor.save = function(){
    Fenix.showLoading();
    
    var success = function(data, textStatus, xhr){
        console.log(data);
        Fenix.closeLoading();
        if(Fenix_Editor.modelCreate == true){
            window.location = window.location;
        } else {
            Fenix.alertHeader('Salvamento realizado com sucesso.', 3000);
        }
    }
    
    var params = $('select, input, radio').serialize();
    
    $.post( 'save', params, success );
}
    
Fenix_Editor.newSection = function(){
    var url = Fenix_Model.grids.grid('getUrl');
    
    url = url.replace(/newSection\=([.]*)/g, "");
    url += "&newSection=" + prompt('Número?');
    
    Fenix_Model.grids.grid('setUrl', url);
    
    Fenix_Model.grids.grid('load');
}
    
Fenix_Editor.newField = function(section){
    var url = Fenix_Model.grids.grid('getUrl');
    
    url = url.replace(/newField\=([^\=]*)/g, "");
    url = url.replace(/section\=([^\=]*)/g, "");
    
    url += "&newField=" + prompt('Número?');
    url += "&section=" + section;
    
    Fenix_Model.grids.grid('setUrl', url);
    
    Fenix_Model.grids.grid('load');
}
    
Fenix_Editor.load = function(){
    Fenix.showLoading();
    
    var success = function(data, textStatus, xhr){
        Fenix.closeLoading();
        if(data != "1"){
            Fenix._internals.ajaxError(null, xhr, null, data);
        } else {
            Fenix.alertHeader('Carregamento realizado com sucesso.', 3000);
        }
    }
    
    var params = "model=" + Fenix_Editor.model;
    
    $.get( 'load', params, success );
}
    
Fenix_Editor["delete"] = function(){
    
    var yes = function(){
        Fenix.showLoading();
        
        var success = function(data, textStatus, xhr){
            Fenix.closeLoading();
            if(data != "1"){
                Fenix._internals.ajaxError(null, xhr, null, data);
            } else {
                Fenix.alertHeader('Remoção realizada com sucesso.', 3000);
            }
        }
        
        var params = "model=" + Fenix_Editor.model;
        
        $.get( 'delete', params, success );
    }
    
    Fenix.confirm('Excluir Modelo?', 'Deseja realmente excluir esse modelo e todos seus dados?', yes, 'btn-danger');
    
}

Fenix_Editor.view = function(){
    var url = Fenix.getBaseUrl() + 'fenix/model/?_model=' + $('input[name^="schema"]')[0].value + "." + $('input[name^="name"]')[0].value;
    window.open(url);
}


