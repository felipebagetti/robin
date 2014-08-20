<?php
require_once 'Robin_Model.php';
class Friendship extends Robin_Model {
	const PENDENTE = 1;
	const ACEITO = 2;
	const NEGADO = 3;
	const BLOQUEADO = 4;
	
	/**
	 *
	 * @return Friendship
	 */
	public static function factory($name = "robin.friendship", $section = null) {
		return parent::factory ( $name, $section );
	}
	
	public function prepareSelect($cols = array(), $where = array(), $sortCol = null, $sortDir = null, $count = null, $offset = null) {
		$select = parent::prepareSelect ( $cols, $where, $sortCol, $sortDir, $count, $offset );
		
		//mostrar apenas os registros que o usuário está presente
		$select->where("id_user_1 = ".Zend_Auth::getInstance()->getIdentity()->id." OR id_user_2 = ".Zend_Auth::getInstance()->getIdentity()->id);
		
		return $select;
	}
	
	public function accept($id){
		$item = $this->select($id);
		
		if ($item['id_user_2'] == Zend_Auth::getInstance()->getIdentity()->id) {
			$item['status'] = self::ACEITO;
			return $this->update($item);
		
		} else {
			throw new Fenix_Exception('Erro! Usuário não tem permissão para realizar essa operação.');
		}
	}
	
	public function refuse($id){
		$item = $this->select($id);
		
		if ($item['id_user_2'] == Zend_Auth::getInstance()->getIdentity()->id) {
			$item['status'] = self::NEGADO;
			return $this->update($item);
		
		} else {
			throw new Fenix_Exception('Erro! Usuário não tem permissão para realizar essa operação.');
		}
	}

	public function cancel($id){
		$this->unfriend($id);
	}
	
	public function unfriend($id){
		$item = $this->select($id);
		if ($item['id_user_1'] == Zend_Auth::getInstance()->getIdentity()->id || $item['id_user_2'] == Zend_Auth::getInstance()->getIdentity()->id ) {
			return $this->delete($id);
		} else {
			throw new Fenix_Exception('Erro! Usuário não tem permissão para realizar essa operação.');
		}
	}
	
	public function insert($item){
		
		//check for permission
		if($item['id_user_1'] != Zend_Auth::getInstance()->getIdentity()->id){
			throw new Fenix_Exception('Erro! Usuário não tem permissão para realizar essa operação.');
		}
		//
		
		//check for validity
		if($item['id_user_1'] == $item['id_user_2']){
			throw new Fenix_Exception('Erro! Ação inválida');
		}
		
		//check for duplicity
		$id1 = $item['id_user_1'];
		$id2 = $item['id_user_2'];
		
		$select = parent::prepareSelect();
		$select->where("(id_user_1 = $id1 AND id_user_2 = $id2) OR (id_user_1 = $id2 AND id_user_2 = $id1)");
		
		$result = $select->query()->fetchAll();
			
		if( !empty($result) ){
			throw new Fenix_Exception("Erro: Usuários já estão relacionados.");
		}
		//
		
		return parent::insert($item);
	}
	
	/**
	 * Checa se dois usuários são amigos. (Existe registro de pedido de amizade com status = aceito)
	 * @param <number> $id_user_1
	 * @param <number> $id_user_2
	 * @return boolean
	 */
	public function checkFriendship($id_user_1, $id_user_2){
		$select = parent::prepareSelect();
		$select->where("(id_user_1 = $id_user_1 AND id_user_2 = $id_user_2) OR (id_user_1 = $id_user_2 AND id_user_2 = $id_user_1)");
		$select->where("status = ?", self::ACEITO);
		
		$result = $select->query()->fetchAll();
		return !empty($result);
	}
}