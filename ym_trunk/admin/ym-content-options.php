<?php

global $ym_formgen, $ym_res, $ym_sys, $duration_str;

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();
	

	if (@empty($_POST['default_account_types'])) {
		$ym_sys->default_account_types = '';
	}

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

echo '<div class="wrap" id="poststuff">
<form action="" method="post">
';

echo ym_start_box(__('Default Content Package Types', 'ym'));

global $ym_package_types;

echo '<p>' . __('Select which package types, new content will inherit by default', 'ym') . '</p>';

$selected = array();
if ($ym_sys->default_account_types) {
	$selected = explode(';', $ym_sys->default_account_types);
}

foreach ($ym_package_types->types as $type) {
	$checked = '';
	if (in_array($type, $selected)) {
		$checked = 'checked="checked"';
	}

	echo '	<div class="ym_setting_list_item">
			<label>
				<input type="checkbox" class="checkbox" name="default_account_types[]" value="' . $type . '" ' . $checked . ' /> ' . __($type) . '
			</label>
		</div>';
}

echo ym_end_box();
echo ym_start_box(__('Additional Options', 'ym'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Enable individual purchase by default?', 'ym'), 'default_ppp', $ym_sys->default_ppp);

echo $ym_formgen->render_form_table_text_row(__('Individual purchase cost default?', 'ym'), 'default_ppp_cost', $ym_sys->default_ppp_cost);

echo $ym_formgen->render_combo_from_array_row(__('Enable Drip Feeding by', 'ym'), 'post_delay_start', array('user_reg'=>'User Registration Date', 'pack_join'=>'Package Join Date'), $ym_sys->post_delay_start);

echo '</table>';

echo ym_end_box();
echo ym_start_box(__('Metered Access','ym'));
echo __('<strong>Warning Metered Access can be used to circumvent protection to gain free access if enabled</strong>','ym');
echo '<table class="form-table">';
$checked = '';
if($ym_sys->enable_metered){
	$checked = 'checked="checked"';
}

echo '	<div class="ym_setting_list_item">
			<label>
				<input type="hidden" name="enable_metered" value="0" />
				<input type="checkbox" class="checkbox" name="enable_metered" value="1" ' . $checked . ' /> ' . __('Enable Metered Access','ym') . '
			</label>
		</div>';
echo $ym_formgen->render_form_table_text_row(__('Number of pages viewable?', 'ym'), 'metered_posts', $ym_sys->metered_posts);
echo '<table><td>'. __('Duration:','ym') .'
				<input class="ym_input" style="width: 50px; font-family:\'Lucida Grande\',Verdana; font-size: 11px; text-align: right;" name="metered_duration" value="' . $ym_sys->metered_duration . '">
	</td>';
echo '<td>
				<select name="trial_duration_type">
				';
				
		foreach ($duration_str as $str => $val) {
			echo '<option value="' . $str . '"';
			if (isset($ym_sys->metered_duration_type)) {
				if ($str == $ym_sys->metered_duration_type) {
					echo ' selected="selected"';
				}
			}
			echo '>' . $val . '</option>';
		}
		echo '
				</select>
			</td></table>';
echo '<p>' . __('Select which package types, you want Guest to be able to access', 'ym') . '</p>';

$selected = array();
if ($ym_sys->metered_account_types) {
	$selected = explode(';', $ym_sys->metered_account_types);
}

foreach ($ym_package_types->types as $type) {
	$checked = '';
	if (in_array($type, $selected)) {
		$checked = 'checked="checked"';
	}

	echo '	<div class="ym_setting_list_item">
			<label>
				<input type="checkbox" class="checkbox" name="metered_account_types[]" value="' . $type . '" ' . $checked . ' /> ' . __($type) . '
			</label>
		</div>';
}
$checked = '';
if($ym_sys->enable_dnt_metered){
	$checked = 'checked="checked"';
}

echo '	<br><div class="ym_setting_list_item">
			<label>
				<input type="hidden" name="enable__dnt_metered" value="0" />
				<input type="checkbox" class="checkbox" name="enable_dnt_metered" value="1" ' . $checked . ' /> ' . __('Obey DNT headers (note some users will not see content)','ym') . '
			</label>
		</div>';
echo '	<br><div class="ym_setting_list_item">
			<label>
				<input type="hidden" name="enable__fcf_metered" value="0" />
				<input type="checkbox" class="checkbox" name="enable_fcf_metered" value="1" ' . $checked . ' /> ' . __('Enable First Click Free, <strong>Warning this will allow Google to index your private content</strong>','ym') . '
			</label>
		</div>';
echo '</table>';
echo ym_end_box();
?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" />
</p>

</form>
</div>
