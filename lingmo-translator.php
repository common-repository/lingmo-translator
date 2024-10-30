<?php

/**
 * @package Lingmo Trnaslator
 * @version 1.0.0
 */
/*
		Plugin Name: Lingmo Translator
		Plugin URI: http://lingmo.global/
		Description: Lingmo Translator Plugin - Provides shortcode & widgets to embed Lingmo Translator in your Wordpress website
		Version: 1.0.0
		Author: Lingmo International
	*/


include(plugin_dir_path(__FILE__) . 'widget.php');

wp_cache_set('prefix_url', "");
wp_cache_set('lingmo_url', "https://live.lingmo-api.com");
//wp_cache_set('prefix_url', "/wordpress");
//wp_cache_set('lingmo_url', "http://localhost:7777");

function mylog($str)
{
}

register_activation_hook(__FILE__, 'lingmo_translator_install');
function lingmo_translator_install()
{
	global $wpdb;
	global $lingmo_translator_db_version;

	$installed_ver = get_option("lingmo_translator_db_version");
	if ($installed_ver != $lingmo_translator_db_version) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		add_option("lingmo_translator_db_version", $lingmo_translator_db_version);
	}
}

function register_lingmo_translator_settings()
{
	register_setting('lingmo_translator_option-group', 'lingmo_translator_api_key');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_source_language');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_widget_layout');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_api_license_info');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_target_languages_api');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_target_languages_wp');
	register_setting('lingmo_translator_option-group', 'lingmo_translator_widget_lang_selection');
}


function lingmo_translator_menu()
{
	//add options page on main menu in left
	add_menu_page("Lingmo Translator Options", "Lingmo Translator", "administrator", "lingmo_translator-general", "lingmo_translator_page_general", "dashicons-admin-site-alt3", 99);

	add_action("admin_init", "register_lingmo_translator_settings");
}

function get_lingmo_token()
{

	if (!empty(get_option('lingmo_translator_api_token_data'))) {
		$token_data = json_decode(get_option('lingmo_translator_api_token_data'));
	}
	if ((get_option('lingmo_translator_api_key') !== false) && (time() > $token_data->ExpiresAtTimestamp)) {
		$url = wp_cache_get('lingmo_url') . '/v1/token/get/' . get_option('lingmo_translator_api_key');
		$response = wp_remote_get($url);
		$response_code = wp_remote_retrieve_response_code($response);
		$result = wp_remote_retrieve_body($response); 

		if ($response_code == 200) {
			if (get_option('lingmo_translator_api_token_data') === false) {
				add_option('lingmo_translator_api_token_data', $result);
			} else {
				update_option('lingmo_translator_api_token_data', $result);
			}
			$token_data = json_decode(get_option('lingmo_translator_api_token_data'));
		} else {
			$token_data = $error_msg;
		}
	}
	return $token_data;
}

function lingmo_translator_page_general()
{
	global $wpdb, $user;

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	$token_data = get_lingmo_token();

	if (isset($token_data->Token)) {

		if ((get_option('lingmo_translator_target_languages_api') === false) || empty(get_option('lingmo_translator_target_languages_api')) || (json_decode(get_option('lingmo_translator_target_languages_api')) == "Token is invalid")) {

			$file = plugin_dir_path( __FILE__ ) . '/langs.json'; 
			$result = file_get_contents($file);


			if (get_option('lingmo_translator_target_languages_api') === false) {
				add_option('lingmo_translator_target_languages_api', $result);
			} else {
				update_option('lingmo_translator_target_languages_api', $result);
			}
		}

		if ((get_option('lingmo_translator_api_license_info') === false) || empty(get_option('lingmo_translator_api_license_info')) || (json_decode(get_option('lingmo_translator_api_license_info')) == "Token is invalid")) {

			$args = array(
				'headers' => array (
					'Authorization' => trim($token_data->Token)
				)
			);

			$response = wp_remote_get(wp_cache_get('lingmo_url') . '/v1/translation/getlicense', $args );
			$response_code = wp_remote_retrieve_response_code($response);
			$result = wp_remote_retrieve_body($response); 

			if ($response_code != 200) {
					$error_msg = "Unable to get license information";
			}

			if (get_option('lingmo_translator_api_license_info') === false) {
				add_option('lingmo_translator_api_license_info', $result);
			} else {
				update_option('lingmo_translator_api_license_info', $result);
			}
		}
	}

?>
	<div class="wrap">
		<div id="dialog-api-form" title="Create Trial API Key">
			<p class="validateTips">All form fields are required.</p>
			
				<table>
					<tr>
						<th>Name:</th>
						<td><input type="text" name="name" id="api_name" placeholder="Jane Smith" class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Email:</th>
						<td><input type="text" name="email" id="api_email" placeholder="jane@smith.com" class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Password:</th>
						<td><input type="password" name="password" id="api_password" value="" class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Re-enter Password:</th>
						<td><input type="password" name="password2" id="api_password2" value="" class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Phone number:</th>
						<td><input type="text" name="phone" id="api_phone" placeholder="111-222-333" class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Company:</th>
						<td><input type="text" name="company" id="api_company" placeholder="Smith Co." class="text ui-widget-content ui-corner-all"></td>
					</tr>
					<tr>
						<th>Website:</th>
						<td><input type="text" name="website" id="api_website" placeholder="https://smith.co" class="text ui-widget-content ui-corner-all"></td>
					</tr>
				</table>
		</div>
		<h2>Lingmo Translator Settings</h2>
		<form method="post" action="options.php">
			<?php settings_fields('lingmo_translator_option-group'); ?>
			<?php do_settings_sections('lingmo_translator_option-group'); ?>
			<div class="postbox-container lingmo_left_col">
				<div id="poststuff">
					<div class="postbox">
						<div class="inside">
							<h3 id="settings">API Settings</h3>
							<table class="form-table" style="width: 100%;" cellpadding="4">
								<tr valign="top">
									<th scope="row">Lingmo API Key</th>
									<td>
										<table>
											<tr>
												<td>
												<input type="text" size="30" name="lingmo_translator_api_key" value="<?php echo esc_attr(get_option('lingmo_translator_api_key')); ?>" /> <?php empty(get_option('lingmo_translator_api_key')) ? submit_button() : ""; ?>
												</td>
												<td><a href='https://lingmointernational.com/lingmo-translator-subscription/' target='_blank'>Get Your API key</a></td>
												<td><a id='open-api-dialog' href='javascript:void(0)'>Get Your Trial API key</a></td>
											<tr>
										</table>
									</td>
								</tr>
								<?php
								$lingmo_license_info = json_decode(get_option('lingmo_translator_api_license_info'));
								?>
								<tr valign="top">
									<th scope="row">Lingmo License Details</th>
									<td>
										<table class="inner_table">
											<tr valign="top">
												<th scope="row">Customer Id</th>
												<td><?php echo $lingmo_license_info->data->CustomerId ?></td>
											</tr>
											<tr valign="top">
												<th scope="row">License Plan</th>
												<td><strong>Id:</strong> <?php echo $lingmo_license_info->data->PlanId ?>&nbsp;&nbsp;&nbsp;<strong>Name:</strong> <?php echo $lingmo_license_info->data->PlanName ?>&nbsp;&nbsp;&nbsp;<strong>Expiry:</strong> <?php echo date("Y-m-d H:i:s", $lingmo_license_info->data->PlanDueDateTimestamp) ?></td>
											</tr>
											<tr valign="top">
												<th scope="row">Quota</th>
												<td>
													<strong>TR:</strong> <?php echo $lingmo_license_info->data->TR_TotalConsumed ?>/<?php echo $lingmo_license_info->data->TR_TotalRequests ?>&nbsp;&nbsp;&nbsp;
													<strong>TTS:</strong> <?php echo $lingmo_license_info->data->STT_TotalConsumed ?>/<?php echo $lingmo_license_info->data->STT_TotalRequests ?>&nbsp;&nbsp;&nbsp;
													<strong>STT:</strong> <?php echo $lingmo_license_info->data->TTS_TotalConsumed ?>/<?php echo $lingmo_license_info->data->TTS_TotalRequests ?>&nbsp;&nbsp;&nbsp;
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Widget Layout</th>
									<td>
										<?php
										$widget_options = array("Dropdown" => "dropdown", "Dropdown with flags" => "dropdown_flags", "Flags and dropdown" => "flags_dropdown", "Flags only" => "flags", "Flags with language name" => "flags_lang_name", "Flags with language code" => "flags_lang_code");
										?>
										<select name="lingmo_translator_widget_layout">
											<option value=""></option>
											<?php
											foreach ($widget_options as $key => $value) {
											?>
												<option value="<?php echo $value ?>" <?php selected(get_option('lingmo_translator_widget_layout'), $value); ?>><?php echo $key ?></option>
											<?php
											}
											?>
										</select>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Default / Source Language</th>
									<td>
										<?php echo get_locale(); ?>&nbsp;&nbsp;&nbsp;[ from <a href="<?php echo site_url("wp-admin/options-general.php") ?>">Settings > General > Site Language</a> ]
									</td>
								</tr>
								<tr valign="top">
									<th scope="row">Target Laguages</th>
									<td class="lang-list">
										<div><a href="javascript:lingmo_check_toggle(true);">Check All</a>&nbsp;&nbsp;&nbsp;<a href="javascript:lingmo_check_toggle(false);">Uncheck All</a></div>
										<div id="lingmo_target_languages_list">
											<!-- <?php echo esc_attr(get_option('lingmo_translator_target_languages_api')); ?> -->
											<?php
											$lingmo_laguages = json_decode(get_option('lingmo_translator_target_languages_api'));
											$options = get_option('lingmo_translator_target_languages_wp');

											if (!empty($lingmo_laguages->data)) {
												foreach ($lingmo_laguages->data as $lang) {
											?>
													<div class="lang"><label><input type="checkbox" name="lingmo_translator_target_languages_wp[<?php echo $lang->LanguageId ?>]" value="1" <?php isset($options[$lang->LanguageId]) ? checked(1, $options[$lang->LanguageId], true) : ""; ?> />&nbsp;<?php echo $lang->LanguageName ?></label></div>
											<?php
												}
											}
											?>
										</div>
									</td>
								</tr>
							</table>
							<div class="submit-btn-wrapper">
								<?php submit_button(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
		<div class="postbox-container lingmo_right_col">
			<div id="poststuff">
				<div class="postbox">
					<div class="inside">
						<h3 id="settings">Widget preview</h3>
						<?php echo do_shortcode("[lingmo-translator]") ?>
					</div>
				</div>
			</div>
			<div id="poststuff">
				<div class="postbox">
					<div class="inside">
						<h3 id="settings">Lingmo News</h3>
						Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate, placeat? Excepturi cumque iste beatae alias nostrum, quibusdam quas porro culpa reprehenderit in, sint eius eum deleniti earum non quaerat consequuntur?<br><br>
						Lorem ipsum dolor sit amet consectetur adipisicing elit. Soluta harum doloribus, reprehenderit delectus repellat dolorum debitis ex, adipisci at dolorem facilis cupiditate, exercitationem atque et quia aliquam maxime nisi maiores.
					</div>
				</div>
			</div>
			<div style="clear: both;"></div>
			<div style="text-align: right;"><span style="font-weight: bold;">&copy;</span> <a href="http://lingmo.global">Lingmo.global</a></div>
		</div>
	</div>
<?php
}


// displays error messages from form submissions
function lingmo_translator_show_error_messages()
{
	if ($codes = lingmo_translator_errors()->get_error_codes()) {
		echo '<div class="lingmo_translator_errors">';
		foreach ($codes as $code) {
			$message = lingmo_translator_errors()->get_error_message($code);
			echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		}
		echo '</div>';
	}
}

// used for tracking error messages
function lingmo_translator_errors()
{
	static $wp_error; // Will hold global variable safely
	return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}

// Custom CSS & JS for this Plugin to be added to <head>
function lingmo_translator_head()
{
?>
	<style type="text/css">
		.lang-list .lang {
			float: left;
			width: 25%;
			margin: 5px;
			padding: 2px;
		}

		.lingmo_left_col {
			width: 68%;
		}

		.lingmo_right_col {
			width: 28%;
			float: right;
		}

		.lingmo_right_col #poststuff,
		.lingmo_left_col #poststuff {
			min-width: 0;
		}

		.form-table .inner_table th {
			width: 80px;
			padding: 5px;
		}

		.form-table .inner_table td {
			padding: 5px;
		}
	</style>
	<script>
		function lingmo_check_toggle(set_flag) {
			jQuery("#lingmo_target_languages_list input[type='checkbox']").each(function(i, e) {
				e.checked = set_flag;
			})
		}
	</script>
<?php
}

/*
	*	Render front end
	*/
function lingmo_translator_render($atts)
{
	$token_data = get_lingmo_token();
	$widget_layout = get_option("lingmo_translator_widget_layout", "dropdown");
?>
	<script>
		var lingmo_token = '<?php echo trim($token_data->Token) ?>';
		var lang_src = '<?php echo get_locale() ?>';
		var lang_target;
		var widget_layout = '<?php echo $widget_layout ?>';


		var original_elements = new Array();
		var original_elements_text = new Array();
		var translated_text = new Array();
		var lang_target_direction = "inherit";


		function lingmo_translate(src_lang, target_lang, dir_lang, token_val) {
			if (src_lang === target_lang) {
				return;
			}

			var lingmo_api_url = "<?=wp_cache_get('lingmo_url') ?>/v1/translation/dotranslatelines?sourceLang=" + src_lang + "&targetLang=" + target_lang;

			jQuery.ajax({
				url: lingmo_api_url,
				type: "POST",
				headers: {
					Authorization: token_val
				},
				data: JSON.stringify(original_elements_text),
				dataType: "json",
				success: function(data) {
					if (data.IsError === false) {
						original_elements.forEach(function(element, index) {
							translated_text[index] = data.ResponseTexts[index].Text;
							element.nodeValue = data.ResponseTexts[index].Text;
						});
					} else {
						console.log("-- API Error--");
						console.log(data.Description);
						jQuery("#lingmo_lang_error_wrapper").html("<span style='color: red; font-size: 10px;'><strong>API Error: </strong>" + data.Description + "</span>");
					}
				},
				error: function(jqXHR, exception) {
					console.info(jqXHR);
					console.info(exception);
				}
			});
		}

		function lingmo_token_call(src, trg, dir) {

			//clear any errors showing
			jQuery("#lingmo_lang_error_wrapper").html("");

			jQuery.ajax({
				url: "<?=wp_cache_get('prefix_url') ?>/wp-admin/admin-ajax.php",
				type: "POST",
				dataType: "JSON",
				data: {
					action: "call_get_token_ajax"
				},
				success: function(resp) {
					if (resp.success) {
						lingmo_token = resp.data;
						lingmo_translate(src, trg, dir, lingmo_token);
					} else {
						console.log("Error getting Lingmo API Token");
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					console.log("AJAX Error: " + thrownError.message);
				}
			});
		}

		function lingmo_switch_original() {
			original_elements.forEach(function(element, index) {
				element.nodeValue = original_elements_text[index].Text;
			});
		}

		function lingmo_switch_lang(flag_obj) {

			console.log(jQuery(flag_obj).find("img").data("value"));
			lang_target = jQuery(flag_obj).find("img").data("value");
			lang_target_direction = jQuery(flag_obj).find("img").data("direction");

			lingmo_token_call(
				lang_src.replace("_", "-"),
				lang_target,
				lang_target_direction
			);
		}
		function lingmo_create_account() {
			var api_name = jQuery("#api_name").val();
			var api_email = jQuery("#api_email").val();
			var api_password = jQuery("#api_password").val();
			var api_password2 = jQuery("#api_password2").val();
			var api_phone = jQuery("#api_phone").val();
			var api_company = jQuery("#api_company").val();
			var api_website = jQuery("#api_website").val();

			if(api_name == "") { alert("Name is required"); return; }
			if(api_email == "") { alert("Email is required"); return; }
			if(api_password == "") { alert("Password is required"); return; }
			if(api_phone == "") { alert("Phone is required"); return; }
			if(api_company == "") { alert("Company is required"); return; }
			if(api_website == "") { alert("Website is required"); return; }
			if(api_password != api_password2) { alert("Passwords are not same"); return; }

			//clear any errors showing
			jQuery("#lingmo_lang_error_wrapper").html("");

			jQuery.ajax({
				url: "<?=wp_cache_get('prefix_url') ?>/wp-admin/admin-ajax.php",
				type: "POST",
				dataType: "JSON",
				data: {
					action: "call_create_trial_account",
					name: api_name,
					email: api_email,
					password: api_password,
					phone: api_phone,
					company: api_company,
					website: api_website
				},
				success: function(resp) {
					console.log("resp: ",resp);
					if (resp.success) {
						if(resp.data.status){
							alert("Account created. Please check email.");
							jQuery( "#dialog-api-form" ).dialog("close");
						} else {
							alert("Error in creating account: "+resp.data.message);
						}
					} else {
						alert("Unable to create account.");
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					alert("Unable to create account. Try with another email account");
					console.log("AJAX Error: " + thrownError.message);
				}
			});
		}
		jQuery(document).ready(function() {
			var dialog = jQuery( "#dialog-api-form" ).dialog({
			autoOpen: false,
			height: 450,
			width: 350,
			modal: true,
			buttons: {
				"Create an account": function(){
					lingmo_create_account();
				},
				Cancel: function() {
					dialog.dialog( "close" );
				}
			},
			close: function() {
			}
			});
			jQuery("#open-api-dialog").click(function() {
				dialog.dialog("open");
			});
			//find only visible text nodes within document body

			var text_nodes = jQuery("body *").not('script').not('#lingmo_widget_wrapper *').contents()
				.filter(function() {
					return (this.nodeType === Node.TEXT_NODE);
				});

			//reset the source arrays and push found text nodes into them
			original_elements = new Array();
			original_elements_text = new Array();
			translated_text = new Array();

			text_nodes.each(function(index, element) {
				var cleaned_text = jQuery.trim(element.nodeValue);
				if (cleaned_text != "") {
					original_elements.push(element);
					original_elements_text.push({
						Tag: index,
						Text: cleaned_text
					});
				}
			});

			<?php
			if (($widget_layout == "dropdown") || ($widget_layout == "flags_dropdown")) {
			?>
				jQuery("#lingmo_translator_widget_lang_selection").on("change", function() {
					if (jQuery(this).val() !== "") {
						//set target lang global var
						lang_target = jQuery(this).val();
						lang_target_direction = jQuery(this)
							.find(":selected")
							.data("direction");

						lingmo_token_call(
							lang_src.replace("_", "-"),
							lang_target,
							lang_target_direction
						);
					}
				});
			<?php
			} elseif ($widget_layout == "dropdown_flags") {
			?>
				//jquery select menu custom rendering - country list with flags
				jQuery.widget("custom.lingmoflagsmenu", jQuery.ui.selectmenu, {
					_renderItem: function(ul, item) {
						var li = jQuery("<li>"),
							wrapper = jQuery("<div>", {
								text: item.label
							});

						if (item.disabled) {
							li.addClass("ui-state-disabled");
						}

						jQuery("<span>", {
							style: item.element.attr("data-style"),
							class: "ui-icon " + item.element.attr("data-class")
						}).appendTo(wrapper);

						return li.append(wrapper).appendTo(ul);
					}
				});

				jQuery("#lingmo_translator_widget_lang_selection")
					.lingmoflagsmenu({
						change: function(e, ui) {
							if (ui.item.value != "") {
								//set target lang global var
								lang_target = ui.item.value;
								lang_target_direction = ui.item.element.data("direction");

								lingmo_token_call(
									lang_src.replace("_", "-"),
									lang_target,
									lang_target_direction
								);
							}
						} //change
					})
					.lingmoflagsmenu("menuWidget")
					.addClass("ui-menu-icons lingmo_flags_dropdown");
			<?php
			}
			?>
		});
	</script>
	<div id="lingmo_widget_wrapper">
		<div class="lingmo_revert_original"><a href="#" onclick="lingmo_switch_original(); return false;">Revert to original language</a></div>
		<?php
		if (($widget_layout == "flags_dropdown") || ($widget_layout == "flags") || ($widget_layout == "flags_lang_name") || ($widget_layout == "flags_lang_code")) {
			if ((get_option('lingmo_translator_target_languages_api') !== false) && (get_option('lingmo_translator_target_languages_wp') !== false)) {
				$lingmo_laguages = json_decode(get_option('lingmo_translator_target_languages_api'));
				$lang_options = get_option('lingmo_translator_target_languages_wp');
				if ($lingmo_laguages) {
					foreach ($lang_options as $key => $value) {
						//find the matching laguage object in api returned language data
						$lang = $lingmo_laguages->data[array_search($key, array_column($lingmo_laguages->data, 'LanguageId'))];
		?>
						<a href="#" onclick="lingmo_switch_lang(this); return false;" class="lingmo_lang_flag">
							<img alt="<?php echo $lang->LanguageId ?>" title="<?php echo $lang->LanguageName ?>" src="<?php echo plugins_url("/flags/" . str_replace(" ", "_", str_replace(", ", "_", $lang->ImgName)) . ".png", __FILE__) ?>" data-class="lingmo_flag_icon" data-direction="<?php echo $lang->TextDirection ?>" data-separator="<?php echo $lang->SentenceSeparator ?>" data-textonly="<?php echo $lang->TextOnly ? "true" : "false" ?>" data-imgname="<?php echo str_replace(" ", "_", str_replace(", ", "_", $lang->ImgName)) ?>" data-value="<?php echo $key ?>" class="lingmo_flag_icon <?php selected(get_option('lingmo_translator_widget_lang_selection'), $key); ?>">
							<?php
							if ($widget_layout == "flags_lang_name") {
								echo "<br>" . $lang->LanguageName;
							}
							?>
							<?php
							if ($widget_layout == "flags_lang_code") {
								echo "<br>" . $lang->LanguageId;
							}
							?>
						</a>
			<?php
					}
				}
			}
		}
		if (($widget_layout == "dropdown") || ($widget_layout == "dropdown_flags") || ($widget_layout == "flags_dropdown")) {
			?>
			<select name="lingmo_lang" id="lingmo_translator_widget_lang_selection" style="width: 100%;">
				<option value="">Select Language</option>
				<?php
				if ((get_option('lingmo_translator_target_languages_api') !== false) && (get_option('lingmo_translator_target_languages_wp') !== false)) {
					$lingmo_laguages = json_decode(get_option('lingmo_translator_target_languages_api'));
					$lang_options = get_option('lingmo_translator_target_languages_wp');
					if ($lingmo_laguages) {
						foreach ($lang_options as $key => $value) {
							//find the matching laguage object in api returned language data
							$lang = $lingmo_laguages->data[array_search($key, array_column($lingmo_laguages->data, 'LanguageId'))];
				?>
							<option data-class="lingmo_flag_icon" data-style="background-image: url(&apos;<?php echo plugins_url("/flags/" . str_replace(" ", "_", str_replace(", ", "_", $lang->ImgName)) . ".png", __FILE__) ?>&apos;);" data-direction="<?php echo $lang->TextDirection ?>" data-separator="<?php echo $lang->SentenceSeparator ?>" data-textonly="<?php echo $lang->TextOnly ? "true" : "false" ?>" data-imgname="<?php echo str_replace(" ", "_", str_replace(", ", "_", $lang->ImgName)) ?>" value="<?php echo $key ?>" <?php selected(get_option('lingmo_translator_widget_lang_selection'), $key); ?>><?php echo $lang->LanguageName ?></option>
				<?php
						}
					}
				}
				?>
			</select>
		<?php
		}
		?>
		<div id="lingmo_lang_error_wrapper"></div>
	</div>
<?php
} //lingmo_translator_render



function lingmo_translator_get_token_ajax()
{

	$token_data = get_lingmo_token();

	if (!isset($token_data->Token)) {
		$return_value = 'Error getting Lingmo API Token.';
		wp_send_json_error($return_value);
	}
	wp_send_json_success($token_data->Token);
}

add_action('wp_ajax_call_get_token_ajax', 'lingmo_translator_get_token_ajax');
add_action('wp_ajax_nopriv_call_get_token_ajax', 'lingmo_translator_get_token_ajax');

function lingmo_translator_create_trial_account()
{
	$name = $_POST['name'];
	$email = $_POST['email'];
	$password = $_POST['password'];
	$phone = $_POST['phone'];
	$company = $_POST['company'];
	$website = $_POST['website'];

	$url = wp_cache_get('lingmo_url') . '/v1/token/RegisterAPI';
	$args = array(
		'body' => array(
			'username' => $email,
			'name' => $name,
			'password' => $password,
			'phone' => $phone,
			'company' => $company,
			'website' => $website
		)
	);
	$response = wp_remote_post($url,$args);
	$response_code = wp_remote_retrieve_response_code($response);
	$result = wp_remote_retrieve_body($response); 

	if ($response_code == 200) {
		wp_send_json_success(json_decode($result));
	} else {
		wp_send_json_error(json_decode($result));
	}

}

add_action('wp_ajax_call_create_trial_account', 'lingmo_translator_create_trial_account');

function lingmo_translator_register_plugin_styles()
{
	$wp_scripts = wp_scripts();
	wp_enqueue_style('plugin_name-admin-ui-css', plugins_url('/css/jquery-ui.css' , __FILE__));

	//enqueue custom js
	wp_enqueue_script('lingmo_translator-script', plugins_url('/js/lingmo-translator.js', __FILE__), array('jquery', 'jquery-ui-widget', 'jquery-ui-selectmenu', 'jquery-ui-dialog'));

	//enqueue custom css
	wp_register_style('lingmo_translator_results', plugins_url('/css/lingmo-translator.css', __FILE__));
	wp_enqueue_style('lingmo_translator_results');

}
add_action('wp_enqueue_scripts', 'lingmo_translator_register_plugin_styles', 999);

function lingmo_translator_register_plugin_styles_admin($hook)
{
	if ('toplevel_page_lingmo_translator-general' != $hook) {
		return;
	}

	lingmo_translator_register_plugin_styles();
}
add_action('admin_enqueue_scripts', 'lingmo_translator_register_plugin_styles_admin');


if (is_admin()) { // admin actions
	add_action('admin_menu', 'lingmo_translator_menu');
	add_action('admin_head', 'lingmo_translator_head');
}

add_shortcode('lingmo-translator', 'lingmo_translator_render');
