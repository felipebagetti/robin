//@include "/fenix/app/fenix/view/js/Fenix.js"
//@include "/fenix/app/modules/sgp/view/js/Sgp_Model.js"

Robin_Profile = {};

Robin_Profile.onLoad = function(record){
	
	//é o proprio perfil do usuario -> botão Editar meu perfil
	//!amizade -> adicionar amigo
	//amigos -> remover amigo
	//user_1 == identity & !Aceito -> Cancelar pedido de amizade
	//user_2 == identity & amizade & !Aceito -> Aceitar pedido de amizade
	//Nas ações de click tem que ser function(){} se não a função é executada e dá erro no Fenix.getBaseUrl
	if(typeof record.id_user_1 == 'undefined'){
		$('#friendship_flag').css('display', 'none');

		$('#btn-friendship').html('Editar meu perfil');
		$('#btn-friendship').addClass('btn-primary');
		
		$('#btn-friendship').click(function(){
			window.location = record.url + "user/record?id="+record.id;
		});
	} else if(!record.id_user_1){
		$('#friendship_flag').css('display', 'none');

		$('#btn-friendship').html('Adicionar como amigo');
		$('#btn-friendship').addClass('btn-primary');
		
		$('#btn-friendship').click(function(){
			Robin_User.requestFriendship(record);
		});
		
	} else if(record.status == Robin_Friendship.STATUS_ACEITO){
		$('#friendship_status').html('Amigo');
		$('#friendship_icon').addClass('icon-check');
		$('#friendship_flag').addClass('label-success');

		$('#btn-friendship').html('Remover amigo');
		$('#btn-friendship').addClass('btn-danger');

		$('#btn-friendship').click(function(){
			Robin_Friendship.unfriend(record);
		});
		
	} else {
		$('#friendship_status').html('Aguardando resposta');
		$('#friendship_icon').addClass('icon-clock-o');
		$('#friendship_flag').addClass('label-gray');
		
		if(record.id_user_identity == record.id_user_1) {
			$('#btn-friendship').html('Cancelar pedido');
			$('#btn-friendship').addClass('btn-gray');
			
			$('#btn-friendship').click(function(){
				Robin_Friendship.cancel(record);
			});
			
		} else if(record.id_user_identity == record.id_user_2) {
			$('#btn-friendship').html('Aceitar amizade');
			$('#btn-friendship').addClass('btn-success');
			
			$('#btn-friendship').click(function(){
				Robin_Friendship.accept(record);
			});
		}
	}
	if(record.picture){
		$('#user_picture').attr('src', record.url + "user/download?w=219&"+JSON.parse(record.picture).hash );
    } else {
    	$('#user_picture').attr('src', 'https://i.imgur.com/NT01cTg.png');
    }

	$('#user_name_title').html((record.name ? record.name : ' - '));
	$('#user_bio').html((record.bio ? record.bio : ' - '));
	$('#user_location').html((record.location ? record.location : ' - '));
	$('#user_creation_date').html((record.creation_date ? 'Usuário desde ' + record.creation_date : ' - '));


	if(record.creation_date){
		var creation_date = record.creation_date.split(' ')[0];

		var tmp = creation_date.split('-');
		creation_date = tmp[2]+'/'+tmp[1]+'/'+tmp[0];

		$('#user_creation_date').html(creation_date);
	} else {
		$('#user_creation_date').html(' - ');
	}
    
	if(record.website){
        $('#user_website').attr('target', '_blank');
		$('#user_website').attr('href', Robin_Misc.urlAbsoluta(record.website));
		$('#user_website').html(record.website);
	} else {
		$('#user_website').html(' - ');
	}

	if(record.twitter){
		$('#user_twitter').attr('target', '_blank');
		$('#user_twitter').attr('href', 'https://twitter.com/'+record.twitter);
		$('#user_twitter').html('twitter.com/'+record.twitter);
	} else {
		$('#user_twitter').html(' - ');
	}

	if(record.facebook){
        $('#user_facebook').attr('target', '_blank');
		$('#user_facebook').attr('href', 'https://facebook.com/'+record.facebook);
		$('#user_facebook').html('facebook.com/'+record.facebook);
	} else {
		$('#user_facebook').html(' - ');
	}
	
	//Add friends info
	var friends = $('.profile-users');
	var id_user;
	var user_name;
	var picture_hash;
	
	for(var i = 0; i < record.friends.length; i++){
		id_user = record.friends[i].id_user;
		user_name = record.friends[i].user_name;
		
		//divuser = picture + link
		var divuser = $('<div class="user">');
		var linkPicture = $('<a>');
		var img = $('<img>');
		
		linkPicture.attr('href', record.url + "user/profile?id="+id_user);
		
		if(record.friends[i].user_picture){
			img.attr('src', record.url + "user/download?w=60&"+JSON.parse(record.friends[i].user_picture).hash);
		} else {
			img.attr('src', 'https://i.imgur.com/NT01cTg.png');
	    }
		img.css('width', '60px');
		img.css('height', '60px');
		
		linkPicture.append(img);
		divuser.append(linkPicture);
		
		//divbody = name + link
		var divbody = $('<div class="body">');
		var divname = $('<div class="user">');
		var linkName = $('<a>');
		
		linkName.attr('href', record.url + "user/profile?id="+id_user);
		linkName.html(user_name);
		
		divname.append(linkName);
		divbody.append(divname);
		
		//assemble div
		var memberDiv = $('<div class="itemdiv memberdiv">');
		var inlineEl = $('<div class="inline pos-rel">');
		
		inlineEl.append(divuser);
		inlineEl.append(divbody);
		memberDiv.append(inlineEl);
		
		//add friend
		friends.append(memberDiv);
	}
}