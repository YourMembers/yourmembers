<?php

/*
* $Id: ym-payment-gateways.php 2429 2012-11-27 13:31:36Z bcarlyon $
* $Revision: 2429 $
* $Date: 2012-11-27 13:31:36 +0000 (Tue, 27 Nov 2012) $
*/

$ym_gateway_paths = apply_filters('ym_alternative_gateway_paths', array());
$ym_gateway_paths[] = YM_MODULES_DIR;

if (ym_get('sel')) {
	$file = ym_get('sel');
	$mode = ym_get('mode');

	$class = rtrim($file, '.php');

	$full_path = '';
	foreach ($ym_gateway_paths as $ref_name => $path) {
		if (is_file($path . $file)) {
			// found
			$full_path = $path . $file;
			break;
		}
	}

	if ($full_path) {
		$extra = '';
		require_once($full_path);
		$obj = new $class();

		if ($mode == 'activate') {
			// activate the selected plugin
			$obj->activate();

			$modules = get_option('ym_modules');

			if (!in_array($class, (array)$modules)) {
				$modules[] = $class;
			}
			// force-redirect to this module's options page
			$extra = '&sel=' . $file . '&mode=options';
		} elseif ($mode == 'deactivate') {
			// deactivate the selected module
			$obj->deactivate();

			$modules = get_option('ym_modules');

			if (in_array($class, (array)$modules)) {
				$key = array_search($class, $modules);
				
				delete_option($modules[$key]); //clears all previous data for this module
				
				$modules[$key] = null;
				unset($modules[$key]);
			}
		} elseif ($mode == 'options') {
			$obj->options();
			echo '<p><a href="' . YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules" class="button-secondary">' . __('Return to Payment Gateways Page') . '</a></p>';
			return;
		}

		update_option('ym_modules', $modules);

		// redirect to the modules page
		$url = YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules' . $extra;
		echo '
		<script type="text/javascript">
		window.location = "' . $url . '"
		</script>
		';
		exit;
	} else {
		ym_display_message(__('Payment Gateway Module not Found', 'ym'), 'error');
	}
} else {
	$available_modules = array();

	foreach ($ym_gateway_paths as $path) {
		$dir = dir($path);
		while (($entry = $dir->read()) !== false) {
			if (is_file($path .'/'. $entry) && $entry != 'ym_model.php' && '.php' == substr($entry, -4, 4)) {
				$available_modules[$entry] = $path;
			}
		}
	}

	global $ym_active_modules;

	echo '
<div class="wrap" id="poststuff">
	<form name="frm" action="" method="post">';

	echo ym_start_box(__('Available Payment Gateways', 'ym'));

	echo '<table style="width: 100%;" cellspacing="0" class="ym_table">
		<tr>
			<th>' . __('Name','ym') . '</td>
			<th width="40%">' . __('Description','ym') . '</td>
			<th>' . __('Status','ym') . '</td>
			<th>' . __('Action','ym') . '</td>
		</tr>';

	
	foreach ($available_modules as $entry => $modules_dir) {		
		$class = rtrim($entry, '.php');
			
		require_once($modules_dir .'/'. $entry);
		$obj = new $class();

		$status = (in_array($class, (array)$ym_active_modules)) ? 'Active' : 'Inactive';

		$links = '';
		if ($status == 'Active') {
			if (method_exists($obj, 'load_options')) {
				$links .= '<a href="' . YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules&mode=options&sel=' . $entry . '">' . __('Settings</a>', 'ym') . '</a>';
			}
			$links .= '<br />';
			if ($entry != 'ym_free.php') {
				$links .= '<a href="' . YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules&mode=deactivate&sel=' . $entry . '">' . __('Deactivate', 'ym') . '</a>';
			}
		} else {
			$activate_url = YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules&mode=activate&sel=' . $entry;
			$links = sprintf(__('<a href="%s">Activate</a>','ym'), $activate_url);
		}
		$desc = $obj->description;

		echo '
		<tr valign="top">
			<td valign="top" width="10%"><span style="font-size: 14px;">'. $obj->name .'</span></td>
			<td valign="top">'. strip_tags($desc) .'</td>
			<td valign="top" width="7%" style="color:' . ($status == 'Active' ? '#00AA00':'#FF0000') . ';">'. $status;
			if (isset($obj->version)) {
				echo '<br />R: ' . preg_replace('/[^0-9]/', '', $obj->version);
			}
			echo '</td>
			<td width="8%" valign="top">'. $links .'</td>
		</tr>
		';
		$obj = null;
	}
}

?>
</form>
</table>

<?php

echo ym_end_box();
echo ym_start_box(__('Global Payment Settings', 'ym'));

global $ym_sys, $ym_res, $ym_formgen;

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	// currency is in here
	$ym_res->update_from_post();
	// sys
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

$sel_currency = ym_get_currency();
$currencies = ym_get_currencies();

echo '
<form action="" method="post">
<table class="form-table">
';

$ym_formgen->render_form_table_text_row(__('The Name that Appears on Customer Reciepts', 'ym'), 'item_name', $ym_sys->item_name, __('The name of the membership to display on the order form', 'ym'));
$ym_formgen->render_combo_from_array_row(__('Payment Currency', 'ym'), 'currency', $currencies, $sel_currency, __('The Currency used for all payments', 'ym'));
$ym_formgen->render_form_table_radio_row(__('Apply sales Tax?', 'ym'), 'global_vat_applicable', $ym_sys->global_vat_applicable);
$ym_formgen->render_form_table_text_row(__('Sales Tax', 'ym'), 'vat_rate', (int)$ym_sys->vat_rate);

$ym_formgen->render_form_table_radio_row(__('Enable Members to Extend Subscription prior to expiry date?', 'ym'), 'allow_upgrade_to_same', (int)$ym_sys->allow_upgrade_to_same, __('Valid only for non-reoccuring package subscriptions', 'ym'));

$ym_formgen->render_form_table_radio_row(__('Enable end of Subscription Grace Period', 'ym'), 'grace_enable', (int)$ym_sys->grace_enable, __('Tip: Useful for pending payments, does not trigger on cancelled/failed payments', 'ym'));
$ym_formgen->render_form_table_text_row(__('Grace Limit (Days)', 'ym'), 'grace_limit', (int)$ym_sys->grace_limit);

echo '<tr>';
echo '<td><label for="tod">' . __('What time of Day should a User Expire?', 'ym') . '</label></td>';
echo '<td>';

$hours = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23);
$mins = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59);

$ym_formgen->render_combo_from_array('expire_time_hour', $hours, $ym_sys->expire_time_hour);
echo ' : ';
$ym_formgen->render_combo_from_array('expire_time_min', $mins, $ym_sys->expire_time_min);
echo ' : ';
$ym_formgen->render_combo_from_array('expire_time_sec', $mins, $ym_sys->expire_time_sec);

echo '</td>';
echo '</tr>';

echo '
</table>
<p class="submit" style="text-align: right;">
	<input type="submit" class="button-primary" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" />
</p>
</form>
';

echo ym_end_box();

?>

</div>
