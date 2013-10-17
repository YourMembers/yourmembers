<?php

// affiliate
//$affiliate_action = $_POST('')

global $ym_sys, $ym_formgen, $ym_auth;

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

echo '<div class="wrap" id="poststuff">';

echo ym_box_top(__('Promote Your Members', 'ym'), TRUE);

if (!$ym_sys->advertise_ym_text) {
	$anchors = array(
		__('Powered By Your Members', 'ym'),
		__('WordPress Membership by Your Members', 'ym'),
		__('Your Membership Site in a Box', 'ym')
	);
	shuffle($anchors);
	$anchor = array_pop($anchors);
} else {
	$anchor = $ym_sys->advertise_ym_text;
}

// hack
if (!isset($ym_sys->advertise_ym)) {
	$ym_sys->advertise_ym = 0;
}

echo '<form method="post">
<table class="form-table">';
$ym_formgen->render_form_table_radio_row(__('Advertise Your Members in Your Themes footer', 'ym'), 'advertise_ym', $ym_sys->advertise_ym);
$ym_formgen->render_form_table_text_row(__('Your Members Affiliate ID', 'ym'), 'advertise_ym_affid', $ym_sys->advertise_ym_affid);
echo '<tr><th>' . __('Preview', 'ym') . '</th><td><a href="http://YourMembers.co.uk/">' . $anchor . '</a>
<input type="hidden" name="advertise_ym_text" value="' . $anchor . '" /></td></tr>';
echo '</table>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" />
</p>
</form>
';

echo ym_end_box();

if ($ym_sys->advertise_ym_affid) {
	list($nothing, $affid) = explode(':', $ym_sys->advertise_ym_affid);
	echo '<iframe src="' . YM_PLUGIN_SITE . '/ym/affiliate?ym_affiliate=status&aff_id=' . $affid . '&key=' . $ym_auth->ym_license_key . '" style="width: 100%; height: 500px;"></iframe>';
} else {
	echo '<iframe src="' . YM_PLUGIN_SITE . '/ym/affiliate?ym_affiliate=load_form&key=' . $ym_auth->ym_license_key . '" style="width: 100%; height: 500px;"></iframe>';
}

echo '</div>';
