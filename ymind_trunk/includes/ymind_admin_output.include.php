<?php

function ymind_render_locked_out_user_table() {
	global $wpdb;

	echo '<table style="width: 100%;" cellspacing="0" cellpadding="2">';

	echo '	<tr>
				<td style="font-weight: bold; border-bottom: 1px solid silver;">'.__('Username', 'ymind').'</td>
				<td style="font-weight: bold; border-bottom: 1px solid silver;">'.__('Email', 'ymind').'</td>
				<td style="font-weight: bold; border-bottom: 1px solid silver;">'.__('Locked Out Since', 'ymind').'</td>
				<td style="font-weight: bold; border-bottom: 1px solid silver;">&nbsp;</td>
			</tr>';

	$sql = 'SELECT u.ID, u.user_login, u.user_email, um.meta_value
			FROM
				' . $wpdb->users . ' u
				JOIN ' . $wpdb->usermeta . ' um ON (
					u.ID = um.user_id
					AND um.meta_key = "ymind_locked_out"
				)
			ORDER BY u.user_login';
	$results = $wpdb->get_results($sql);
	if (count($results) > 0) {
		foreach($results as $result=>$obj) {
			echo '	<tr>
						<td>
							<a href="/wp-admin/user-edit.php?user_id=' . $obj->ID . '">' . $obj->user_login . '</a>
						</td>
						<td>' . $obj->user_email . '</td>
						<td>' . ($obj->meta_value > 1 ? date('jS F Y', $obj->meta_value):''.__('Email Activation', 'ymind').'') . '</td>
						<td>
							<a href="' . $_SERVER['REQUEST_URI'] . '&unlock=' . $obj->ID . '">'.__('Unlock', 'ymind').'</a>
						</td>
					</tr>';
		}
	} else {
		echo '	<tr>
					<td colspan="4" style="font-style: italic;">'.__('There are currently no locked out users.', 'ymind').'</td>
				</tr>';
	}

	echo '</table>';
}

function ymind_render_footer() {
	$style = 'style="color:#FFFFFF; text-decoration:none;"';

	echo '<div style="background:#272b2e; width:100%; height:35px;" id="newmedias">
			<a href="http://www.newmedias.co.uk" id="logo1">
				<div style="float:left;">
					<img src="http://cdn.newmedias.co.uk/images/logo-small.jpg" style="text-decoration:none;" />
				</div>
			</a>
			<div id="newmedias-info" style="float:right; color:#FFFFFF; padding-top:10px; padding-right:5px;">
				<strong>
					<a href="http://www.newmedias.co.uk/your_minder/support" ' . $style . '>Documentation</a> |
					<a href="http://www.newmedias.co.uk/support" ' . $style . '>Support</a> |
					<a href="http://www.newmedias.co.uk/your_minder/license" ' . $style . '>License</a>
				</strong>
			</div>
		</div>';
}

?>