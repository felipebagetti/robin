<?php
require_once 'Robin_Model.php';
class Goal extends Robin_Model {
	const PENDENTE = 1;
	const CONCLUIDA = 2;
	const CANCELADA = 3;
	
	// section user - attr profile
	const ADMINISTRADOR = 1;
	const COLABORADOR = 2;
	
	/**
	 *
	 * @return Goal
	 */
	public static function factory($name = "robin.goal", $section = null) {
		return parent::factory ( $name, $section );
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::prepareSelect()
	 */
	public function prepareSelect($cols = array(), $where = array(), $sortCol = null, $sortDir = null, $count = null, $offset = null) {
		$select = parent::prepareSelect ( $cols, $where, $sortCol, $sortDir, $count, $offset );
		
		if ($this->getSection ()->getName () == 'goal') {
			$select->where ( "goal.id IN (SELECT id_goal FROM robin.goal_user where id_user = " . Zend_Auth::getInstance ()->getIdentity ()->id . ")" );
		}
		
		return $select;
	}
	
	/**
	 * Adds $id_user as contributor of $id_goal
	 * 
	 * @param <number> $id_goal
	 * @param <number> $id_user
	 * @throws Fenix_Exception in case of: logged in user not (friend with $id_user AND admin of $id_goal)
	 * @return <number> goal_user.id in case of success
	 */
	public function shareGoalUser($id_goal, $id_user) {
		// verifica amizade
		$friendship = new Friendship ();
		if (! $friendship->checkFriendship ( Zend_Auth::getInstance ()->getIdentity ()->id, $id_user )) {
			throw new Fenix_Exception ( 'Erro: Usuários não são relacionados.' );
		}
		
		// verifica se é admin da meta
		if ($this->getAdmin ( $id_goal ) != Zend_Auth::getInstance ()->getIdentity ()->id) {
			throw new Fenix_Exception ( 'Erro: Usuário não tem permissão para compartilhar meta.' );
		}
		
		// adiciona goal.user
		$item = array('id_goal' => $id_goal, 'id_user' => $id_user, 'profile' => self::COLABORADOR);
		return Model::factory ( 'goal', 'user' )->insert($item);
	}
	
	//TODO
	public function shareGoalTeam($id_goal, $id_team) {
		// verifica se está no grupo
		
		// verifica se é admin da meta
		
		// select em todos os usuarios do grupo
		
		// adiciona goal.user para cada um dos usuarios
	}
	
	/**
	 * Returns id_user of admin
	 * 
	 * @param unknown $id_goal        	
	 */
	public function getAdmin($id_goal) {
		$select = Model::factory ( 'goal', 'user' )->prepareSelect ( 'id_user' )->limit ( 1 );
		$select->where ( 'id_goal = ?', $id_goal );
		$select->where ( 'profile = ?', self::ADMINISTRADOR );
		
		$resultAdmin = $select->query ()->fetchAll ();
		
		if (! empty ( $resultAdmin )) {
			return $resultAdmin [0] ['id_user'];
		} else {
			throw new Fenix_Exception ( "Exceção: Meta não possui usuário administrador." );
		}
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::insert()
	 */
	public function insert($item) {
		
		// doesn't allow more than two levels of hierarchy
		if (false && $this->_section == "goal" && ! empty ( $item ['id_goal'] )) {
			$parentGoal = $this->select ( $item ['id_goal'] );
			
			if (! empty ( $parentGoal ['id_goal'] )) {
				throw new Fenix_Exception ( 'Hierarquia de metas está limitada para apenas dois níveis.' );
			}
		}
		
		if ($this->_section == "user") {
			
			// checks user duplicity
			$select = $this->prepareSelect ();
			$select->where ( 'id_goal = ?', $item ['id_goal'] );
			$select->where ( 'id_user = ?', $item ['id_user'] );
			
			$resultDuplicity = $select->query ()->fetchAll ();
			
			if (! empty ( $resultDuplicity )) {
				throw new Fenix_Exception ( "Exceção: Usuário já está na meta." );
			}
			
			// allows only one administrator
			if ($item ['profile'] == self::ADMINISTRADOR) {
				$select = $this->prepareSelect ();
				$select->where ( 'id_goal = ?', $item ['id_goal'] );
				$select->where ( 'profile = ?', self::ADMINISTRADOR );
				
				$resultAdmin = $select->query ()->fetchAll ();
				
				if (! empty ( $resultAdmin )) {
					throw new Fenix_Exception ( "Exceção: Meta já possui usuário administrador." );
				}
			}
		}
		
		$id = parent::insert ( $item );
		
		// Insert the current user as the goal administrator
		if ($this->_section == "goal") {
			$goalUser = array (
					'id_goal' => $id,
					'id_user' => Zend_Auth::getInstance ()->getIdentity ()->id,
					'profile' => 1 
			);
			Model::factory ( "goal", "user" )->insert ( $goalUser );
		}
		
		return $id;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Model::insert()
	 */
	public function delete($id) {
		
		$item = $this->select ( $id );
		$ret = parent::delete ( $id );
		
		if ($this->_section == "user") {
			
			// If there is another user{ transfer administratorship} else {deletes whole goal}
			if ($item ['profile'] == self::ADMINISTRADOR) {
				$newadmin = Model::factory ( "goal", "user" )->prepareSelect ()->where ( 'id_goal = ?', $item ['id_goal'] )->limit ( 1 )->query ()->fetchAll ();
				
				if (! empty ( $newadmin )) {
					$newadmin = $newadmin [0];
					$newadmin ['profile'] = self::ADMINISTRADOR;
					
					Model::factory ( "goal", "user" )->update ( $newadmin );
				} else {
					Model::factory ( "goal" )->delete($item['id_goal']);
				}
			}
		}
		return $ret;
	}
}




