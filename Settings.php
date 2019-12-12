<?php
class Settings {
	public $settings = array(
		'admin_menu_category' => 'Settings',
		'admin_menu_name' => 'Settings',
		'admin_menu_icon' => '<i class="icon-wrench"></i>',
		'description' => 'Allows you to configure the main Billic settings.',
	);
	function admin_area() {
		global $billic, $db;
		$current_module = 'Core';
		if (!empty($_POST['billic_ajax_module'])) {
			$current_module = $_POST['billic_ajax_module'];
		}
		if (!empty($_POST)) {
			if (empty($_POST['billic_ajax_module'])) {
				err('There was no $_POST[\'billic_ajax_module\'] so Billic doesn\'t know what module to call. To fix this, add this to your form: ' . htmlentities('<input type="hidden" name="billic_ajax_module" value="billic_ajax_module">'));
			}
			if (!$billic->user_has_permission($billic->user, $_POST['billic_ajax_module'])) {
				err('You do not have permission to view this page');
			}
			if ($_POST['billic_ajax_module'] == 'Core') {
				if (isset($_POST['regen_cache'])) {
					$billic->regenerate_module_cache();
					$billic->regenerate_menu_cache();
					$billic->status = 'updated';
				} else {
					if (!empty($_POST['amazon_ses_auth'])) {
						$ses = explode(' ', $_POST['amazon_ses_auth']);
						if (count($ses) != 3) {
							$billic->error('The billic setting "Amazon SES Auth" contains invalid information');
						}
						if (strpos($ses[0], '.amazonaws.com') === false) {
							$billic->error('The billic setting "Amazon SES Auth" contains invalid information');
						}
					}
					if (empty($billic->errors)) {
						set_config('billic_domain', $_POST['billic_domain']);
						set_config('billic_ssl', $_POST['billic_ssl']);
						set_config('billic_force_ssl', $_POST['billic_force_ssl']);
						set_config('billic_force_www', $_POST['billic_force_www']);
						set_config('billic_session_timeout', $_POST['billic_session_timeout']);
						set_config('billic_lock_session_ip', $_POST['billic_lock_session_ip']);
						set_config('billic_default_page', $_POST['billic_default_page']);
						set_config('billic_userloginurl', $_POST['billic_userloginurl']);
						set_config('billic_companyname', $_POST['billic_companyname']);
						set_config('billic_companyemail', $_POST['billic_companyemail']);
						set_config('billic_returnemail', $_POST['billic_returnemail']);
						set_config('billic_companyaddress', $_POST['billic_companyaddress']);
						set_config('billic_vatnumber', $_POST['billic_vatnumber']);
						set_config('billic_currency_code', $_POST['billic_currency_code']);
						set_config('billic_currency_prefix', $_POST['billic_currency_prefix']);
						set_config('billic_currency_suffix', $_POST['billic_currency_suffix']);
						set_config('billic_cloudflare', $_POST['billic_cloudflare']);
						set_config('amazon_ses_auth', $_POST['amazon_ses_auth_host'] . ' ' . $_POST['amazon_ses_auth_key'] . ' ' . $_POST['amazon_ses_auth_id']);
						$billic->status = 'updated';
					}
				}
			} else {
				$billic->module($_POST['billic_ajax_module']);
				$billic->enter_module($_POST['billic_ajax_module']);
				$billic->modules[$_POST['billic_ajax_module']]->settings(array());
				$billic->exit_module();
			}
		}
		if (isset($_GET['AjaxPage'])) {
			$billic->disable_content();
			if (!$billic->user_has_permission($billic->user, $_GET['AjaxPage'])) {
				err('You do not have permission to view this page');
			}
			if ($_GET['AjaxPage'] == 'Core') {
				echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="Core"><table class="table table-striped">';
				echo '<tr><th colspan="2">Developer Settings</th></tr>';
				echo '<tr><td width="175">Module Cache</td><td><input type="submit" class="btn btn-info" name="regen_cache" value="Regenerate Module Cache &raquo;"></td></tr>';
				echo '</table></form>';
				echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="Core"><table class="table table-striped">';
				echo '<tr><th colspan="2">Website Settings</th></tr>';
				echo '<tr><td width="175">Domain</td><td><input type="text" class="form-control" name="billic_domain" value="' . safe(get_config('billic_domain')) . '"><br>The (sub)domain name of your installation. For example: billic.yourcompany.com</td></tr>';
				echo '<tr><td>SSL</td><td><input type="checkbox" name="billic_ssl" value="1"' . (get_config('billic_ssl') == 1 ? ' checked' : '') . '> Enable SSL Connections?</td></tr>';
				echo '<tr><td>Force SSL</td><td><input type="checkbox" name="billic_force_ssl" value="1"' . (get_config('billic_force_ssl') == 1 ? ' checked' : '') . '> Force SSL Connections?</td></tr>';
				$domain_without_www = $_SERVER['SERVER_NAME'];
				if (substr($domain_without_www, 0, 4) == 'www.') {
					$domain_without_www = substr($domain_without_www, 4);
				}
				echo '<tr><td>Force www.</td><td><input type="checkbox" name="billic_force_www" value="1"' . (get_config('billic_force_www') == 1 ? ' checked' : '') . '> Force redirection to <b>www.</b>' . $domain_without_www . '</td></tr>';
				echo '<tr><td>Session Timeout</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="billic_session_timeout" value="' . safe(get_config('billic_session_timeout')) . '"><span class="input-group-addon" id="basic-addon2">minutes</div></div></td></tr>';
				echo '<tr><td>Lock sessions to IP address</td><td><input type="checkbox" name="billic_lock_session_ip" value="1"' . (get_config('billic_lock_session_ip') == 1 ? ' checked' : '') . '> If a user\'s IP address changes it will log them out. This prevents cookie theft.</td></tr>';
				echo '<tr><td>Default Page</td><td>';
				// get_config('billic_default_page')
				$pages = $db->q('SELECT `uri`, `menu_name` FROM `pages` ORDER BY `uri` ASC');
				if (count($pages) == 0) {
					echo 'No pages have been created yet.';
				} else {
					echo '<select class="form-control" name="billic_default_page">';
					foreach ($pages as $page) {
						echo '<option value="' . safe($page['uri']) . '"' . (get_config('billic_default_page') == $page['uri'] ? ' selected' : '') . '>/' . safe($page['uri']);
						if (!empty($page['menu_name'])) {
							echo ' &raquo; ' . safe($page['menu_name']);
						}
						echo '</option>';
					}
					echo '</select>';
				}
				echo '<tr><td>User Login URL</td><td><input type="text" class="form-control" name="billic_userloginurl" value="' . safe(get_config('billic_userloginurl')) . '"></td></tr>';
				echo '</td></tr>';
				echo '<tr><th colspan="2">Company Info</th></tr>';
				echo '<tr><td>Company Name</td><td><input type="text" class="form-control" name="billic_companyname" value="' . safe(get_config('billic_companyname')) . '"></td></tr>';
				echo '<tr><td>Company Email</td><td><input type="text" class="form-control" name="billic_companyemail" value="' . safe(get_config('billic_companyemail')) . '"></td></tr>';
				echo '<tr><td>Return Email</td><td><input type="text" class="form-control" name="billic_returnemail" value="' . safe(get_config('billic_returnemail')) . '"> Where bounce messages are sent.</td></tr>';
				echo '<tr><td>Company Address</td><td><textarea class="form-control" name="billic_companyaddress" style="width: 100%;height: 100px">' . safe(get_config('billic_companyaddress')) . '</textarea></td></tr>';
				echo '<tr><td>VAT Number</td><td><input type="text" class="form-control" name="billic_vatnumber" value="' . safe(get_config('billic_vatnumber')) . '"><br>Enter your company\'s VAT number if you have one. Otherwise leave it blank.</td></tr>';
				echo '<tr><th colspan="2">Currency Settings</th></tr>';
				echo '<tr><td>Currency Code</td><td><input type="text" class="form-control" name="billic_currency_code" value="' . safe(get_config('billic_currency_code')) . '"></td></tr>';
				echo '<tr><td>Currency Format</td><td><div class="input-group" style="width: 300px"><input type="text" class="form-control" name="billic_currency_prefix" value="' . safe(get_config('billic_currency_prefix')) . '"><span class="input-group-addon">178.58</span><input type="text" class="form-control" name="billic_currency_suffix" value="' . safe(get_config('billic_currency_suffix')) . '"></div></td></tr>';
				echo '<tr><th colspan="2">3rd Party Settings</th></tr>';
				echo '<tr><td>CloudFlare</td><td><input type="checkbox" name="billic_cloudflare" value="1"' . (get_config('billic_cloudflare') == 1 ? ' checked' : '') . '> If you are using CloudFlare for this website tick this box.<br><b>Warning:</b> If this box is ticked and you are not using cloudflare, users will be able to spoof IP addresses.</td></tr>';
				$ses = explode(' ', get_config('amazon_ses_auth'));
				echo '<tr><td>Amazon SES Host</td><td><input type="text" class="form-control" name="amazon_ses_auth_host" value="' . safe($ses[0]) . '"></td></tr>';
				echo '<tr><td>Amazon SES ID</td><td><input type="text" class="form-control" name="amazon_ses_auth_key" value="' . safe($ses[1]) . '"></td></tr>';
				echo '<tr><td>Amazon SES Key</td><td><input type="text" class="form-control" name="amazon_ses_auth_id" value="' . safe($ses[2]) . '"></td></tr>';
				echo '<tr><td colspan="2" align="center"><input type="submit" class="btn btn-success" name="update" value="Update &raquo;"></td></tr>';
				echo '</table></form>';
				exit;
			}
			$billic->module($_GET['AjaxPage']);
			$billic->enter_module($_GET['AjaxPage']);
			$billic->modules[$_GET['AjaxPage']]->settings(array());
			$billic->exit_module();
			exit;
		}
		$billic->set_title('Settings');
		echo '<h1><i class="icon-wrench"></i> Settings</h1>';
		$billic->show_errors();
		echo '<style>#dashboardLoader{left:50%;font-size:25px;margin:5em auto;width:1em;height:1em;border-radius:50%;text-indent:-9999em;-webkit-animation:load4 1.3s infinite linear;animation:load4 1.3s infinite linear;-webkit-transform:translateZ(0);-ms-transform:translateZ(0);transform:translateZ(0)}@-webkit-keyframes load4{0%,100%{box-shadow:0 -3em 0 .2em #074f99,2em -2em 0 0 #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 0 #074f99}12.5%{box-shadow:0 -3em 0 0 #074f99,2em -2em 0 .2em #074f99,3em 0 0 0 #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}25%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 0 #074f99,3em 0 0 .2em #074f99,2em 2em 0 0 #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}37.5%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 0 #074f99,2em 2em 0 .2em #074f99,0 3em 0 0 #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}50%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 0 #074f99,0 3em 0 .2em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}62.5%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 0 #074f99,-2em 2em 0 .2em #074f99,-3em 0 0 0 #074f99,-2em -2em 0 -.5em #074f99}75%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 .2em #074f99,-2em -2em 0 0 #074f99}87.5%{box-shadow:0 -3em 0 0 #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 0 #074f99,-2em -2em 0 .2em #074f99}}@keyframes load4{0%,100%{box-shadow:0 -3em 0 .2em #074f99,2em -2em 0 0 #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 0 #074f99}12.5%{box-shadow:0 -3em 0 0 #074f99,2em -2em 0 .2em #074f99,3em 0 0 0 #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}25%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 0 #074f99,3em 0 0 .2em #074f99,2em 2em 0 0 #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}37.5%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 0 #074f99,2em 2em 0 .2em #074f99,0 3em 0 0 #074f99,-2em 2em 0 -.5em #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}50%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 0 #074f99,0 3em 0 .2em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 -.5em #074f99,-2em -2em 0 -.5em #074f99}62.5%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 0 #074f99,-2em 2em 0 .2em #074f99,-3em 0 0 0 #074f99,-2em -2em 0 -.5em #074f99}75%{box-shadow:0 -3em 0 -.5em #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 .2em #074f99,-2em -2em 0 0 #074f99}87.5%{box-shadow:0 -3em 0 0 #074f99,2em -2em 0 -.5em #074f99,3em 0 0 -.5em #074f99,2em 2em 0 -.5em #074f99,0 3em 0 -.5em #074f99,-2em 2em 0 0 #074f99,-3em 0 0 0 #074f99,-2em -2em 0 .2em #074f99}}</style><script>function loadSettingsPage(page) { $( "#settingsPage" ).html(\'<div id="dashboardLoader">Loading...</div>\'); $.get( "/Admin/Settings/Module/' . $user_row['id'] . '/AjaxPage/"+encodeURIComponent(page)+"/", function( data ) { $( "#settingsPage" ).html( data ); }); } addLoadEvent(function() { loadSettingsPage(\'' . $current_module . '\'); });</script><div class="row"><div class="col-md-2" style="overflow:hidden"><div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical"><a class="nav-link' . ($current_module == 'Core' ? ' active' : '') . '" href="#" data-toggle="tab" onClick="loadSettingsPage(\'Core\')">Billic Core</a></a>';
		$modules = $billic->module_list_function('settings');
		foreach ($modules as $module) {
			$section = file_get_contents('Modules/' . $module['id'] . '.php', NULL, NULL, NULL, 1000);
			preg_match('~<i class="icon-([a-z0-9\-]+)"></i>~i', $section, $icon);
			$icon = $icon[1];
			if (empty($icon)) {
				$icon = 'puzzle';
			}
			echo '<a class="nav-link' . ($current_module == $module['id'] ? ' active' : '') . '" href="#" data-toggle="tab" onClick="loadSettingsPage(\'' . $module['id'] . '\')"><i class="icon-' . $icon . '"></i>&nbsp;' . $module['id'] . '</a>';
		}
		echo '</div></div><div class="col-md-10"><div class="tab-content" style="background: #fff;padding: 0 20px 0 20px;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;text-align: justify;text-justify: inter-word"><div class="tab-pane active" id="settingsPage" style="padding:10px"><div id="dashboardLoader">Loading...</div></div></div></div></div>';
	}
}
