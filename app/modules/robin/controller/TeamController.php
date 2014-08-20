<?php

require_once __DIR__.'/ModelController.php';

class TeamController extends ModelController {
	
	/**
	 * (non-PHPdoc)
	 * @see ModelController::_getGrid()
	 */
	protected function _getGrid(){
	
		$grid = parent::_getGrid();
	
		$grid->setOption(Grid::OPTION_EDIT_ON_LAYER, true);
		$grid->setOption(Grid::OPTION_SORT_COL, 'name');
		$grid->setOption(Grid::OPTION_SORT_DIR, 'ASC');
		$grid->setOption(Grid::OPTION_PAGINATION_PER_PAGE, 5);
	
		$grid->addJs('robin/Robin_Team.js');
	
		$pictureColumn = $grid->getColumn('picture');
		if ( !empty($pictureColumn) ) {
			$grid->setColumnAttribute('picture', GridColumn::FORMATTER, 'Robin_Team.formatterPicture');
			$grid->setColumnAttribute('picture', GridColumn::STYLE_HEADER, "center");
			$grid->setColumnAttribute('picture', GridColumn::STYLE_CONTENT, "center");
			$grid->setColumnAttribute('picture', GridColumn::WIDTH, "8em");
		}
		
		$nameColumn = $grid->getColumn('picture');
		if ( !empty($nameColumn) ) {
			$grid->setColumnAttribute('name', GridColumn::FORMATTER, 'Robin_Team.formatterName');
		}
		
		$grid->deleteButton('Alterar');
		$grid->deleteButton('Excluir');
	
// 		$grid->deleteButtonPage('Novo Registro');
	
		return $grid;
	}
}