<div id="user-profile-2" class="user-profile">
	<div class="tabbable">
		<ul class="nav nav-tabs padding-18">
			<li class="active"><a data-toggle="tab" href="#home"> <i
					class="green ace-icon fa fa-user bigger-120"></i> Perfil
			</a></li>

			<li><a data-toggle="tab" href="#friends"> <i
					class="blue ace-icon fa fa-users bigger-120"></i> Amigos
			</a></li>

			<!-- 
			<li><a data-toggle="tab" href="#feed"> <i
					class="orange ace-icon fa fa-rss bigger-120"></i> Activity Feed
			</a></li>

			<li><a data-toggle="tab" href="#pictures"> <i
					class="pink ace-icon fa fa-picture-o bigger-120"></i> Pictures
			</a></li>
			-->

		</ul>

		<div class="tab-content no-border padding-24">
			<div id="home" class="tab-pane in active">
				<div class="row">
					<div class="col-xs-12 col-sm-3 center">
						<span class="profile-picture"> <img
							class="editable img-responsive" id="user_picture"
							src="" />
						</span>

						<div class="space space-4"></div>

						<a id="btn-friendship" href="#" class="btn btn-sm btn-block"> <i
							class="ace-icon fa fa-plus-circle bigger-120"></i> <span
							class="bigger-110"></span>
						</a>
					</div>
					<!-- /.col -->

					<div class="col-xs-12 col-sm-9">
						<h4 class="blue">
							<span class="middle" id="user_name_title"></span> 
							<span id="friendship_flag" class="label arrowed-in align-middle"> 
								<i id="friendship_icon" class=""></i> 
								<span id="friendship_status"></span>
							</span>

						</h4>

						<div class="profile-user-info">

							<div class="profile-info-row">
								<div class="profile-info-name">Localização</div>

								<div class="profile-info-value">
									<span id="user_location"></span>
								</div>
							</div>

							<div class="profile-info-row">
								<div class="profile-info-name">
									<i class="middle ace-icon icon-clock-o bigger-150 gray"></i>
								</div>

								<div class="profile-info-value">
									Usuário desde <span id="user_creation_date"></span>
								</div>
							</div>

							<div class="profile-info-row">
								<div class="profile-info-name">
									<i class="middle ace-icon fa icon-link bigger-150 gray"></i>
								</div>

								<div class="profile-info-value">
									<a href="#" id="user_website"></a>
								</div>
							</div>

							<div class="profile-info-row">
								<div class="profile-info-name">
									<i
										class="middle ace-icon fa icon-facebook-square bigger-150 blue"></i>
								</div>

								<div class="profile-info-value">
									<a href="#" id="user_facebook">Encontre-me no Facebook</a>
								</div>
							</div>

							<div class="profile-info-row">
								<div class="profile-info-name">
									<i
										class="middle ace-icon fa icon-twitter-square bigger-150 light-blue"></i>
								</div>

								<div class="profile-info-value">
									<a href="#" id="user_twitter">Siga-me no Twitter</a>
								</div>
							</div>
						</div>

					</div>
					<!-- /.col -->
				</div>
				<!-- /.row -->

				<div class="space-10"></div>

				<div class="row">
					<div class="col-xs-12">
						<div class="widget-box transparent">
							<div class="widget-header widget-header-small">
								<h4 class="smaller">
									<i class="icon-check bigger-110"></i> Descrição
								</h4>
							</div>

							<div class="widget-body">
								<div class="widget-main">
									<p id="user_bio"></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.row -->

			</div>
			<!-- /#home -->

			<div id="feed" class="tab-pane">
				<div class="profile-feed row">
					<div class="col-sm-6">
						<div class="profile-activity clearfix">
							<div>
								<img class="pull-left" alt="Alex Doe's avatar" src="" /> <a
									class="user" href="#"> Alex Doe </a> changed his profile photo.
								<a href="#">Take a look</a>

								<div class="time">
									<i class="ace-icon fa fa-clock-o bigger-110"></i> an hour ago
								</div>
							</div>

							<div class="tools action-buttons">
								<a href="#" class="blue"> <i
									class="ace-icon fa fa-pencil bigger-125"></i>
								</a> <a href="#" class="red"> <i
									class="ace-icon fa fa-times bigger-125"></i>
								</a>
							</div>
						</div>

					</div>
					<!-- /.col -->
				</div>
				<!-- /.row -->

				<div class="space-12"></div>

				<div class="center">
					<button type="button"
						class="btn btn-sm btn-primary btn-white btn-round">
						<i class="ace-icon fa fa-rss bigger-150 middle orange2"></i> <span
							class="bigger-110">View more activities</span> <i
							class="icon-on-right ace-icon fa fa-arrow-right"></i>
					</button>
				</div>
			</div>
			<!-- /#feed -->

			<div id="friends" class="tab-pane">
				<!-- #section:pages/profile.friends -->
				<div class="profile-users clearfix">
				</div>

			</div>
			<!-- /#friends -->

			<div id="pictures" class="tab-pane">
				<ul class="ace-thumbnails">
					<li><a href="#" data-rel="colorbox"> <img alt="150x150" src="" />
							<div class="text">
								<div class="inner">Sample Caption on Hover</div>
							</div>
					</a>

						<div class="tools tools-bottom">
							<a href="#"> <i class="ace-icon fa fa-link"></i>
							</a> <a href="#"> <i class="ace-icon fa fa-paperclip"></i>
							</a> <a href="#"> <i class="ace-icon fa fa-pencil"></i>
							</a> <a href="#"> <i class="ace-icon fa fa-times red"></i>
							</a>
						</div></li>

				</ul>
			</div>
			<!-- /#pictures -->
		</div>
	</div>
</div>