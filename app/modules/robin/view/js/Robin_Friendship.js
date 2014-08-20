// @include "/fenix/app/fenix/view/js/Fenix.js"
// @include "/fenix/app/modules/sgp/view/js/Sgp_Model.js"

if(typeof Robin_Friendship == "undefined"){
    
	Robin_Friendship = {
	    STATUS_PENDENTE: 1,
	    STATUS_ACEITO: 2,
	    STATUS_NEGADO: 3,
	    STATUS_BLOQUEADO: 4,
	    grid : {}
	};
    
}

Robin_Friendship.formatterPicture = function(text, record, column, grid, table, tr, td) {
	
	var thumbnail = $('<img>');
    if(record.user_picture){
		thumbnail.attr('src', Fenix.getBaseUrl() + 'user/download?' + JSON.parse(record.user_picture).hash + '&w=60');
    } else {
    	thumbnail.attr('src', 'https://i.imgur.com/NT01cTg.png');
    }
    thumbnail.css('border-radius', '3px');
    thumbnail.css('width', '60px');

    return thumbnail;
}

Robin_Friendship.formatterName = function(text, record, column, grid, table, tr, td) {

	tr.css('cursor', 'pointer');
	tr.click(function(){
		window.location = Fenix.getBaseUrl() + 'user/profile?id=' + record.id_user;
	});

	return record.user_name;
}


Robin_Friendship.formatterStatus = function(text, record, column, grid, table, tr, td) {
	
	if(record.status == Robin_Friendship.STATUS_PENDENTE){
		text = '<span class="label label-warning arrowed-in">Pedido de amizade pendente</span>';
	} else if(record.status == Robin_Friendship.STATUS_ACEITO){
		text = '<span class="label label-success arrowed-in">Amigos</span>';
	} else if(record.status == Robin_Friendship.STATUS_NEGADO){
		
		if(record.id_user_2 && record.id_user_1 && record.id_user_1 == record.id_user_identity){
			text = '<span class="label label-warning arrowed-in">Pedido de amizade pendente</span>';
		} else {
			text = '<span class="label label-danger arrowed-in">Pedido de amizade negado</span>';
		}
	}
	
	return text;
}

Robin_Friendship.accept = function(record){
	
	var id = (record.friendship_id ? record.friendship_id : record.id);
	
	$.get(Fenix.getBaseUrl() + 'friendship/accept?id='+id, function(){
		if(Fenix_Model.grids.grid){
			Fenix_Model.grids.grid('load');	
		} else {
			window.location.reload(true);
		}
    });
}

Robin_Friendship.cancel = function(record){
	
	var id = (record.friendship_id ? record.friendship_id : record.id);
	
	$.get(Fenix.getBaseUrl() + 'friendship/cancel?id='+id, function(){
		if(Fenix_Model.grids.grid){
			Fenix_Model.grids.grid('load');	
		} else {
			window.location.reload(true);
		}
	});
}

Robin_Friendship.refuse = function(record){
	
	var id = (record.friendship_id ? record.friendship_id : record.id);
	
	$.get(Fenix.getBaseUrl() + 'friendship/refuse?id='+id, function(){
		if(Fenix_Model.grids.grid){
			Fenix_Model.grids.grid('load');	
		} else {
			window.location.reload(true);
		}
	});
}

Robin_Friendship.unfriend = function(record){
	
	var id = (record.friendship_id ? record.friendship_id : record.id);
	
	Fenix.confirm('Excluir Amigo', 'Deseja realmente excluir esse amigo?', function(){
        
	    $.get(Fenix.getBaseUrl() + 'friendship/unfriend?id='+id, function(data){
	    	if(Fenix_Model.grids.grid){
				Fenix_Model.grids.grid('load');	
			} else {
				window.location.reload(true);
			}
	    });
    });
}

Robin_Friendship.buttonsFormatter = function(text, record, column, grid, table, tr, td){
    var removeButtons = [];
    
    if(record.id_user_1){
    	removeButtons.push("Adicionar como amigo");
    }
    
    if(record.status == Robin_Friendship.STATUS_ACEITO){
    	removeButtons.push("Aceitar pedido de amizade");
    	removeButtons.push("Recusar pedido de amizade");
    	removeButtons.push("Deletar pedido de amizade");
    
    } else {
    	removeButtons.push("Remover amizade");
    
    	if( record.id_user_identity != record.id_user_1 ){
        	
        	if(record.status == Robin_Friendship.STATUS_NEGADO){
        		removeButtons.push("Recusar pedido de amizade");
        	} else {
        		removeButtons.push("Deletar pedido de amizade");
        	}
        }
    	
    	if( record.id_user_identity != record.id_user_2 ){
        	removeButtons.push("Aceitar pedido de amizade");
        	removeButtons.push("Recusar pedido de amizade");
        }
    }
    
    Robin_Misc.buttonsFormatter(text, record, column, grid, table, tr, td, removeButtons);
        
    $('th:contains(Ações)', Fenix_Model.grids.last()).css('width', '20em');
}

Robin_Friendship.buttonsGoalFormatter = function(text, record, column, grid, table, tr, td){
    var removeButtons = [];

    if( record.id_goal_user ){
    	removeButtons.push("Compartilhar meta");
    } else {
    	removeButtons.push("Remover");    	
    }
    
    Robin_Misc.buttonsFormatter(text, record, column, grid, table, tr, td, removeButtons);
        
    $('th:contains(Ações)', Fenix_Model.grids.last()).css('width', '20em');
}