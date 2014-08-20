<?php

require_once 'ModelController.php';

class FriendshipController extends ModelController {
	
	/**
	 * (non-PHPdoc)
	 * @see ModelController::_getGrid()
	 */
	protected function _getGrid(){
	
		if (isset($_REQUEST['pending'])) {
			$this->_setGridOption(Grid::OPTION_TITLE, 'Pedidos pendentes');
			$this->_setGridOption(Grid::OPTION_PARAMS, 'pending');
		} else {
			$this->_setGridOption(Grid::OPTION_TITLE, 'Amigos');
		}
		
		$grid = parent::_getGrid();
	
		$grid->setOption(Grid::OPTION_BUTTONS_FORMATTER, 'Robin_Friendship.buttonsFormatter');
		$grid->setOption(Grid::OPTION_SORT_COL, 'friendship.id');
		$grid->setOption(Grid::OPTION_SORT_DIR, 'DESC');
		$grid->setOption(Grid::OPTION_PAGINATION_PER_PAGE, 5);
		
		$grid->addJs('robin/Robin_Friendship.js');

		$grid->setColumnAttribute('status', GridColumn::FORMATTER, 'Robin_Friendship.formatterStatus');
		$grid->setColumnAttribute('status', GridColumn::STYLE_HEADER, 'center');
		$grid->setColumnAttribute('status', GridColumn::STYLE_CONTENT, 'center');
		
		$grid->addColumnAt(0, 'user_picture', 'Foto', 'Robin_Friendship.formatterPicture', 'center', 'center');
		$grid->addColumnAt(1, 'user_name', 'Nome', 'Robin_Friendship.formatterName', 'center', 'center');
		
		$grid->deleteButton('Alterar');
		$grid->deleteButton('Excluir');
		
		$grid->deleteButtonPage('Novo Registro');
		
		$grid->addButton('Aceitar pedido de amizade', "Robin_Friendship.accept", 'icon-thumbs-up');
		$grid->addButton('Recusar pedido de amizade', "Robin_Friendship.refuse", 'icon-thumbs-down');
		$grid->addButton('Deletar pedido de amizade', "Robin_Friendship.cancel", 'icon-ban');
		$grid->addButton('Remover amizade', "Robin_Friendship.unfriend", 'icon-times');
		
		return $grid;
	}
	
	public function acceptAction(){
	
		$ret = $this->_getModel()->accept($_REQUEST['id']);
		die($ret);
	}
	
	public function cancelAction(){
	
		$ret = $this->_getModel()->cancel($_REQUEST['id']);
		die($ret);
	}
	
	public function refuseAction(){
	
		$ret = $this->_getModel()->refuse($_REQUEST['id']);
		die($ret);
	}
	
	public function unfriendAction(){
	
		$ret = $this->_getModel()->unfriend($_REQUEST['id']);
		die($ret);
	}
	
	protected function _data($filters = null, $query = null, $sortCol = null, $sortDir = null, $limit = null, $offset = null){
		
		// Cria o model para permitir que as constantes sejam referenciadas (garante que está incluída a classe)
		Model::factory("friendship");
		
		$idUser = Zend_Auth::getInstance()->getIdentity()->id;
		
		$select = parent::_data($filters, $query, $sortCol, $sortDir, $limit, $offset);
		
		$cols = array();
		$cols['id_user'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$idUser} THEN id_user_2 ELSE id_user_1 END");
		$cols['user_name'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$idUser} THEN user_user_2.name ELSE user_user_1.name END");
		$cols['user_picture'] = new Zend_Db_Expr("CASE WHEN id_user_1 = {$idUser} THEN user_user_2.picture ELSE user_user_1.picture END");

		if (!empty($_REQUEST['id_goal'])) {
			$sql = "(SELECT goal_user.id FROM goal_user WHERE id_user = (CASE WHEN id_user_1 = {$idUser} THEN id_user_2 ELSE id_user_1 END) AND goal_user.id_goal = ". $_REQUEST['id_goal'].")";
			$cols['id_goal_user'] = new Zend_Db_Expr($sql); 
		}
		
		if( isset($_REQUEST['pending']) ){
			$select->where('friendship.status != ?', Friendship::ACEITO);
		} else {
			$select->where('friendship.status = ?', Friendship::ACEITO);
		}

		$select->columns($cols);
		
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
}