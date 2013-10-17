<?php

/*
* $Id: ym-advanced-registration.php 2166 2012-05-25 14:16:20Z bcarlyon $
* $Revision: 2166 $
* $Date: 2012-05-25 15:16:20 +0100 (Fri, 25 May 2012) $
*/

global $ym_formgen, $ym_res, $ym_sys;

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	// strip spaces just in case
	$ym_sys->register_https_pages = str_replace(' ', '', $ym_sys->register_https_pages);

	update_option('ym_sys', $ym_sys);

	ym_display_message(__('System Updated','ym'));

	update_option('users_can_register', ym_post('wp_users_can_register'));

	echo '<meta http-equiv="refresh" content="5;' . YM_ADMIN_URL . '&ym_page=ym-advanced-registration' . '" />';
	return;
}

$wp_users_can_register = get_option('users_can_register');


echo '
<form method="post">
<div class="wrap" id="poststuff">
';
echo ym_start_box(__('Registration Settings', 'ym'));
echo '<table class="form-table">';

$ym_formgen->render_form_table_radio_row(__('Enable WordPress Registration', 'ym'), 'wp_users_can_register', $wp_users_can_register, __('If you are using Registration Flows only, you can Turn this off. It is a Duplicate of the Option found under Settings -> Generate (Membership/Anyone Can Register', 'ym'));

$ym_formgen->render_form_table_url_row(__('Block wp-Login.php?action=register', 'ym'), 'block_wp_login_action_register', $ym_sys->block_wp_login_action_register, __('If Using Registration Flows Only, you can redirect requests for wp-login.php?action=register to this URL', 'ym'));

$ym_formgen->render_form_table_radio_row(__('Enable Modified Registration', 'ym'), 'modified_registration', (int)$ym_sys->modified_registration, __('Adds an extra step to the registration process', 'ym'));

$ym_formgen->render_form_table_radio_row(__('Disable custom registration fields?', 'ym'), 'hide_custom_fields', (int)$ym_sys->hide_custom_fields, __('Choosing Yes will hide custom registration fields from a new Member. But will still show them in their profile', 'ym'));

$ym_formgen->render_form_table_text_row(__('What to show next to required custom fields?', 'ym'), 'required_custom_field_symbol', $ym_sys->required_custom_field_symbol, __('Most commonly "* Required"', 'ym'));

?>
	</table>
	
	<p class="submit" style="text-align: right;">
		<input type="submit" class="button-primary" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" />
	</p>
<?php

echo ym_end_box();
echo '</div>';

echo '<div class="wrap" id="poststuff">
';

echo ym_box_top(__('SSL Control', 'ym'));

echo '<p>' . __('This control is redudant if your entire site runs over SSL', 'ym') . '</p>';

$url = get_option('siteurl');
if (substr($url, 0, 5) == 'https') {
	echo '<p>' . __('And you are running in SSL Only', 'ym') . '</p>';
} else {
echo '<table class="form-table">';
$ym_formgen->render_form_table_radio_row(__('Enable SSL Only WordPress Registration', 'ym'), 'register_https_only', $ym_sys->register_https_only);
//$ym_formgen->render_form_table_radio_row(__('Escape SSL When Not Registration', 'ym'), 'register_https_escape', $ym_sys->register_https_escape);
echo '</table>';

echo '<p>' . __('You can enable Registration to be done over SSL, in the case of Registration Flow Pages, or Pages that have the [ym_register] shortcode on, in order to avoid a meta Refresh, this control exists', 'ym') . '</p>';

echo '<table class="form-table">';
$ym_formgen->render_form_table_text_row(__('Comma Separated List of Page/Post IDs', 'ym'), 'register_https_pages', $ym_sys->register_https_pages);
?>
	</table>
	<p class="submit" style="text-align: right;">
		<input type="submit" class="button-primary" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" />
	</p>
<?php
}

echo ym_end_box();
echo '</div></form>';
