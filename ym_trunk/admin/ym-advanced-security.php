<?php

global $ym_formgen, $ym_res, $ym_sys;

$full_protect_options = array(
	0 => 'Flexible',
	1 => 'Full',
);

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

$roles = new WP_Roles();
$roles_array = $roles->role_names;

echo '<div class="wrap" id="poststuff">
<form action="" method="post">
';

echo ym_box_top(__('Content Protection Settings', 'ym'));

//$selected = $ym_sys->ym_hide_posts ? 2 : ($ym_sys->magic_mode ? 1 : 0);

echo '<table class="form-table">';
echo $ym_formgen->render_combo_from_array_row(__('Post Protection', 'ym'), 'protect_mode', $full_protect_options, $ym_sys->protect_mode);
echo '</table>';

echo '<p>' . __('<strong>Flexible</strong> is the default option, allowing multiple private tags within the content, and will not redirect', 'ym') . '</p>';
echo '<p>' . __('<strong>Full</strong> Protects the entire the content, redirecting to a location if access is not permitted to the post.', 'ym') . '</p>';

// permanent loop occurs when the page looping to has tags on
echo '
<table class="form-table">
<tr>
	<th>' . __('Logged out user No Access URL', 'ym') . '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . __('If not a valid permalink to a page, a permanent loop can occur', 'ym') . '</div></th>
	<td>' . site_url() . '
		<input class="ym_input" name="no_access_redirect_lo" value="' . $ym_sys->no_access_redirect_lo . '" style="width: 400px;" />
	</td>
</tr>
<tr>
	<th>' . __('Logged in user No Access URL', 'ym') . '</th>
	<td>' . site_url() . '
		<input class="ym_input" name="no_access_redirect_li" value="' . $ym_sys->no_access_redirect_li . '" style="width: 400px;" />
	</td>
</tr>
</table>
';

echo '<p>' . __('You can optionally enable these redirects to fire on the index/home page as opposed to just on the individual content pages', 'ym') . '</p>';
echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Redirect on Homepage', 'ym'), 'redirect_on_homepage', $ym_sys->redirect_on_homepage);
echo '<tr><td colspan="2"><p>' . __('If Full Protection on, with Yes will redirect on Direct view, otherwise a 404 Page Occurs', 'ym') . '</p></td></tr>';
echo $ym_formgen->render_form_table_radio_row(__('Hide Pages from Menus', 'ym'), 'hide_pages', $ym_sys->hide_pages);
echo $ym_formgen->render_form_table_radio_row(__('Hide Posts from Indexes', 'ym'), 'hide_posts', $ym_sys->hide_posts);
echo '</table>';

echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('WP Login Page', 'ym'));
echo '<p>' . __('If enabled this will change the URL that the WordPress logo links to on the WP-Login Page', 'ym') . '</p>';
echo '<table class="form-table">';
echo '
<tr>
	<th>' . __('URL to link to, leave blank to disable and / for HomePage', 'ym') . '</th>
	<td>' . site_url() . '
		<input class="ym_input" name="wp_login_header_url" value="' . $ym_sys->wp_login_header_url . '" style="width: 400px;" />
	</td>
</tr>
';
echo '
<tr>
	<th>' . __('Logo to use, leave blank to use WordPress logo, the dimensions should match 326px wide and 67px tall', 'ym') . '</th>
	<td>' . site_url() . '
		<input class="ym_input" name="wp_login_header_logo" value="' . $ym_sys->wp_login_header_logo . '" style="width: 400px;" />
	</td>
</tr>
';
echo '</table>';
echo ym_box_bottom();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('Default Redirects', 'ym'));

echo '<p>' . __('When a User triggers a redirect, a pack specfic redirect will be used or one of these defaults', 'ym') . '</p>';

echo '<table class="form-table">';

echo $ym_formgen->render_form_table_url_row(__('Redirect on login', 'ym'), 'login_redirect_url', $ym_sys->login_redirect_url, __('URL to redirect Member on login (leave blank for wp-admin)', 'ym'));
echo $ym_formgen->render_form_table_url_row(__('No WP Admin URL', 'ym'), 'wpadmin_disable_redirect_url', $ym_sys->wpadmin_disable_redirect_url, __('URL to redirect Member from wp-admin (leave blank if you wish members to have access to wp-admin)', 'ym'));
echo $ym_formgen->render_form_table_url_row(__('Redirect on Logout', 'ym'), 'logout_redirect_url', $ym_sys->logout_redirect_url, __('URL to redirect Member to when they logout, (redirect_to as a $_REQUEST var overrides (from wp_logout_url))', 'ym'));
echo $ym_formgen->render_form_table_radio_row(__('Hide the membership content page?', 'ym'), 'hide_membership_content', $ym_sys->hide_membership_content);

echo '</table>';
echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('Misc Links', 'ym'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_url_row(__('Login Link', 'ym'), 'ym_get_login_link_url', $ym_sys->ym_get_login_link_url, __('Replaces the link applied to the Private Shortcode Blue box and YM Sidebar Widgets', 'ym'));
echo $ym_formgen->render_form_table_url_row(__('Register Link', 'ym'), 'ym_get_register_link_url', $ym_sys->ym_get_register_link_url, __('Replaces the link applied to the Private Shortcode Blue box and YM Sidebar Widgets', 'ym'));

echo $ym_formgen->render_form_table_url_row(__('Upgrade Link', 'ym'), 'upgrade_link', $ym_sys->upgrade_link, __('Replaces the upgrade/downgrade link URL in the [ym_user_profile] shortcode and on the default Membership Details page', 'ym'));
echo $ym_formgen->render_form_table_text_row(__('Link Text to show for upgrade/downgrade link (Profile)', 'ym'), 'upgrade_downgrade_string', $ym_sys->upgrade_downgrade_string, __('Found on Membership Details and ym_profile shortcode', 'ym'));

echo $ym_formgen->render_form_table_url_row(__('Membership Details URL', 'ym'), 'membership_details_redirect_url', $ym_sys->membership_details_redirect_url, __('If you have a custom page for Membership Details/Profile enter it here', 'ym'));

echo '</table>';
echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('RSS Token Settings'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Enable RSS Tokens', 'ym'), 'use_rss_token', (int)$ym_sys->use_rss_token);
echo '</table>';
echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('Your Members Admin Area Access', 'ym'));
echo '<table class="form-table">';

echo $ym_formgen->render_combo_from_array_row(__('WP Role Level', 'ym'), 'admin_role', $roles_array, $ym_sys->admin_role);
echo $ym_formgen->render_combo_from_array_row(__('Minimum WP Role for Content User interface', 'ym'), 'account_interface_admin_role', $roles_array, $ym_sys->account_interface_admin_role, __('The minimum WP Role that the user must have to see the account interface (post and page editor sidebar box for authoring) for YM', 'ym'));

echo '</table>';
echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
<?php

echo ym_box_top(__('Dev Tools', 'ym'));
echo $ym_formgen->render_form_table_radio_row(__('Dev Tools', 'ym'), 'dev_tools', $ym_sys->dev_tools);
echo ym_end_box();

?>

<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>

</form>
</div>
