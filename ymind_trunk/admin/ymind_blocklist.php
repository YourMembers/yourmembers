<?php

echo '<div class="wrap" id="poststuff">
		<h2>' . __('Your Minder Block List','ymind') . '</h2>';

echo ymind_start_box('Your Minder - Block List');

if (isset($_POST['delete'])) {
	ymind_blocks_delete();
}

if (isset($_REQUEST['block_edit'])) {
	ymind_block_edit();
} else if (isset($_POST['block_save'])) {
	ymind_block_save();
} else {
	ymind_block_list();
}

echo ymind_end_box();

function ymind_block_save() {
	global $wpdb;
	
	$id = htmlspecialchars($_POST['id']);
	$ip = htmlspecialchars($_POST['ip']);

	if (!$ip) {
		ymind_display_error(__('Please fill in all the fields', 'ymind'));
		ymind_block_edit();
		return;
	}

	if ($id > 0) {
		$ret = ymind_block_update($id, $ip);
	} else {
		$ret = ymind_block_insert($ip);
	}

	if ($ret === false) {
		ymind_display_error(__('Error while updating data', 'ymind'));
		ymind_block_edit();
	} else {
		ymind_display_feedback(__('Block entry updated.', 'ymind'));
		ymind_block_list();
	}
}

function ymind_block_edit() {
	$id = ym_request('id');
	$row = ymind_get_block($id);

	echo '
	<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="id" value="'.$row->id.'"/>

	<table class="form-table"><tbody>
	
	<tr class="form-field">
		<td>'.__('IP Address', 'ymind').'</td>
		<td>
			<input style="width: 150px;" name="ip" value="'.$row->ip.'"/>
			<span style="color: gray; font-style: italic;">'.__('Please enter an IP address to block.', 'ymind').'</span>
		</td>
	</tr>	

	</tbody>
	</table>

	<div style="margin-top: 5px; float: left;">
		<input type="submit" name="block_save" value="'.__('Save', 'ymind').'" class="button"/>
	</div>
	</form>
	
	<div style="margin-top:5px; float: left;">
		<form method="post">
			<input type="submit" value="&laquo; '.__('Back to block list', 'ymind').'" class="button"/>
		</form>
	</div>
	
	<div style="clear: both;">&nbsp;</div>
	';
}

function ymind_block_list() {

	echo '<table style="width: 100%;" cellspacing="0" cellpadding="2">
		<tr>
			<td style="font-weight: bold; border-bottom: 1px solid silver;">' . __('IP', 'ymind') . '</td>
			<td style="font-weight: bold; border-bottom: 1px solid silver;">' . __('Edit', 'ymind') . '</td>
			<td style="font-weight: bold; border-bottom: 1px solid silver;">' .  __('Delete', 'ymind') . '</td>
		</tr>
	<tbody>';

	//.alternate
	$messages = ymind_get_all_blocks();

	if (count($messages)>0) {
		foreach ($messages as $i=>$m) {
				
			echo '<tr'.($i%2==1?' class="alternate"':'').'>
				<td>'.$m->ip.'</td>
				<td>
					<form method="post">
						<input type="hidden" value="'.$m->id.'" name="id"/>
						<input type="submit" class="button" name="block_edit" value="'.__('Edit','ymind').'" />
					</form>
				</td>
				<td>
				<form method="post">
					<input type="hidden" value="'.$m->id.'" name="delete"/>
					<input type="button" value="Delete" class="button-secondary delete" name="deletebtn" onclick="if (confirm(\'Are you sure you want to delete selected entries?\')) this.form.submit();"/>
				</form>
				</td>
			</tr>';
		}
	} else {
		echo '<tr>
				<td>' . __('There currently no blocked IPs', 'ymind') . '</td>
			</tr>';
	}

	echo '</tbody>
		</table>
		<form method="post">
			<div class="submit" style="text-align: right;">
				<input type="submit" name="block_edit" value="' . __('Add a new block','ymind') . ' &raquo;" />
			</div>
		</form>';

}

function ymind_blocks_delete() {
	$deleted = 0;
	$id = htmlspecialchars($_POST['delete']);
	$d = ymind_block_delete($id);
	
	if ($d) $deleted++;

	if ($deleted) {
		ymind_display_error(__('Deleted blocks: '.$deleted, 'ymind'));
	} else {
		ymind_display_error(__('No blocks deleted.', 'ymind'));
	}
}

echo '</div>';

ymind_render_footer();

?>