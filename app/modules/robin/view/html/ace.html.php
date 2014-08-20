<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title><?php echo isset($view->title) ? $view->title : ""; ?></title>
		<meta name="description" content="" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo Util::getBaseUrl().'robin/img/favicon.ico'; ?>"/>
		<?php
        foreach($view->_css as $css){
            echo '<link rel="stylesheet" href="'.$css.'">'."\n";
        }
        foreach($view->_js as $js){
            echo '<script src="'.$js.'"></script>'."\n";
        }
        ?>
        <script type="text/javascript">
        <?php echo $view->_jsOnload; ?>
        </script>
	</head>
	<body class="navbar-fixed breadcrumbs-fixed">
		<div class="navbar navbar-default navbar-fixed-top" id="navbar">
			<script type="text/javascript">
				try{ace.settings.check('navbar' , 'fixed')}catch(e){}
			</script>

			<div class="navbar-container" id="navbar-container">
				<div class="navbar-header pull-left">
					<a href="<?php echo Util::getBaseUrl().'goal/calendar'; ?>" class="navbar-brand">
						<small>
							<i class="icon icon-list"></i>
							Robin
						</small>
					</a><!-- /.brand -->
				</div><!-- /.navbar-header -->

				<div class="navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						
						<li class="light-blue">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
							    <img class="nav-user-photo" src="<?php echo Util::getBaseUrl().'user/image?w=40'; ?>">
								<span class="user-info">
									<small>Bem-vindo,</small>
									<?php if(Zend_Auth::getInstance()->getIdentity()) echo Zend_Auth::getInstance()->getIdentity()->{ Util::getConfig(Util::CONFIG_AUTH)->column_name }; ?>
								</span>

								<i class="icon-caret-down"></i>
							</a>

							<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="<?php echo Util::getBaseUrl()."user/profile?id=".Zend_Auth::getInstance()->getIdentity()->id; ?>">
										<i class="icon-cog"></i>
										Meu Perfil
									</a>
								</li>

								<li>
									<a href="#" onclick="Fenix_Model.page(Fenix.getBaseUrl()+'user/change-password', 'Trocar Senha', 600); return false;">
										<i class="icon-user"></i>
										Trocar Senha
									</a>
								</li>

								<li class="divider"></li>

								<li>
									<a href="<?php echo $view->_baseUrl . 'fenix/auth/logoff'; ?>">
										<i class="icon-power-off"></i>
										Sair do Sistema
									</a>
								</li>
							</ul>
						</li>
					</ul><!-- /.ace-nav -->
				</div><!-- /.navbar-header -->
			</div><!-- /.container -->
		</div>

		<div class="main-container" id="main-container">
			<script type="text/javascript">
				try{ace.settings.check('main-container' , 'fixed')}catch(e){}
			</script>

			<div class="main-container-inner">
				<a class="menu-toggler" id="menu-toggler" href="#">
					<span class="menu-text"></span>
				</a>

				<div class="sidebar sidebar-fixed" id="sidebar">
					<script type="text/javascript">
						try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
					</script>

					<ul class="nav nav-list">
					
				    <?php echo $view->menu; ?>
				    
				    <script type="text/javascript">
                        $('li.active').closest('ul.submenu').css('display', 'block');
                    </script>
					
					</ul><!-- /.nav-list -->

					<div class="sidebar-collapse" id="sidebar-collapse">
						<i class="icon-angle-double-left" data-icon1="icon-angle-double-left" data-icon2="icon-angle-double-right"></i>
					</div>

					<script type="text/javascript">
						try{ace.settings.check('sidebar' , 'collapsed')}catch(e){}
					</script>
				</div>

				<div class="main-content">
					<div class="breadcrumbs breadcrumbs-fixed" id="breadcrumbs">
						<script type="text/javascript">
							try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
						</script>

						<ul class="breadcrumb">
						
							<li <?php isset($view->title) && $view->title == 'Robin' ? 'class="active"' : '' ?>>
								<i class="icon-home home-icon"></i>
								<a href="<?php echo Util::getBaseUrl().'goal/'; ?>">Página Inicial</a>
							</li>
						
							<?php foreach($view->breadcumbs as $key => $value){
					        ?>
    							<li>
    								<?php if(!is_int($key)){ ?><a href="<?php echo $key; ?>"><?php echo $value; ?></a><?php } ?>
    								<?php if(is_int($key)){ echo $value; } ?>
    							</li>
							<?php } ?>
						
							<?php if(isset($view->title) && $view->title != "Robin"){ ?>
							<li class="active">
								<?php echo $view->title; ?>
							</li>
							<?php } ?>
							
						</ul><!-- .breadcrumb -->

						<?php /*
						
						<div class="nav-search" id="nav-search">
							<form class="form-search">
								<span class="input-icon">
									<input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
									<i class="icon-search nav-search-icon"></i>
								</span>
							</form>
						</div><!-- #nav-search -->
						*/ ?>
					</div>
					
					<div class="page-content">

					    <?php if(isset($view->showHeader) && $view->showHeader !== false){ ?>
    					<div class="page-header">
    							<h1>
    								<?php echo $view->title; ?>
    								<?php if(isset($view->description)){ ?>
    								<small>
    									<i class="icon-double-angle-right"></i>
    									<?php echo $view->description; ?>
    								</small>
    								<?php } ?>
    							</h1>
    						</div><!-- /.page-header -->
						<?php } ?>
					
						<div class="row">
							<div class="col-xs-12" id="element-container">
								
								<!-- PAGE CONTENT BEGINS -->
								
								<?php if(isset($view->include) && $view->include){ require $view->include; } ?>
							
								<!-- PAGE CONTENT ENDS -->
								
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div><!-- /.main-content -->

			</div><!-- /.main-container-inner -->

		</div><!-- /.main-container -->

	</body>
</html>
