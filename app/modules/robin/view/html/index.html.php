<!DOCTYPE html>
<html lang="en">
	<head>
	<!--[if IE 9]>
		<meta http-equiv="Content-Type" content="text/html" charset="UTF-8" />
	<![endif]-->
	<!--[if ! IE 9]>
		<meta charset="UTF-8" />
	<![endif]-->
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
        <title>Robin</title>
		
		<meta name="description" content="Página de entrada no sistema" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	</head>

	<body class="login-layout">
		<div class="main-container">
			<div class="main-content">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<div class="login-container">
							<div class="center">
								<h1 class="white">
									<img alt="Robin" src="<?php echo Util::getBaseUrl().'robin/img/logo-index.png';?>" height="120">
								</h1>
							</div>

							<div class="space-6"></div>

							<div class="position-relative">
								<div id="login-box" class="login-box visible widget-box no-border">
									<div class="widget-body">
										<div class="widget-main">
											<h4 class="header blue lighter bigger">
												<i class="icon-user light-blue"></i>
												Por favor, digite suas informações
										    </h4>
											<div class="space-6"></div>

											<iframe src="" name="iframe" style="display: none;"></iframe>
											<form action="<?php echo $view->_baseUrl . 'fenix/auth/logon'; ?>" method="post" target="iframe">
												<fieldset>
													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="text" class="form-control" name="login" id="login" placeholder="Email" autocorrect="off" autocapitalize="off" />
															<i class="icon-user"></i>
														</span>
													</label>

													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="password" class="form-control" name="password" id="password" placeholder="Senha" />
															<i class="icon-lock"></i>
														</span>
													</label>

													<div style="display: none;">
    													<label class="block clearfix">
    														<span class="block input-icon input-icon-right">
    															<input type="password" class="form-control" name="tfa" id="tfa" placeholder="Código de Autenticação" />
    															<i class="icon-lock"></i>
    														</span>
    													</label>
													</div>

													<div class="space"></div>

													<div class="clearfix">
														<label class="inline">
															<input type="checkbox" class="ace" name="remember" value="1" />
															<span class="lbl"> Lembrar-me </span>
														</label>

														<button type="submit" class="width-35 pull-right btn btn-sm btn-primary">
															<i class="icon-key"></i>
															Entrar
														</button>
													</div>

													<div class="space-4"></div>
												</fieldset>
											</form>

										</div><!-- /widget-main -->

										<div class="toolbar clearfix">
											<div>
												<a href="#" onclick="Robin_Index.show('forgot-box'); $('[name=email]').focus(); return false;" class="forgot-password-link">
													<i class="icon-arrow-left"></i>
													Esqueci minha senha
												</a>
											</div>

											<div>
												<a href="#" onclick="Robin_Index.show('signup-box'); $('[name=nome]', '#signup-box').focus(); return false;" class="user-signup-link">
													Quero me cadastrar
													<i class="icon-arrow-right"></i>
												</a>
											</div>
										</div>
									</div><!-- /widget-body -->
								</div><!-- /login-box -->

								<div id="forgot-box" class="forgot-box widget-box no-border">
									<div class="widget-body">
										<div class="widget-main">
											<h4 class="header red lighter bigger">
												<i class="icon-key"></i>
												Redefinir Senha
											</h4>

											<div class="space-6"></div>
											<p>
												Digite seu email para receber instruções:
											</p>

											<form action="<?php echo $view->_baseUrl . 'redefinir-senha-email'; ?>" method="post" target="iframe">
												<fieldset>
													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="email" class="form-control" name="email" placeholder="Email" />
															<i class="icon-envelope"></i>
														</span>
													</label>

													<div class="clearfix">
														<button type="submit" class="width-35 pull-right btn btn-sm btn-danger">
															<i class="icon-lightbulb"></i>
															Enviar!
														</button>
													</div>
												</fieldset>
											</form>
										</div><!-- /widget-main -->

										<div class="toolbar center">
											<a href="#" onclick="Robin_Index.show('login-box'); $('#login').focus(); return false;" class="back-to-login-link">
												Voltar a tela de Entrada
												<i class="icon-arrow-right"></i>
											</a>
										</div>
									</div><!-- /widget-body -->
								</div><!-- /forgot-box -->
								
								<div id="signup-box" class="signup-box widget-box no-border">
									<div class="widget-body">
										<div class="widget-main">
											<h4 class="header green lighter bigger">
												<i class="icon-group blue"></i>
												Cadastro de Novo Usuário
											</h4>

											<div class="space-6"></div>
											<p> Digite suas informações para começar: </p>

											<form action="<?php echo $view->_baseUrl . 'cadastro-submit'; ?>" method="post" target="iframe">
											    <input type="password" name="password_chrome" style="display: none;" />
											    
												<fieldset>
												<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="text" name="name" class="form-control" placeholder="Nome" />
															<i class="icon-user"></i>
														</span>
													</label>
													
													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="email" name="email" class="form-control" placeholder="Email" />
															<i class="icon-envelope"></i>
														</span>
													</label>

													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="password" name="password" class="form-control" placeholder="Senha" />
															<i class="icon-lock"></i>
														</span>
													</label>

													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="password" name="password_repeat" class="form-control" placeholder="Repetir senha" />
															<i class="icon-retweet"></i>
														</span>
													</label>

													<!-- 
													<label class="block">
														<input type="checkbox" class="ace" />
														<span class="lbl">
															I accept the
															<a href="#">User Agreement</a>
														</span>
													</label>
													 -->

													<div class="space-24"></div>

													<div class="clearfix">
														<button type="submit" class="width-65 pull-right btn btn-sm btn-success">
															Cadastrar
															<i class="icon-arrow-right icon-on-right"></i>
														</button>
													</div>
												</fieldset>
											</form>
										</div>

										<div class="toolbar center">
											<a href="#" onclick="Robin_Index.show('login-box'); return false;" class="back-to-login-link">
												<i class="icon-arrow-left"></i>
												Voltar ao Login
											</a>
										</div>
									</div><!-- /widget-body -->
								</div><!-- /signup-box -->

							</div><!-- /position-relative -->
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div>
		</div><!-- /.main-container -->

	</body>
</html>
