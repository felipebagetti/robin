<?php 

const MENU_LINK = 1;
const MENU_HEADER = 2;
const MENU_DIVIDER = 3;

function menuPrint($view, $menu, $firstLevel = false){
    
    $hasSubmenu = isset($menu['_submenu']) && is_array($menu['_submenu']);
    
    if(strlen($menu['url']) > 0){
        $menu['url'] = ' href="'.$view->_baseUrl . $menu['url'].'"';
    } else {
        $menu['url'] = ' href="#" onclick="return false;"';
    }
    
    $className = $hasSubmenu ? ($firstLevel ? "dropdown" : "dropdown-submenu" ) : "";
    $aAttr = "";
    if($className == "dropdown"){
        $aAttr = ' class="dropdown-toggle" data-toggle="dropdown"';
        $menu['name'] .= ' <b class="caret"></b>';
    }
    
    $html = array();
    
    switch ($menu['type']){
        case MENU_DIVIDER:
            $html[] = '<li class="divider">';
            break;
        case MENU_HEADER:
            $html[] = '<li class="navbar-header">'.$menu['name'];
            break;
        case MENU_LINK: default:
            $html[] = '<li class="'.$className.'"><a'.$menu['url'].$aAttr.'>'.$menu['name'].'</a>';
            break;
    }
    
    if($hasSubmenu){
        
        $html[] = '<ul class="dropdown-menu">';
         
        foreach($menu['_submenu'] as $submenu){
            $html = array_merge($html, menuPrint($view, $submenu));
        }
        
        $html[] = '</ul>';
        
    }
    
    $html[] = '</li>';
    
    return $html;
}

function imageLogo(){
    
    $ret = "/img/logo-topo.png";
    
    $defaultModule = Util::getConfig(Util::CONFIG_GENERAL)->defaultModule;
    
    if(is_file("app/modules/".$defaultModule."/view".$ret)){
        $ret = $defaultModule.$ret;
    } else {
        $ret = "fenix".$ret;
    }
    
    return Util::getBaseUrl() . $ret; 
}

?>
<div class="navbar navbar-default" style="height: 40px; min-height: 41px;">
	<div class="navbar-header">
		
		<a class="navbar-brand" href="<?php echo $view->_baseUrl; ?>"> 
			<img src="<?php echo imageLogo() ?>"
			width="83" height="26" style="height: 26px;" />
		</a>
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
			<span class="icon-bar"></span> 
			<span class="icon-bar"></span> 
			<span class="icon-bar"></span>
		</button>
	</div>

	<div class="navbar-collapse navbar-responsive-collapse collapse">
		<ul class="nav navbar-nav">
    
    	  <?php
    	      try {
      			foreach ( Util::getMenu () as $menu ) {
				   echo implode ( "\n", menuPrint ( $view, $menu, true ) );
				}
              } catch(Exception $e){
                Util::exceptionLog($e);
              }
    	  ?>
      
      		<script type="text/javascript">
     			 try {
    	  			$('a[href="'+window.location.toString().split("?")[0].split('#')[0]+'"]').parent().addClass('active');
    	  			$('a[href="'+window.location.toString()+'"]').parent().addClass('active');
     			 } catch(e){}
      		</script>

		</ul>
		
		<ul class="nav nav-navbar navbar-right ">
			<li class="navbar-nav divider-vertical"></li>
			<li class="dropdown" style="max-height: 36px;">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown"> <i class="glyphicon glyphicon-user"></i> 
					<?php if(Zend_Auth::getInstance()->getIdentity()) echo Zend_Auth::getInstance()->getIdentity()->{ Util::getConfig(Util::CONFIG_AUTH)->column_name }; ?> 
					<b class="caret"></b>
				</a>
				<ul class="dropdown-menu">
					<!--
          				<li><a href="#"><i class="icon-wrench"></i> Configurações</a></li>
          			-->
					<li>
						<a href="#" onclick="Fenix_Model.page(Fenix.getBaseUrl()+'<?php echo str_replace(".", "/", Util::getConfig(Util::CONFIG_AUTH)->model); ?>/change-password', 'Trocar Senha', 400); return false;">
						<i class="glyphicon glyphicon-edit"></i> Trocar Senha</a>
					</li>
					<li class="divider"></li>
					<li><a href="<?php echo $view->_baseUrl . 'fenix/auth/logoff'; ?>"><i class="glyphicon glyphicon-off"></i> Sair do Sistema</a></li>
				</ul>
			</li>
			
			<li style="max-width: 37px; margin-top: -34px; margin-left:120px;"><a href="<?php echo $view->_baseUrl . 'fenix/auth/logoff'; ?>" id="btn-logoff"><i class="glyphicon glyphicon-off"></i></a></li>
		</ul>
	</div>
</div>