//@include "/fenix/app/fenix/view/js/Fenix.js"
//@include "/fenix/app/modules/sgp/view/js/Sgp_Model.js"

Robin_Team = {};

Robin_Team.formatterPicture = function(text, record, column, grid, table, tr, td) {
    
	var thumbnail = $('<img>');
    if(record.picture){
		thumbnail.attr('src', Fenix.getBaseUrl() + 'team/download?' + JSON.parse(record.picture).hash + '&w=60');
    } else {
    	thumbnail.attr('src', 'https://i.imgur.com/iGSq78u.png');
    }
    thumbnail.css('border-radius', '3px');
    thumbnail.css('width', '60px');

	return thumbnail;
}

Robin_Team.formatterName = function(text, record, column, grid, table, tr, td) {

	tr.css('cursor', 'pointer');
	tr.click(function(){
		Fenix_Model.page(Fenix.getBaseUrl()+'team/record?id='+record.id, record.name, 600);
	});

	return text;
}