<?php

/*
* $Id: ym-admin_functions_posting.include.php 2608 2013-02-22 10:47:57Z bcarlyon $
* $Revision: 2608 $
* $Date: 2013-02-22 10:47:57 +0000 (Fri, 22 Feb 2013) $
*/

/**
// new meta box
*/
function ym_meta_box_setup() {
	$name = sprintf(__('Your Members (V%s)', 'ym'), YM_PLUGIN_VERSION);

	// custom post types??
	$types = get_post_types();
	$ignore = array('mediapage', 'attachment', 'revision', 'nav_menu_item');
	foreach ($types as $type) {
		add_meta_box('ym_private_setup', $name, 'ym_private_setup_meta_box', $type, 'side', 'high');
		add_meta_box('ym_post_delay', __('Your Members: Drip Feeding', 'ym'), 'ym_post_delay_meta_box', $type, 'side', 'high');
		add_meta_box('ym_pay_per_post', __('Your Members: Content Purchase', 'ym'), 'ym_ppp_meta_box', $type, 'normal', 'high');
		add_meta_box('ym_bundle', __('Your Members: Content Bundles', 'ym'), 'ym_bundle_meta_box', $type, 'side', 'high');
	}
}

function ym_private_setup_meta_box($post) {
	global $ym_package_types, $ym_sys;

	echo '<p>' . __('Don&#39;t forget the <strong>[private]</strong> private tags <strong>[/private]</strong>', 'ym') . '</p>';
	echo '<p>' . '<img src="' . YM_IMAGES_DIR_URL . '/tinymce/private.png" style="background: #FFF;" />' . __('You can use the TinyMCE Button', 'ym') . '</p>';

	if (!$post->ID || $post->post_status == 'auto-draft') {
		$selected = $ym_sys->default_account_types;
	} else {
		$selected = get_post_meta($post->ID, '_ym_account_type', true);
	}
	$selected = explode(';', $selected);

	$post_type = (substr($_SERVER['SCRIPT_FILENAME'], strrpos($_SERVER['SCRIPT_FILENAME'], '/')) == '/page-new.php'? 'Page':'Post');	
	echo '<p>' . sprintf(__('Which package types(s) will have access to this %s?', 'ym'), $post_type) . '</p>';
	
	foreach ($ym_package_types->types as $type) {
		$checked_string = '';
		if (in_array($type, $selected)) {
			$checked_string = 'checked="checked"';
		}

		echo '
		<div class="ym_setting_list_item">
			<label>
				<input type="checkbox" class="checkbox" name="post_account_types[]" value="' . $type . '" ' . $checked_string . ' /> ' . __($type) . '
			</label>
		</div>';
	}
}

function ym_post_delay_meta_box($post) {
	global $ym_package_types;
	echo '
	<p' . __('How long should the user have been a member to see this content?', 'ym') . '</p>
	<table style="width:100%;">';

	$min_dur = array();
	$data = get_post_meta($post->ID, '_ym_account_min_duration', true);
	if ($data) {
		$data = explode(';', $data);
		foreach ($data as $item) {
			$item = explode('=', $item);
			$min_dur[$item[0]] = $item[1];
		}
	}

	foreach ($ym_package_types->types as $type) {
		$type_safe = strtolower(str_replace(' ','_',$type));
		$val = isset($min_dur[$type_safe]) ? $min_dur[$type_safe] : '';
		echo '
		<tr>
			<td style="font-size:11px;">' . $type . '</td>
			<td style="font-size:11px;">
				<input class="ym_text" name="available_from[' . $type_safe . ']" value="' . $val . '" style="width: 25px;"/> ' . __('Day(s)', 'ym') . '
			</td>
		<tr>
		';
	}
			
	echo '</table>';
}

function ym_ppp_meta_box($post) {
	global $ym_sys;

	if (isset($post->ID) && $post->ID > 0 && $post->post_status != 'auto-draft') {
		$post_purchasable = get_post_meta($post->ID, '_ym_post_purchasable', true);
		$post_purchasable_cost = get_post_meta($post->ID, '_ym_post_purchasable_cost', true);
		$post_purchasable_expiry = get_post_meta($post->ID, '_ym_post_purchasable_expiry', true);
		$post_purchasable_limit = get_post_meta($post->ID, '_ym_post_purchasable_limit', true);
		$post_purchasable_duration = get_post_meta($post->ID, '_ym_post_purchasable_duration', true);
		$post_purchasable_featured = get_post_meta($post->ID, '_ym_post_purchasable_featured', true);
		$ppp_index = get_post_meta($post->ID, '_ym_post_purchasable_index', true);
	} else {
		$post_purchasable = $ym_sys->default_ppp;
		$post_purchasable_cost = $ym_sys->default_ppp_cost;
		$post_purchasable_expiry = '';
		$post_purchasable_limit = '';
		$post_purchasable_duration = '';
		$post_purchasable_featured = 0;
		$ppp_index = '';
	}

	$post_purchasable_no = $post_purchasable ? '' : 'checked="checked"';
	$post_purchasable_yes = $post_purchasable ? 'checked="checked"' : '';
	$post_rest_show = $post_purchasable_yes ? 'show' : 'none';

	$post_purchasable_featured_no = $post_purchasable_featured ? '' : 'checked="checked"';
	$post_purchasable_featured_yes = $post_purchasable_featured ? 'checked="checked"' : '';

	$post_type = (substr($_SERVER['SCRIPT_FILENAME'], strrpos($_SERVER['SCRIPT_FILENAME'], '/')) == '/page-new.php'? 'Page' : 'Post');

	echo '
<table style="width: 100%;">
	<tr>
		<td><label for="post_purchasable" style="width: 50%">' . sprintf(__('Is this %s available to buy?', 'ym'), $post_type) . '</label></td>
		<td>
<label for="post_purchasable_no" onclick="jQuery(\'.post_purchasable_rest\').hide();"><input type="radio" name="post_purchasable" id="post_purchasable_no" value="0" ' . $post_purchasable_no . ' /> ' . __('No', 'ym') . '</label>
<label for="post_purchasable_yes" onclick="jQuery(\'.post_purchasable_rest\').show();"><input type="radio" name="post_purchasable" id="post_purchasable_yes" value="1" ' . $post_purchasable_yes . ' />' . __('Yes', 'ym') . '</label>
		</td>
	</tr>

';

	$class = ' class="post_purchasable_rest" style="display: ' . $post_rest_show . ';"';
	echo '
	<tr ' . $class . '>
		<td>' . sprintf(__('%s Cost (Leave blank for Bundle Only):', 'ym'), $post_type) . '</td>
		<td><input type="text" class="ym_text" name="post_purchasable_cost" value="' . $post_purchasable_cost . '" size="2" /> ' . ym_get_currency() . '</td>
	</tr>
	<tr ' . $class . '>
		<td>' . sprintf(__('Purchase Expiry (date %s stops being Purchasable)', 'ym'), $post_type) . '</td>
		<td><input type="text" class="ym_text ym_datepicker" name="post_purchasable_expiry" id="post_purchasable_expiry" value="' . $post_purchasable_expiry . '" size="12" /> 
				<a href="#nowhere" onclick="ym_clear_target(\'post_purchasable_expiry\');">' . __('Clear Date', 'ym') . '</a></td>
	</tr>
	<tr ' . $class . '>
		<td>' . sprintf(__('Number of %s Available, blank for unlimited', 'ym'), $post_type) . '</td>
		<td><input type="text" class="ym_text" name="post_purchasable_limit" value="' . $post_purchasable_limit . '" size="4" /></td>
	</tr>
	<tr ' . $class . '>
		<td>' . sprintf(__('Buyer can see %s for, blank for forever', 'ym'), $post_type) . '</td>
		<td><input type="text" class="ym_text" name="post_purchasable_duration" value="' . $post_purchasable_duration . '" size="2" /> ' . __('Day(s)', 'ym') . '</td>
	</tr>

	<tr ' . $class . '>
		<td>' . sprintf(__('Make a Featured Purchasable %s', 'ym'), $post_type) . '</td>
		<td>
<label for="post_purchasable_featured_no"><input type="radio" name="post_purchasable_featured" id="post_purchasable_featured_no" value="0" ' . $post_purchasable_featured_no . ' /> ' . __('No', 'ym') . '</label>
<label for="post_purchasable_featured_yes"><input type="radio" name="post_purchasable_featured" id="post_purchasable_featured_yes" value="1" ' . $post_purchasable_featured_yes . ' />' . __('Yes', 'ym') . '</label>
		</td>
	</tr>
	<tr ' . $class . '>
		<td>' . sprintf(__('PPP Index, for Grouping Posts, blank for no grouping')) . '</td>
		<td><input type="text" class="ym_text" name="ppp_index" value="' . $ppp_index . '" size="2" /></td>
	</tr>
	';

	$gateways = ym_spawn_gateways();
	foreach ($gateways as $gateway) {
		if (method_exists($gateway, 'additional_pack_fields')) {
			$fields = $gateway->additional_pack_fields();
			foreach ($fields as $field) {
				$val = get_post_meta($post->ID, '_ym_post_purchasable_' . $field['name'], true);

				echo '
	<tr ' . $class . '>
		<td>' . $field['label'];

				if ($field['caption']) {
					echo ' - ' . $field['caption'];
				}

				echo '</td>
		<td>' . '<input type="text" class="ym_text" name="' . $field['name'] . '" value="' . $val . '" />
	</tr>';
			}
		}
	}
	echo '
</table>
';
}
function ym_bundle_meta_box($post) {
	global $wpdb;

	echo '<p>' . __('You can add/remove this Post to a Content Bundle, it needs to be set as Purchasable under the Content Purchase Widget', 'ym') . '</p>';

	$in_bundle = array();
	$query = 'SELECT pack_id FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc
		WHERE post_id = ' . $post->ID;
	foreach ($wpdb->get_results($query) as $row) {
		$in_bundle[] = $row->pack_id;
	}
	$in_bundle = array_unique($in_bundle);

	$bundles = ym_get_bundles();

	foreach ($bundles as $bundle) {
		echo $bundle->name . ' ';

		$rows = ym_get_bundle_posts($bundle->id);
		$count = count($rows);
		echo '(' . $count . ' ' . __('Posts in pack', 'ym') . ') ';

		echo '<input type="checkbox" name="ym_bundle[]" value="' . $bundle->id . '" ';

		if (in_array($bundle->id, $in_bundle)) {
			echo 'checked="checked"';
		}

		echo ' />';
		echo '<br />';
	}
}
/**
End Meta Box
*/

/**
// Updates the account type for posts and pages
*/
function ym_account_save($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
		return;
	}
	// nonce needed
	/**
// Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'myplugin_noncename' );
  // in widgets 
	//if ( !wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename( __FILE__ ) ) )
*/

	$ignores = array(
		'auto-draft',
		'inherit',
		'trash',
	);
	// allow save on types from http://codex.wordpress.org/Function_Reference/get_post_status
	if (in_array(get_post_status($post_id), $ignores)) {
		return;
	}
	//we add the account types within this if because it should always be set.
	//the only occurrence where it wouldnt be set is in a scheduled post.
	$opt = '';
	if (ym_post('post_account_types')) {
		foreach ((array)ym_post('post_account_types') as $type) {
			$opt .= $type.';';
		}
		$opt = rtrim($opt, ';');
	}
	update_post_meta($post_id, '_ym_account_type', $opt);

	update_post_meta($post_id, '_ym_post_purchasable', ym_post('post_purchasable'));
	if (ym_post('post_purchasable')) {
		$purchase_expiry = ym_post('post_purchasable_expiry');
		$purchase_limit = ym_post('post_purchasable_limit');

		if ($purchase_expiry != '') {
			$purchase_expiry = strtotime($purchase_expiry);
		}

		$purchase_duration = (int)ym_post('post_purchasable_duration');

		update_post_meta($post_id, '_ym_post_purchasable_featured', ym_post('post_purchasable_featured'));
		update_post_meta($post_id, '_ym_post_purchasable_index', ym_post('ppp_index'));
		update_post_meta($post_id, '_ym_post_purchasable_cost', ym_post('post_purchasable_cost'));
		update_post_meta($post_id, '_ym_post_purchasable_expiry', $purchase_expiry);
		update_post_meta($post_id, '_ym_post_purchasable_limit', $purchase_limit);
		update_post_meta($post_id, '_ym_post_purchasable_duration', $purchase_duration);

		$gateways = ym_spawn_gateways();
		foreach ($gateways as $gateway) {
			if (method_exists($gateway, 'additional_pack_fields')) {
				$fields = $gateway->additional_pack_fields();
				foreach ($fields as $field) {
					update_post_meta($post_id, '_ym_post_purchasable_' . $field['name'], ym_post($field['name']));
				}
			}
		}

		// bundle
		global $wpdb;
		$data = ym_post('ym_bundle', array());
		$bundles = ym_get_bundles();
		foreach ($bundles as $bundle) {
			$id = $bundle->id;
			if (in_array($id, $data)) {
				// add
				ym_add_post_to_bundle($post_id, $id);
			} else {
				// remove
				ym_remove_post_from_bundle($post_id, $id);
			}
		}
	}

	if (ym_post('available_from')) {
		$string = array();

		foreach (ym_post('available_from') as $key=>$type) {
			$string[] = $key . '=' . $type;
		}

		$string = implode(';', $string);

		update_post_meta($post_id, '_ym_account_min_duration', $string);
	}
}
/**
// End Meta Box
*/

/**
// Start Tiny MCE
*/
function ym_tinymce_addbuttons_plugin($buttons) {
	$buttons['ym_private'] = YM_JS_DIR_URL . 'ym_tinymce.js';
	$buttons['ym_no_access'] = YM_JS_DIR_URL . 'ym_tinymce.js';
	$buttons['ym_user_has_access'] = YM_JS_DIR_URL . 'ym_tinymce.js';
	return $buttons;
}
function ym_tinymce_addbuttons_register($buttons) {
   array_push($buttons, 'separator', 'ym_private', 'ym_no_access', 'ym_user_has_access');
   return $buttons;
}
function ym_tinymce_addbuttons() {
	if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		return;

	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'ym_tinymce_addbuttons_plugin');
		add_filter('mce_buttons', 'ym_tinymce_addbuttons_register');
	}
}
/**
// End Tiny MCE
*/
