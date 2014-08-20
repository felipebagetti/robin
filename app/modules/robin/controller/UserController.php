<?php

require_once __DIR__.'/ModelController.php';

class UserController extends ModelController {

	/**
	 * (non-PHPdoc)
	 * @see ModelController::_getGrid()
	 */
	protected function _getGrid(){
	
		$grid = parent::_getGrid();
	
		$grid->setOption(Grid::OPTION_TITLE, 'Usuários');
		$grid->setOption(Grid::OPTION_EDIT_ON_LAYER, true);
		$grid->setOption(Grid::OPTION_SORT_COL, 'name');
		$grid->setOption(Grid::OPTION_SORT_DIR, 'ASC');
		$grid->setOption(Grid::OPTION_PAGINATION_PER_PAGE, 5);
		$grid->setOption(Grid::OPTION_BUTTONS_FORMATTER, 'Robin_Friendship.buttonsFormatter');
	
		$grid->addJs('robin/Robin_User.js');
		$grid->addJs('robin/Robin_Friendship.js');
	
		$pictureColumn = $grid->getColumn('picture');
		if ( !empty($pictureColumn) ) {
			$grid->setColumnAttribute('picture', GridColumn::FORMATTER, 'Robin_User.formatterPicture');
			$grid->setColumnAttribute('picture', GridColumn::STYLE_HEADER, "center");
			$grid->setColumnAttribute('picture', GridColumn::STYLE_CONTENT, "center");
			$grid->setColumnAttribute('picture', GridColumn::WIDTH, "8em");
		}
	
		$grid->setColumnAttribute('name', GridColumn::FORMATTER, 'Robin_User.formatterName');
		
		$grid->addColumnAfter('location', 'status', 'Status', 'Robin_Friendship.formatterStatus', 'center', 'center');
	
		$grid->deleteButton('Alterar');
		$grid->deleteButton('Excluir');
	
		$grid->deleteButtonPage('Novo Registro');
	
		$grid->addButton('Adicionar como amigo', "Robin_User.requestFriendship", 'icon-plus');
		$grid->addButton('Aceitar pedido de amizade', "Robin_Friendship.accept", 'icon-thumbs-up');
		$grid->addButton('Recusar pedido de amizade', "Robin_Friendship.refuse", 'icon-thumbs-down');
		$grid->addButton('Deletar pedido de amizade', "Robin_Friendship.cancel", 'icon-ban');
		$grid->addButton('Remover amizade', "Robin_Friendship.unfriend", 'icon-times');
	
		return $grid;
	}
	
	/**
	 * Desativa os subgrids
	 * (non-PHPdoc)
	 * @see Fenix_ModelController::_getFormAddGrid()
	 */
	protected function _getFormPrepareSubsections(Form $form){}
	
	public function requestFriendshipAction(){
	
		$friendship = Model::factory('friendship');
	
		$item = array (
				'id_user_1' => Zend_Auth::getInstance()->getIdentity()->id,
				'id_user_2' => $_REQUEST['id']
		);
	
		$ret = $friendship->insert($item);
	
		die($ret);
	}
	
	protected function _getForm(){
		
		if ( isset($_REQUEST['id']) && (int)$_REQUEST['id'] != Zend_Auth::getInstance()->getIdentity()->id) {
			throw new Fenix_Exception('Permissão negada.');
		}
		
		$form = parent::_getForm();
		
		$form->setOption(Form::OPTION_TITLE, 'Editar Meu Perfil');
		
		$form->deleteField('password');
		
		$form->deleteButtonPage('Excluir');
		$form->deleteButtonPage('Voltar');
		
		return $form;
	}
	
	public function changePasswordAction(){
	
		$user = Zend_Auth::getInstance()->getIdentity();
	
		$this->_setFormOption(Form::OPTION_TITLE, 'Trocar Senha');
		$this->_setFormOption(Form::OPTION_RECORD, $this->_getModel()->select($user->id));
		$this->_setFormOption(Form::OPTION_ACTION, 'change-password-save');
		$this->_setFormOption(Form::OPTION_LABEL_COLUMN_WIDTH, '13em');
	
		$form = parent::_getForm();
		
		foreach($form->getFields() as $field){
		    if( !in_array( $field->getName(), array('name', 'login', 'email') ) ){
		        $form->deleteField( $field->getName() );
		    }
		}
	
		$record = $form->getOption(Form::OPTION_RECORD);
	
		$form->addOnload("$('<tr><td colspan=\'2\'><strong>Escolha uma nova senha com pelo menos 6 caracteres:</strong></td></tr>').insertBefore( $('#password_new').closest('tr') );");
		$form->addOnload('$("#password").attr( "parsley-minlength", "6");');
		$form->addOnload('$("#password_new").attr( "parsley-minlength", "6");');
		$form->addOnload('$("#password_confirm").attr( "parsley-minlength", "6");');
		$form->addOnload("$('#password').focus();");
	
		$form->setFieldAttribute('name', FormField::FORM, 'v');
		$form->setFieldAttribute('login', FormField::FORM, 'v');
		$form->setFieldAttribute('email', FormField::FORM, 'v');
		$form->setFieldAttribute('email', FormField::TITLE, 'Email');
	
		iF($record['login'] == $record['email']){
			$form->deleteField('login');
		}
		
		$form->addField( FormField::factory('password', ModelConfig::PASSWORD)->setTitle('Senha Atual')->setRequired(true) );
		$form->addField( FormField::factory('password_new', ModelConfig::PASSWORD)->setTitle('Nova Senha')->setRequired(true) );
		$form->addField( FormField::factory('password_confirm', ModelConfig::PASSWORD)->setTitle('Confirmar Nova Senha')->setRequired(true) );
	
		$form->render();
	}
	
	public function changePasswordSaveAction(){
	
		$user = $this->_getModel()->select( Zend_Auth::getInstance()->getIdentity()->id, false );
	
		if($user['password'] === $this->_getModel()->passwordHash($user['login'], $_REQUEST['password'])){
	
			if($_REQUEST['password_new'] === $_REQUEST['password_confirm']){
				$passwordHash = $this->_getModel()->passwordHash( $user['login'], $_REQUEST['password_new'] );
				$this->_getModel()->updatePassword( $user['login'], $passwordHash );
	
				die("Ok");
			} else {
				throw new Fenix_Exception("A confirmação da nova senha não está igual à nova senha.");
			}
	
		}
	
		throw new Fenix_Exception("Senha atual incorreta");
	}
	
	protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
		 
		$identity = Zend_Auth::getInstance()->getIdentity()->id;
		
		// Lista dos campos que será personalizada
		$cols = array();
		
		// Só personaliza a lista de campos no caso de uma consulta aos dados
		// da seção principal, para otimizar o carregamento da grid
		if( !$this->_getModel()->isSubsection() ){
		
			$cols[] = 'id';
			
			$fieldsRemove = array(
					'password',
					'email',
					'phone_number',
					'login'
			);
			foreach( $this->_getModel()->getFields() as $field ){
				if( $field->getNameDatabase() && !in_array($field->getName(), $fieldsRemove) ){
					$cols[] = $field->getName();
				}
			}
		
		}
		
		$select = $this->_getModel()->prepareSelect($cols, null, $sortCol, $sortDir, $limit, $offset);
		
		$cond = "(id_user_1 = user.id OR id_user_2 = user.id) AND (id_user_2 = $identity OR id_user_1 = $identity)";
		$select->joinLeft('friendship', $cond, array('friendship_id' => 'friendship.id', 'id_user_1' => 'id_user_1', 'id_user_2' => 'id_user_2', 'status' => 'friendship.status'), 'robin');
		$select->where('robin.user.id != ?', $identity);
		
		// Só executa o _dataSearch caso a busca esteja ativdada para essa seção
		if($query !== null && $this->_searchActivated($this->_getModel()->getSection()->getName())){
			$this->_dataSearch($select, $query );
		}
		
		// Caso haja filtros no request
		if($filters !== null){
			$this->_dataFilters($select, $filters);
		}
		
		return $select;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Fenix_ModelController::_dataPrepare()
	 */
	protected function _dataPrepare($data){
	
		$idUser = Zend_Auth::getInstance()->getIdentity()->id;
	
		$data = parent::_dataPrepare($data);
	
		foreach ($data as $key => $value) {
			$data[$key]['id_user_identity'] = $idUser;
		}
	
		return $data;
	}
	
	public function profileAction(){
	
		//user
		$idUser = Zend_Auth::getInstance()->getIdentity()->id;

		if( (int)$idUser === (int)$_REQUEST['id']){
			$user = $this->_getModel()->select($idUser);
		} else {
			$select = $this->_data();
			$select->where('user.id = ?', $_REQUEST['id']);
			$result = $select->query()->fetchAll();
			
			$result[0]['id_user_identity'] = $idUser;
			
			$user = $result[0];
		}
		
		//append => user[friends]
		$cols = array();
		$cols['id_user'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$_REQUEST['id']} THEN id_user_2 ELSE id_user_1 END");
		$cols['user_name'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$_REQUEST['id']} THEN user_user_2.name ELSE user_user_1.name END");
		$cols['user_picture'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$_REQUEST['id']} THEN user_user_2.picture ELSE user_user_1.picture END");

		$selectF = Model::factory('friendship')->prepareSelect();
		$selectF->reset(Zend_Db_Select::WHERE);
		$selectF->where('friendship.status = ?', Friendship::ACEITO);
		$selectF->where("id_user_1 = {$_REQUEST['id']} OR id_user_2 = {$_REQUEST['id']}");

		$selectF->columns($cols);
		
		
		
		$resultF = $selectF->query()->fetchAll();
		
		$user['url'] = Util::getBaseUrl();
		$user['friends'] = $resultF;
		
		//prepare view
		$view = $this->_prepareAce();
		
		$view->addPageJs( new Form() );
		
		$view->addJs('robin/Robin_User.js');
		$view->addJs('robin/Robin_Friendship.js');
		$view->addJs('robin/Robin_Profile.js');
	
		$view->include = "profile.html.php";
	
		$view->addOnload('Robin_Profile.onLoad('.json_encode(Util::utf8_encode($user)).');');
		
		$view->render();
	}
	
	public function imageAction(){
	    $user = $this->_getModel()->select(Zend_Auth::getInstance()->getIdentity()->id);
	    
	    if (!empty($user['picture'])) {
		    $picture = json_decode($user['picture']);
		    header("Location: download?".$picture->hash."&w=".(isset($_REQUEST['w']) ? $_REQUEST['w'] : '50'));
	    } else {
		    header("Location: https://i.imgur.com/NT01cTg.png");
	    }
	    
	    die();
	}
}
