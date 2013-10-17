<?php

$js = "	<script>
				function ymind_show_hide(element, show) {
					if (show == 1) {
						var show_value = 'block';
					} else {
						var show_value = 'none';
					}

					document.getElementById(element).style.display = show_value;
				}

			</script>";

echo $js;

echo '<div style="" class="wrap" id="poststuff">
			<h2>'.__('Your Minder Settings', 'ymind').'</h2>
	
			<div>';

ymind_admin_update();

$current_url = get_option('ymind_redirect_url');
$email_offender = get_option('ymind_email_offender');
$mail_subject = get_option('ymind_mail_subject');
$mail_message = get_option('ymind_mail_message');
$timeout_logins = get_option('ymind_timeout_logins');
$timeout_mins = get_option('ymind_timeout_minutes');
$lockout_mins = get_option('ymind_lockout_minutes');
$lockout_option = get_option('ymind_lockout_option');
$login_error = get_option('ymind_locked_out_error');
$activate_redirect = get_option('ymind_activate_redirect');

$td_style = 'style="vertical-align: top;"';
$caption_style = 'style="color: gray; font-style: italic;"';

echo ymind_start_box('Your Minder - Settings');

echo '	<form method="POST">
			<table class="form-table">
				<tbody>
				    	<tr class="form-field">
					    	<td width="220px" ' . $td_style . '>Force logout URL</td>
							<td>
								<input name="new_url" value="' . $current_url . '" style="width: 350px;" />
								<br/>
								<div ' . $caption_style . '>'.__('This is where the user is sent when their ip is not the same as the one they previously logged in from.', 'ymind').'</div>
							</td>
						</tr>

				    	<tr class="form-field">
					    	<td ' . $td_style . '>'.__('Multiple Login Lockout Condition', 'ymind').'</td>
							<td>
								<input name="timeout_logins" value="' . $timeout_logins . '" style="width: 50px;" /> '.__('Logins over', 'ymind').'
								<input name="timeout_minutes" value="' . $timeout_mins . '" style="width: 50px;" /> '.__('Minutes', 'ymind').'
								<br/>
								<div ' . $caption_style . '>'.__('The number of minutes between logins from multiple ip addresses that have to pass without both accounts being locked out.', 'ymind').'</div>
							</td>
						</tr>

						<tr class="form-field">
							<td ' . $td_style . '>'.__('Lockout or Logout', 'ymind').'</td>
							<td>
								<label>
									<input style="width: 30px;" type="radio" name="lockout_option" value="1" ' . ($lockout_option == '1' ? 'checked="true"':'') . ' onclick="ymind_show_hide(\'lockout_options_div\', 1);"/> '.__('Lockout', 'ymind').'
								</label>
								<label>
									<input style="width: 30px;" type="radio" name="lockout_option" value="0" ' . ($lockout_option == '0' ? 'checked="true"':'') . ' onclick="ymind_show_hide(\'lockout_options_div\', 0);"/> '.__('Logout', 'ymind').'
								</label>
								<br/>
								<div ' . $caption_style . '>'.__('This gives you the option to log the user out on a breach or lock them out completely.', 'ymind').'</div>
							</td>
						</tr>

						<div id="lockout_options_div" style="display:' . ($lockout_option == 1 ? 'block':'none') . ';">
					    	<tr class="form-field">
						    	<td ' . $td_style . '>'.__('Locked Out Login Error', 'ymind').'</td>
								<td>
									<input name="login_error" value="' . $login_error . '" style="width: 450px;" />
									<br/>
									<div ' . $caption_style . '>'.__('The message that is shown to the user on login when they have been locked out.', 'ymind').'</div>
								</td>
							</tr>
							
							<tr class="form-field">
								<td ' . $td_style . '>'.__('Email Activation or Timed lockout', 'ymind').'</td>
								<td>
									<label>
										<input style="width: 30px;" type="radio" name="email_offender" value="1" ' . ($email_offender == '1' ? 'checked="true"':'') . ' onclick="ymind_show_hide(\'lockout_mins_div\', 0); ymind_show_hide(\'lockout_email_div\', 1); "/> '.__('Email', 'ymind').'
									</label>
									<label>
										<input style="width: 30px;" type="radio" name="email_offender" value="0" ' . ($email_offender == '0' ? 'checked="true"':'') . ' onclick="ymind_show_hide(\'lockout_mins_div\', 1); ymind_show_hide(\'lockout_email_div\', 0);"/> '.__('Timed', 'ymind').'
									</label><br/>
									<div ' . $caption_style . '>'.__('Would you like to notify the owner of the account via email in the event of a breach or lock them out for a number of minutes?', 'ymind').'</div>
								</td>
							</tr>
					</tbody>
				</table>
				
				
				<table class="form-table" id="lockout_mins_div" style="display:' . ($email_offender == 1 ? 'none':'block') . ';">
					<tbody>
								<tr class="form-field">
							    	<td width="220px" ' . $td_style . '>'.__('Lockout Minutes', 'ymind').'</td>
									<td>
										<input name="lockout_minutes" value="' . $lockout_mins . '" style="width: 50px;" /> '.__('Minutes', 'ymind').'
										<br/>
										<div ' . $caption_style . '>'.__('If you selected the option to lock the user out for a period of time then how long should that be?', 'ymind').'</div>
									</td>
								</tr>								
					</tbody>
				</table>
				
				<table class="form-table" id="lockout_email_div" style="display:' . ($email_offender == 0 ? 'none':'block') . ';">
					<tbody>


								<tr class="form-field">
									<td width="220px" ' . $td_style . '>'.__('Email Subject', 'ymind').'</td>
									<td>
										<input name="mail_subject" value="' . $mail_subject . '" style="width: 350px;" />
									</td>
								</tr>

								<tr class="form-field">
									<td></td>
									<td>
										<textarea name="mail_message" style="width: 610px;">' . $mail_message . '</textarea>
										<br/>
										<div ' . $caption_style . '>'.__('The email that is sent to the user of the account with the following hook in it [activation_link].', 'ymind').'</div>
									</td>
								</tr>

								<tr class="form-field" >
									<td ' . $td_style . '>'.__('Activation Redirect', 'ymind').'</td>
									<td>
										<input name="activate_redirect" style="width: 350px;" value="' . $activate_redirect . '" />
										<br/>
										<div ' . $caption_style . '>'.__('This is an optional redirect for the user once an accepted activation link has been processed. If this box is left empty then then it will not redirect.', 'ymind').'</div>
									</td>
								</tr>


					</tbody>
				</table>
						

							<input type="submit" name="submit" value="Update" class="button" />

				</form>';

echo ymind_end_box();

echo ymind_start_box('Remove the Plugin, License or both');

	if (ymind_get('ymind_deactivate')) {
		$deactivate_url = YMIND_ADMIN_DIR_URL . 'admin.php?page=ymind/admin/ymind_admin.php';
		delete_option('ymind_license_key');
		echo '<script>window.location="' . $deactivate_url . '";</script>';
	} else if (isset($_REQUEST['ymind_uninstall']) && isset($_POST['go']) && current_user_can('edit_plugins')) {
    	check_admin_referer('deactivate-plugin_' . YMIND_PLUGIN_DIRNAME . 'ymind.php');


    	// deactivate using WP's plugin deactivation algorithm
    	$current = get_option('active_plugins');
    	array_splice($current, array_search(YMIND_PLUGIN_DIRNAME . 'ymind.php', $current), 1);
    	update_option('active_plugins', $current);

    	do_action('deactivate_' . YMIND_PLUGIN_DIRNAME . '/ymind.php');

    	ymind_deactivate(); //ymind_uninstall is in post so it will do a hard uninstall instead
    	echo '<script>document.location="' . YMIND_ADMIN_DIR_URL . '";</script>';
    } else {
	$deactivate_url = YMIND_ADMIN_DIR_URL . 'admin.php?page=' . ymind_get('page') . '&ymind_deactivate=true';
?>
		
		<p><?php _e('This will remove all Your Minder data including blocked members, IPs, and Logs. Only use this tool if you really mean to permanently remove Your Minder, otherwise deactivate/activate using the normal Plugin screen.','ymind') ?></p>
		<p><strong><?php _e('Back up before removal! Once done this can not be undone!','ymind') ?></strong></p>
		<input type="button" name="go" value="<?php _e('Remove YMIND and ALL of it\'s Data','ymind') ?>" class="button" onclick="if (confirm('<?php _e('Are you sure you want to delete Your Minder?','ymind'); ?>')) { document.forms['remove_ymind_form'].submit(); }"/>
	
	<strong>OR</strong>
	<?php echo '<a href="' . $deactivate_url . '"><input class="button" type="button" value="Just Delete the License" /></a>';
	
    }

    echo ymind_end_box();

echo '
	<form method="post" name="remove_ymind_form" id="remove_ymind_form">
		' . wp_nonce_field('deactivate-plugin_' . YMIND_PLUGIN_DIRNAME . 'ymind.php') . '
		<input type="hidden" name="ymind_uninstall" id="ymind_uninstall" value="1" />
		<input type="hidden" name="go" value="true" />
	</form>

					</div>
				</div>
				<br/><br/>
				';



ymind_render_footer();



?>