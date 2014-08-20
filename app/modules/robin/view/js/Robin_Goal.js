//@include "/fenix/app/fenix/view/js/Fenix.js"
//@include "/fenix/app/modules/sgp/view/js/Sgp_Model.js"

Robin_Goal = {};

Robin_Goal.share = function(record){
	
	var id_goal = Fenix_Model.forms.last().form('getRecord').id;
	
	$.get(Fenix.getBaseUrl() + 'goal/share?id_user='+record.id_user+'&id_goal='+id_goal, function(){
		if(Fenix_Model.grids.grid){
			Fenix_Model.grids.grid('load');	
		} else {
			window.location.reload(true);
		}
	});
}

Robin_Goal.deleteShare = function(record){
	
	Fenix.confirm('Remover compartilhamento', 'Remover amigo da meta?', function(){
		
		$.get(Fenix.getBaseUrl() + 'goal/delete-share?id_goal_user='+record.id_goal_user, function(data){
			if(Fenix_Model.grids.grid){
				Fenix_Model.grids.grid('load');	
			} else {
				window.location.reload(true);
			}
		});
	});
}

Robin_Goal.leaveGoal = function(id){
	
	Fenix.confirm('Sair da meta', 'Sair da meta?', function(){
	    $.get(Fenix.getBaseUrl() + 'goal/leave-goal?id='+id, function(data){
				window.location.reload(true);
	    });
    });
}

Robin_Goal.formatterName = function(text, record, column, grid, table, tr, td) {

	tr.css('cursor', 'pointer');
	tr.click(function(){
		Fenix_Model.page(Fenix.getBaseUrl()+'goal/record?id='+record.id, record.name, 600);
	});

	return text;
}

Robin_Goal.calendarSave = function(calEvent, revertFunc){
    var params = [ 
        'id='+calEvent.id,
        'start='+calEvent.start.format()
    ];
    
    $.get('calendar-save', params.join("&"), function(data){
        Fenix.alertHeader('Evento atualizado com sucesso.', 3000);
    }).fail( typeof revertFunc != "undefined" ? revertFunc : null );
    
}
//Baixa:1,Normal:2,Alta:3,Urgente:4
Robin_Goal.calendarClassName = function(priority){
    if(priority == 1){
        className = 'label-grey';
    } else if (priority == 2){
        className = 'label-info';
    } else if (priority == 3){
        className = 'label-yellow';
    } else if(priority == 4){
        className = 'label-danger';
    }
    return className;
}

Robin_Goal.submitCallback = function(page, data, xhr){

    if( $("#calendar").length > 0 ){
        
        var calEventEdit = $("#calendar").data('_calEventEdit'); 
        
        var title = $('#name').val();
        var start = $('#due_datetime').val().replace(/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/, "$3-$2-$1").replace(" ", "T");
        var className = Robin_Goal.calendarClassName( $('#priority').val() );
        
        if(start.length == 16){
            start += ":00Z";
        }
        
        if( !calEventEdit ){
            
            // Só adiciona no calendário se houver data definida
            if(start){
                var calEvent = {id: data, title: title, start: start, className: className};
	            
			    $("#calendar").fullCalendar('renderEvent',
			                    calEvent,
			                    true // make the event "stick"
			                );
            }
            
        } else {
            
            // Exclusão deregistro
            if(!data){
                $("#calendar").fullCalendar('removeEvents', calEventEdit.id);
                $('.modal').modal('hide');
            }
            // Alteração de registro
            else {
                
                // Permanece com data
                if(start) {
	                calEventEdit.title = title;
		            calEventEdit.start = start;
		            calEventEdit.className = className;
		            
		            $("#calendar").fullCalendar('updateEvent', calEventEdit);
                }
                // Data foi removida
                else {
                    $("#calendar").fullCalendar('removeEvents', calEventEdit.id);
                    $('.modal').modal('hide');
                }
                
            }
            
            $("#calendar").data('_calEventEdit', false);
        }
        
        Fenix.layerClose();
        
    } else {
        Fenix_Model.submitCallback(page);
    }
    
    $.getJSON('without-date', Robin_Goal.externalEvents);
}

Robin_Goal.record = function(){
    Fenix_Model.page('record', 'Nova Meta', 750);
}


Robin_Goal.externalEvents = function(events){
    
    $('#external-events').empty();
    
    for( var i = 0; i < events.length ; i++ ){
        
        var event = events[i];
        var className = Robin_Goal.calendarClassName(event.priority);
        
        $('#external-events').append(
            $('<div class="external-event '+className+'" data-class="'+className+'" data-id="'+event.id+'"><i class="icon-arrows"></i>'+event.title+'</div>')
                .on('click', function(id){
                    return function(){ Fenix_Model.page('record?id='+id, 'Dados da Meta', 850); };
                }(event.id) )
                .css('cursor', 'pointer')
        )
    }
    
    /* initialize the external events
    -----------------------------------------------------------------*/

    $('#external-events div.external-event').each(function() {

        // create an Event Object (http://arshaw.com/fullcalendar/docs/event_data/Event_Object/)
        // it doesn't need to have a start or end
        var eventObject = {
            title: $.trim($(this).text()) // use the element's text as the event title
        };

        // store the Event Object in the DOM element so we can get to it later
        $(this).data('eventObject', eventObject);

        $(this).off('draggable');
        
        // make the event draggable using jQuery UI
        $(this).draggable({
            zIndex: 999,
            revert: true,      // will cause the event to go back to its
            revertDuration: 0  //  original position after the drag
        });
        
    });
    
}

Robin_Goal.showCalendar = function(eventos){
    
    jQuery(function($) {

    /* initialize the calendar
    -----------------------------------------------------------------*/

    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();
    
    $.each(eventos, function(k, event){
        eventos[k].className = Robin_Goal.calendarClassName(event.priority);
    });

    var calendar = $('#calendar').fullCalendar({
        
         buttonHtml: {
            prev: '<i class="ace-icon fa fa-chevron-left"></i>',
            next: '<i class="ace-icon fa fa-chevron-right"></i>'
        },
        
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        
        // Metas pré-carregadas
        events: eventos,
        
        eventDurationEditable: false,
        defaultTimedEventDuration: '01:00:00',
        
        eventResize: function(event, delta, revertFunc) {
            revertFunc();
        },
        
        editable: true,
        droppable: true, // this allows things to be dropped onto the calendar !!!
        drop: function(date, allDay, ui, view) { // this function is called when something is dropped
            
            // retrieve the dropped element's stored Event Object
            var originalEventObject = $(this).data('eventObject');
            var $extraEventClass = $(this).attr('data-class');
            
            // we need to copy it, so that multiple events don't have a reference to the same object
            var copiedEventObject = $.extend({}, originalEventObject);
            
            // assign it the date that was reported
            copiedEventObject.id = $(this).attr('data-id');
            copiedEventObject.start = date;
            copiedEventObject.allDay = allDay;
            
            if($extraEventClass) copiedEventObject['className'] = [$extraEventClass];
            
            // render the event on the calendar
            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
            $('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
            
            // is the "remove after drop" checkbox checked?
            if (true || $('#drop-remove').is(':checked')) {
                // if so, remove the element from the "Draggable Events" list
                $(this).remove();
            }
            
            Robin_Goal.calendarSave(copiedEventObject);
            
        }
        ,
        selectable: true,
        selectHelper: true,
        select: function(start, end, allDay) {
            Fenix_Model.page('record?start='+start.format(), 'Nova Meta', 750);
            calendar.fullCalendar('unselect');
        }
        ,
        eventDrop: function( calEvent, delta, revertFunc, jsEvent, ui, view ){
            Robin_Goal.calendarSave(calEvent, revertFunc);
        }
        ,
        eventClick: function(calEvent, jsEvent, view) {
            $("#calendar").data('_calEventEdit', calEvent);
            Fenix_Model.page('record?id='+calEvent.id, 'Dados da Meta', 850);
        }
        
    });

    });   
}