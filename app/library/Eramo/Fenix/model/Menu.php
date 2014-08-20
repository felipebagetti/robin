<?php 

class Fenix_Menu extends Model {
    
    /**
     * @return Fenix_Menu
     */
    public static function factory($name = "menu", $section = null){
        return parent::factory($name, $section);
    }

    /**
     * (non-PHPdoc)
     * @see Model::insert()
     */
    public function insert($item){
        if(!$item['position']){
            $item['position'] = 0;
        }
        return parent::insert($item); 
    }
    
    /**
     * (non-PHPdoc)
     * @see Model::update()
     */
    public function update($item){
        if(!$item['position']){
            $item['position'] = 0;
        }
        return parent::update($item); 
    }
    
}










