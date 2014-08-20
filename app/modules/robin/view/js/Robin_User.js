//@include "/fenix/app/fenix/view/js/Fenix.js"
//@include "/fenix/app/modules/sgp/view/js/Sgp_Model.js"

Robin_User = {};

Robin_User.formatterPicture = function(text, record, column, grid, table, tr, td) {
    
	var thumbnail = $('<img>');
    if(record.picture){
		thumbnail.attr('src', Fenix.getBaseUrl() + 'user/download?' + JSON.parse(record.picture).hash + '&w=60');
    } else {
    	thumbnail.attr('src', 'https://i.imgur.com/NT01cTg.png');
    }
    thumbnail.css('border-radius', '3px');
    thumbnail.css('width', '60px');

	return thumbnail;
}

Robin_User.formatterName = function(text, record, column, grid, table, tr, td) {

	tr.css('cursor', 'pointer');
	tr.click(function(){
		window.location = Fenix.getBaseUrl() + 'user/profile?id=' + record.id;
	});

	return text;
}

Robin_User.requestFriendship = function(record){
	$.get(Fenix.getBaseUrl() + 'user/requestFriendship?id='+record.id, function(){
		if(Fenix_Model.grids.grid){
			Fenix_Model.grids.grid('load');	
		} else {
			window.location.reload(true);
		}
	});
}