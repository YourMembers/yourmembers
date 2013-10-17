 <?php

/*
* $Id: yss_settings.php 1951 2012-02-29 09:36:13Z bcarlyon $
* $Revision: 1951 $
* $Date: 2012-02-29 09:36:13 +0000 (Wed, 29 Feb 2012) $
*/

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$keys_to_update = array(
		'yss_user_key',
		'yss_secret_key',
		'yss_playerofchoice',
		'yss_no_access_message',
		'yss_custom_player',
			
		'yss_cloudfront_id',
		'yss_cloudfront_public',
		'yss_cloudfront_private',
	);
		
	foreach ($keys_to_update as $key) {
		update_option($key, stripcslashes($_POST[$key]));
	}
}
	
// This plugin is dependent on ym so I can use its functions....
echo ym_start_box('No Access Message');

echo '<table class="form-table">';
$ym_formgen->render_form_table_textarea_row('No access message', 'yss_no_access_message', get_option('yss_no_access_message'), 'This will be shown if the video is unavailable to the user. Leave it blank to not show anything.');
echo '</table>';
	
echo ym_end_box();	
	
echo ym_start_box('Amazon S3 Settings');
	
echo '<p>We need your API keys for Amazon S3 to be able to communicate successfully with Amazon S3. You can get your User and Secret Key here: <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key">Amazon Web Services</a></p>';
	
echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_text_row('User Key', 'yss_user_key', get_option('yss_user_key'), 'Your Amazon User Key');
	$ym_formgen->render_form_table_text_row('Secret Key', 'yss_secret_key', get_option('yss_secret_key'), 'Your Amazon Secret Key');
	
echo '</table>';
	
echo ym_end_box();
	
echo ym_start_box('Amazon Cloudfront Settings');
	
echo '<p>We need your Key Pair details for Amazon Cloudfront to be able to communicate successfully with Amazon Cloudfront, and generate secure URLs.</p>';
echo '<p>You can get your Key Pair here: <a href="https://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key#keypair_block">Amazon Web Services</a> just select Key Pairs</p>';
	
echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_text_row('Key Pair ID', 'yss_cloudfront_id', get_option('yss_cloudfront_id'), 'Your Amazon Key Pair ID');
	$ym_formgen->render_form_table_textarea_row('Key Pair Private Key', 'yss_cloudfront_private', get_option('yss_cloudfront_private'), 'The contents of the private .pem file');
	
echo '</table>';
	
echo ym_end_box();
	
echo ym_start_box('Supported Player Install');
echo '<p>' . __('The Following Players are currently supported and can be used with Your Secure Stream, each is a Wordpress Plugin','yss') . '</p>';
//echo '<p>' . __('We do not directly use the plugin, but its easier to manage the Flash Player files', 'yss') . '</p>';
echo '<p>' . __('YSS does not support non FLV files in a streaming distribution under FlowPlayer. It just does not work!', 'yss') . '</p>';
	
// player install
include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

global $yss_plugins;

$players = array();
echo '<ul>';
foreach ($yss_plugins as $nicename=>$data) {
	$name = $data['name'];
	$slug = $data['slug'];
		
	$action_links = array();
	$action_links[] = '<a href="' . admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug .
						'&amp;TB_iframe=true&amp;width=600&amp;height=550') . '" class="thickbox" title="' .
						esc_attr( sprintf( __( 'More information about %s' ), $name ) ) . '">' . __('Details') . '</a>';
	
	if ( current_user_can('install_plugins') || current_user_can('update_plugins') ) {
		$api = plugins_api('plugin_information', $data);
			
		$status = install_plugin_install_status($api);
		$context = $status;
	
		switch ( $status['status'] ) {
			case 'install':
				if ( $status['url'] )
					$action_links[] = '<a class="install-now" href="' . $status['url'] . '" title="' . esc_attr( sprintf( __( 'Install %s' ), $name ) ) . '">' . __('Install Now') . '</a>';
				break;
			case 'update_available':
				if ( $status['url'] )
					$action_links[] = '<a href="' . $status['url'] . '" title="' . esc_attr( sprintf( __( 'Update to version %s' ), $status['version'] ) ) . '">' . sprintf( __('Update Now'), $status['version'] ) . '</a>';
				break;
			case 'latest_installed':
			case 'newer_installed':
				$action_links[] = '<span title="' . esc_attr__( 'This plugin is already installed and is up to date' ) . ' ">' . __('Installed') . '</span>';
				break;
		}
			
		$all_plugins = apply_filters( 'all_plugins', get_plugins() );	
		
		foreach ( (array)$all_plugins as $plugin_file => $plugin_data) {
			
			if ($plugin_data['Name'] == $name) {
				$is_active_for_network = is_plugin_active_for_network($plugin_file);
				$is_active = $is_active_for_network || is_plugin_active( $plugin_file );
				if ( $is_active_for_network && !is_super_admin() )
					continue;

				if ( $is_active ) {
					$players[$slug] = $name;
					if ( $is_active_for_network ) {
						if ( is_super_admin() )
							$action_links['network_deactivate'] = '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;networkwide=1&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'deactivate-plugin_' . $plugin_file) . '" title="' . __('Deactivate this plugin') . '">' . __('Network Deactivate') . '</a>';
					} else {
						$action_links['deactivate'] = '<a href="' . wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'deactivate-plugin_' . $plugin_file) . '" title="' . __('Deactivate this plugin') . '">' . __('Deactivate') . '</a>';
					}
				} else {
					if ( is_multisite() && is_network_only_plugin( $plugin_file ) )
						$action_links['network_only'] = '<span title="' . __('This plugin can only be activated for all sites in a network') . '">' . __('Network Only') . '</span>';
					else
						$action_links['activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'activate-plugin_' . $plugin_file) . '" title="' . __('Activate this plugin') . '" class="edit">' . __('Activate') . '</a>';

					if ( is_multisite() && current_user_can( 'manage_network_plugins' ) )
						$action_links['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'activate-plugin_' . $plugin_file) . '" title="' . __('Activate this plugin for all sites in this network') . '" class="edit">' . __('Network Activate') . '</a>';
	
					if ( current_user_can('delete_plugins') )
						$action_links['delete'] = '<a href="' . wp_nonce_url('plugins.php?action=delete-selected&amp;checked[]=' . $plugin_file . '&amp;plugin_status=' . $context . '&amp;paged=' . $page, 'bulk-manage-plugins') . '" title="' . __('Delete this plugin') . '" class="delete">' . __('Delete') . '</a>';
				} // end if $is_active
			}
		}
	}
	
	$action_links = apply_filters( 'plugin_install_action_links', $action_links, $plugin );
	
	echo '<li style="margin-left: 15px;">- ' . $name . ' - ';
					
	if ( !empty($action_links) ) {
		echo implode(' | ', str_replace('href', 'target="_parent" href', $action_links));
	}
		
	echo '</li>';
}
echo '</ul>';
	
echo ym_end_box();
	
echo ym_start_box('Custom Player');
	
echo '<p>As apposed to using one of the custom players, you can create your Own player code to use</p>';
echo '<p>Just create the code to use (using the needed placeholder shortcodes) in the area provided and select <em>custom player</em> from the drop down</p>';
	
echo '<p>Shortcodes: 
<ul>
	<li>[yss_video_url] : the video URL</li>
	<li>[yss_streamer] : If streaming is to be used, then replace with the required URL</li>
	<li>[yss_video_height] : height in digits (so you will proabably need px after in your code)</li>
	<li>[yss_video_width] : width in digiits</li>
</ul>
';

echo '<textarea name="yss_custom_player" id="yss_custom_player" style="width: 600px; height: 250px;">' . get_option('yss_custom_player') . '</textarea>';
	
echo ym_end_box();
	
echo ym_start_box('Player Settings');
	
echo '<table class="form-table">';
	
//	$hours = array();
//	for ($x=0;$x<=24;$x++) {
//		$hours[] = $x;
//	}
	
//	$ym_formgen->render_combo_from_array_row('Expire Time (Hours)', 'yss_expire_time', $hours, get_option('yss_expire_time'), 'How long a link remains valid, for example if you allow video download, or a member distributes a video link');
	
//	if (!sizeof($players)) {
//		$players[''] = 'Please install/activate a player';
//	}
	$players['custom'] = 'Custom Player';
	
	$ym_formgen->render_combo_from_array_row('Default Player', 'yss_playerofchoice', $players, get_option('yss_playerofchoice'), 'YSS Player of choice');
echo '</table>';
	
echo ym_end_box();
	
echo '
	<p class="submit" style="text-align: right;">
		<input type="submit" name="settings_update" value="';
	_e('Save Settings','yss');
echo ' &raquo;" />
	</p>
	';
