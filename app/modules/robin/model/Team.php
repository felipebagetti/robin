<?php
require_once 'Robin_Model.php';
class Team extends Robin_Model {
	const ADMINISTRADOR = 1;
	const COLABORADOR = 2;
	
	/**
	 *
	 * @return Goal
	 */
	public static function factory($name = "robin.team", $section = null) {
		return parent::factory ( $name, $section );
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::prepareSelect()
	 */
	public function prepareSelect($cols = array(), $where = array(), $sortCol = null, $sortDir = null, $count = null, $offset = null) {
		$select = parent::prepareSelect ( $cols, $where, $sortCol, $sortDir, $count, $offset );
		
		if ($this->getSection ()->getName () == 'team') {
			$select->where ( "team.id IN (SELECT id_team FROM robin.team_user where id_user = " . Zend_Auth::getInstance ()->getIdentity ()->id . ")" );
		}
		
		return $select;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::insert()
	 */
	public function insert($item) {
		if ($this->_section == "user") {
			
			// checks user duplicity
			$select = $this->prepareSelect ();
			$select->where ( 'id_team = ?', $item ['id_team'] );
			$select->where ( 'id_user = ?', $item ['id_user'] );
			
			$resultDuplicity = $select->query ()->fetchAll ();
			
			if (! empty ( $resultDuplicity )) {
				throw new Fenix_Exception ( "Exceção: Usuário já está no grupo." );
			}
			
			// allows only one administrator
			if ($item ['profile'] == self::ADMINISTRADOR) {
				$select = $this->prepareSelect ();
				$select->where ( 'id_team = ?', $item ['id_team'] );
				$select->where ( 'profile = ?', self::ADMINISTRADOR );
				
				$resultAdmin = $select->query ()->fetchAll ();
				
				if (! empty ( $resultAdmin )) {
					throw new Fenix_Exception ( "Exceção: Grupo já possui usuário administrador." );
				}
			}
		}
		
		$id = parent::insert ( $item );
		
		// Insert the current user as the team administrator
		if ($this->_section == "team") {
			$teamUser = array (
					'id_team' => $id,
					'id_user' => Zend_Auth::getInstance ()->getIdentity ()->id,
					'profile' => self::ADMINISTRADOR
			);
			Model::factory ( "team", "user" )->insert ( $teamUser );
		}
		
		return $id;
	}
	public function delete($id) {
		$item = $this->select ( $id );
		$ret = parent::delete ( $id );
		
		if ($this->_section == "user") {
			
			// transfer administratorship
			if ($item ['profile'] == self::ADMINISTRADOR) {
				$newadmin = Model::factory ( "team", "user" )->prepareSelect ()->where ( 'id_team = ?', $item ['id_team'] )->limit ( 1 )->query ()->fetchAll ();
				
				if (! empty ( $newadmin )) {
					$newadmin = $newadmin [0];
					$newadmin ['profile'] = self::ADMINISTRADOR;
						
					Model::factory ( "team", "user" )->update ( $newadmin );
				} else {
					Model::factory ( "team" )->delete($item['id_team']);
				}
			}
		}
	}
}




