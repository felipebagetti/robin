<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<link rel="shortcut icon" type="image/x-icon" href="<?php echo Util::getBaseUrl().'nfse/img/favicon.ico'; ?>"/>
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
        <title>Robin - Redefinição de Senha</title>
		
		<meta name="description" content="Recuperação de Senha" />
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
								    <i class="icon-file-text"></i>
									<span>Robin</span>
								</h1>
							</div>

							<div class="space-6"></div>

							<div class="position-relative">
								 
								<div id="forgot-box" class="forgot-box widget-box no-border visible">
									<div class="widget-body">
										<div class="widget-main">
											<h4 class="header red lighter bigger">
												<i class="icon-key"></i>
												Redefinir Senha
											</h4>

											<div class="space-6"></div>
											<p>
												Digite uma nova senha:
											</p>

											<iframe src="" name="iframe" style="display: none;"></iframe>
											<form action="<?php echo $view->_baseUrl . 'redefinir-senha-submit'; ?>" method="post" target="iframe">
											    <input type="hidden" name="token" value="<?php echo $view->token; ?>">
												<fieldset>

													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="password" class="form-control" name="password" id="password" placeholder="Senha" />
															<i class="icon-lock"></i>
														</span>
													</label>
												
													<label class="block clearfix">
														<span class="block input-icon input-icon-right">
															<input type="password" class="form-control" name="password-confirm" id="password-confirm" placeholder="Confirmação da Senha" />
															<i class="icon-lock"></i>
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

									</div><!-- /widget-body -->
								</div><!-- /forgot-box -->

							</div><!-- /position-relative -->
						</div>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div>
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<!-- inline scripts related to this page -->

	</body>
</html>
