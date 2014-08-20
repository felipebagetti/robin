window.ParsleyConfig = window.ParsleyConfig || {};

(function ($) {
  window.ParsleyConfig = $.extend( true, {}, window.ParsleyConfig, {
    excluded: '',
    listeners: {
        onFieldError: function ( elem, constraints, ParsleyField ) {
            var form = Fenix_Model.forms.has( elem );
            var field = form.form('getField', elem.attr('name') );
            // Campos do tipo Data/Hora possuem um input hidden que cont�m o valor preenchido
            // nos dois campos adicionais e por isso a valida��o n�o � feita diretamente nele
            if(field && field.type == 'datetime'){
                // Faz a marca��o de erro nos dois elementos: data e hora
                $('#'+field.name+'_date').addClass('parsley-validated parsley-error');
                $('#'+field.name+'_time').addClass('parsley-validated parsley-error');
            }
        },
        onFieldSuccess: function ( elem, constraints, ParsleyField ) {
            var form = Fenix_Model.forms.has( elem );
            var field = form.form('getField', elem.attr('name') );
            // Campos do tipo Data/Hora possuem um input hidden que cont�m o valor preenchido
            // nos dois campos adicionais e por isso a valida��o n�o � feita diretamente nele
            if(field && field.type == 'datetime'){
                // Faz a marca��o de erro nos dois elementos: data e hora
                $('#'+field.name+'_date').removeClass('parsley-error');
                $('#'+field.name+'_time').removeClass('parsley-error');
            }
        }
    },
    messages: {
        defaultMessage: "Este valor parece estar inv�lido."
      , type: {
            email:      "Este valor deve ser um e-mail v�lido."
          , url:        "Este valor deve ser uma URL v�lida."
          , urlstrict:  "Este valor deve ser uma URL v�lida."
          , number:     "Este valor deve ser um n�mero v�lido."
          , digits:     "Este valor deve ser um d�gito v�lido."
          , dateIso:    "Este valor deve ser uma data v�lida (YYYY-MM-DD)."
          , alphanum:   "Este valor deve ser alfanum�rico."
        }
      , notnull:        "Este valor n�o deve ser nulo."
      , notblank:       "Este valor n�o deve ser branco."
      , required:       "Este valor � obrigat�rio."
      , regexp:         "Este valor parece estar inv�lido."
      , min:            "Este valor deve ser maior que %s."
      , max:            "Este valor deve ser menor que %s."
      , range:          "Este valor deve estar entre %s e %s."
      , minlength:      "Este valor � muito pequeno. Ele deve ter %s caracteres ou mais."
      , maxlength:      "Este valor � muito grande. Ele deve ter %s caracteres ou menos."
      , rangelength:    "O tamanho deste valor � inv�lido. Ele deve possuir entre %s e %s caracteres."
      , equalto:        "Este valor deve ser o mesmo."
      , minwords:       "Este valor deve possuir no m�nimo %s palavras."
      , maxwords:       "Este valor deve possuir no m�ximo %s palavras."
      , rangewords:     "Este valor deve possuir entre %s e %s palavras."
    }
  });
}(window.jQuery || window.Zepto));