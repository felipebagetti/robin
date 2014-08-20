<?php

require_once 'ModelController.php';

class GoalController extends ModelController {
	
    /**
     * Exibe o calendário das metas
     */
    public function calendarAction(){
    
        $view = $this->_prepareAce();
        
        $view->include = "goal.html.php";
        
        
        foreach( array( new Grid(), new Form() ) as $page ){
            $view->addPageJs($page);
        }
        
        $view->title = "Metas";
        
        $view->addJs('robin/jquery-ui-1.10.3.custom.js');
        $view->addJs('robin/jquery.ui.touch-punch.js');
        
        $view->addJs('robin/moment.js');
        $view->addJs('robin/fullcalendar.js');
        $view->addJs('robin/fullcalendar-pt-br.js');
        
        $view->addOnload('Robin_Goal.externalEvents('.json_encode(Util::utf8_encode($this->_withoutDate())).');');
        $view->addOnload('Robin_Goal.showCalendar('.json_encode(Util::utf8_encode($this->_withDate())).');');
        $view->addOnload('$("#nova-meta").click( Robin_Goal.record );');
    
        $view->render();
    }
    
    /**
     * Cria os eventos no formato esperado pelo calendário
     * @param mixed $registro
     * @return mixed
     */
    protected function _createCalendarEvent($registro){
        return array(
                'id' => $registro['id'],
                'title' => $registro['name'],
                'start' => $registro['due_datetime'],
                'priority' => $registro['priority'],
        );
    }
    
    /**
     * Retorna a lista de eventos com data
     * @return mixed
     */
    public function _withDate(){
        $dados = $this->_getModel()->prepareSelect()->where('goal.due_datetime IS NOT NULL')->query()->fetchAll();
        $eventos = array_map(array($this, "_createCalendarEvent"), $dados);
        return $eventos;
    }
    
    /**
     * Retorna a lista de eventos sem data
     * @return mixed
     */
    public function _withoutDate(){
        $dados = $this->_getModel()->prepareSelect()->where('goal.due_datetime IS NULL')->query()->fetchAll();
        $eventos = array_map(array($this, "_createCalendarEvent"), $dados);
        return $eventos;
    }
    
    /**
     * Retorna lists dos eventos sem data 
     */
    public function withoutDateAction(){
        die(json_encode(Util::utf8_encode($this->_withoutDate())));
    }
    
    /**
     * Faz o processo de salvamento dos dados de uma meta a partir das informações enviadas pelo calendário
     */
    public function calendarSaveAction(){
        
        $_REQUEST = array(
            'id' => $_REQUEST['id'],
            'due_datetime' => substr(str_replace("T", " ", $_REQUEST['start']), 0, 16)
        );
        
        return parent::saveAction();
    }
    
	public function _getFormGoal(){
		
		$form  = parent::_getForm();
		
		$form->setOption(Form::OPTION_SUBMIT_CALLBACK, 'Robin_Goal.submitCallback');

		$record = $form->getOption(Form::OPTION_RECORD);
		
		if ( isset($record['id']) && ((int)$this->_getModel()->getAdmin($record['id']) === (int)Zend_Auth::getInstance()->getIdentity()->id)) {
			// Prepara a aba de Amigos
			$gridSub = $this->_getFormPrepareGridSubcadastro($form, Model::factory("friendship"), "Convidar Amigos");
			
			$gridSub->addJs('robin/Robin_Goal.js');
			
			$gridSub->setOption(Grid::OPTION_PARAMS, 'id_goal='.$record['id']);
			$gridSub->setOption(Grid::OPTION_BUTTONS_FORMATTER, 'Robin_Friendship.buttonsGoalFormatter');
			$gridSub->setOption(Grid::OPTION_AUTO_LOAD, true);
			$gridSub->setOption(Grid::OPTION_PAGINATION_PER_PAGE, 3);
			
			$gridSub->deleteColumn('status');
			
			$gridSub->deleteButtonPage('Novo Registro');
			foreach ($gridSub->getButtons() as $value) {
				$gridSub->deleteButton($value->title);
			}
			
			$gridSub->addButton('Compartilhar meta', "Robin_Goal.share", 'icon-share');
			$gridSub->addButton('Remover', "Robin_Goal.deleteShare", 'icon-times');

		} else {
			$form->deleteButtonPage('Excluir');
		}
		
		if( isset($_REQUEST['start']) ){
		    $record['due_datetime'] = str_replace("T", " ", $_REQUEST['start']);
		}

		if( isset($record['id']) ){
    		$form->addButtonPage( 'Sair da meta', "Robin_Goal.leaveGoal({$record['id']})", 'icon-times', array('className' => 'btn btn-default btn-sm') );
		}
		
		$form->setOption(Form::OPTION_RECORD, $record);
		
		return $form;
	}
	
	public function _getGridUser(){
		
		$grid = parent::_getGrid();
		
		$grid->deleteButtonPage('Novo Registro');
		
		foreach ($grid->getButtons() as $value) {
			$grid->deleteButton($value->title);
		}
		
		return $grid;
	}
	
	public function _getGrid(){
		
		$grid = parent::_getGrid();
		
		$name = $grid->getColumn('name');
		if ( !empty($name) ) {
			$grid->setColumnAttribute('name', GridColumn::FORMATTER, 'Robin_Goal.formatterName');
		}
		
		foreach ($grid->getButtons() as $value) {
			$grid->deleteButton($value->title);
		}
		
		return $grid;
	}
	
	public function shareAction(){
	
		// Cria o model para permitir que as constantes sejam referenciadas (garante que está incluída a classe)
		Model::factory("goal");
		
		$item = array(
			'id_goal' => $_REQUEST['id_goal'],
			'id_user' => $_REQUEST['id_user'],
			'profile' => Goal::COLABORADOR
		);
		
		$ret = Model::factory("goal", "user")->insert($item);
		
		die($ret);
	}
	
	public function deleteShareAction(){
	
		$ret = Model::factory("goal", "user")->delete($_REQUEST['id_goal_user']);
		
		die($ret);
	}
	
	public function leaveGoalAction(){
	
		$modelGoalUser = Model::factory("goal", "user");
		
		$select = $modelGoalUser->prepareSelect('id');
		$select->where('id_goal = ?', $_REQUEST['id']);
		$select->where('id_user = ?', Zend_Auth::getInstance()->getIdentity()->id);
		$select->limit(1);
		
		$record = $select->query()->fetchAll();
		
		$ret = $modelGoalUser->delete($record[0]['id']);
		
		die($ret);
	}
}