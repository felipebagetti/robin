
/**
 * Select2 Brazilian Portuguese translation
 */
(function ($) {
    "use strict";
    
    $.extend($.fn.select2.defaults, {
        initSelection : function (element, callback) {
            var data = {id: element.val(), text: element.val()};
            callback(data);
        },
        matcher : function(term, text) { return text.stripAccents().toLowerCase().indexOf(term.stripAccents().toLowerCase())!=-1; },
        placeholder: "",
        formatNoMatches: function () { return "Nenhum resultado encontrado"; },
        formatInputTooShort: function (input, min) { var n = min - input.length; return "Informe " + n + " caracter" + (n == 1? "" : "es"); },
        formatInputTooLong: function (input, max) { var n = input.length - max; return "Apague " + n + " caracter" + (n == 1? "" : "es"); },
        formatSelectionTooBig: function (limit) { return "Só é possível selecionar " + limit + " elemento" + (limit == 1 ? "" : "s"); },
        formatLoadMore: function (pageNumber) { return "Carregando mais resultados..."; },
        formatSearching: function () { return "Buscando..."; }
    });
    
    $.fn.valChange = function (v) {
        var el = $(this);
        
        el.val(v);
        // Não há o elemento
        if(el.val() != v){
            // Tenta localizar uma option com o valor v
            v = $('option:contains("'+v+'")', el ).val();
            if(v){
                el.val(v);
            }
        }
        
        if(el.select2){
            return el.select2("val", v, true);
        } else {
            return el.trigger("change");
        }
        
    }
    
})(jQuery);


