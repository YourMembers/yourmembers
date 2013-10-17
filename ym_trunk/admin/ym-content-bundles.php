<?php

if (ym_request('do_munch') && ym_request('offset')) {
	$max = ym_request('max');

	$offset = ym_request('offset');
	$post_ids = explode(',', ym_request('post_ids'));

	$posts = ym_bundle_get_some_posts($offset, $post_ids, $max);
	$total = count($posts);

	if (!$total) {
		echo '<tr><td>' . __('No Posts', 'ym') . '</td></tr>';
	}

	$more = array();
	foreach ($posts as $post) {
		$line = '<tr>';
		$line .= '<td>(' . $post->ID . ' - ' . $post->post_type . ') ' . addslashes($post->post_title) . '</td>';
		$line .= '<td><input type="checkbox" name="post_ids[]" value="' . $post->ID . '" /></td>';
		$line .= '</tr>';
		$more[] = $line;
	}
	echo json_encode($more);
	exit;
}

echo '<div class="wrap" id="poststuff">';

ym_bundle_update();

echo __('<p>Bundles are groups of purchasable posts which can be sold as one item. Previously known as Pay Per Post Packs</p>','ym');


if ($bundle_id = ym_post('bundle_id')) {
	$bundle = ym_get_bundle($bundle_id);
	
	if (ym_post('edit')) {
		echo ym_start_box(__('Edit Bundle: "', 'ym') . $bundle->name . '"');
		ym_bundle_form($bundle, __('Update Bundle', 'ym'));
		echo '<form action="" method="post"><input class="button-secondary" type="submit" name="submit" value="' . __('Cancel Edit Bundle', 'ym') . '" /></form>';
		echo ym_end_box();
		echo '</div>';
		return;
	} else if (ym_post('posts')) {
		echo ym_start_box(__('Posts within Bundle "', 'ym') . $bundle->name . '"');
		ym_bundle_edit_content($bundle);
		echo ym_end_box();
		echo '</div>';
		echo '<form action="" method="post"><input type="submit" class="button-secondary" value="' . __('Leave Bundle Content Manager', 'ym') . '" /></form>';
		return;
	}
}
	echo ym_start_box(__('Content Bundles','ym'));

	echo '
<table style="width: 100%;">
	<thead>
		<tr>
			<th>' . __('(ID) Name', 'ym') . '</th>
			<th>' . __('Cost', 'ym') . '</th>
			<th>' . __('Description', 'ym') . '</th>

			<th>' . __('Additional', 'ym') . '</th>

			<th>' . __('Purchase Expire', 'ym') . '</th>
			<th>' . __('Purchase Left/Limit', 'ym') . '</th>
			<th>' . __('Bundle End Sale', 'ym') . '</th>
			<th>' . __('Date Created', 'ym') . '</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	';

	if ($bundles = ym_get_bundles()) {
		foreach ($bundles as $i => $bundle) {
			$bundle->additional = unserialize($bundle->additional);
			$string = array();
			if (is_array($bundle->additional)) {
				foreach ($bundle->additional as $key => $val) {
					$string[] = str_replace('_', ' ', $key) . ': ' . $val;
				}
			}
			$string = implode('<br />', $string);

			$posts = count(ym_get_bundle_posts($bundle->id));

			echo '<tr>';
			echo '<td>(' . $bundle->id . ') ' . $bundle->name . '</td>';
			echo '<td>' . number_format($bundle->cost, 2) . '</td>';
			echo '<td>' . $bundle->description . '</td>';

			echo '<td>';
			echo $string;
			echo '</td>';

			echo '<td>' . ($bundle->purchaseexpire ? $bundle->purchaseexpire . ' ' . __('Day(s)', 'ym') : __('None', 'ym')) . '</td>';
			echo '<td>' . ($bundle->purchaselimit ? ym_bundle_purchased_count($bundle->bundle_id) . '/' . $bundle->purchaselimit : __('None', 'ym')) . '</td>';
			echo '<td>' . ($bundle->saleend ? date(YM_DATE, $bundle->saleend) : __('None', 'ym') ). '</td>';
			echo '<td>' . date(YM_DATE, $bundle->unixtime) . '</td>';

			echo '<td>';
			echo '
<form action="" method="post">
	<input type="hidden" name="bundle_id" value="' . $bundle->id . '" />

	<input type="submit" class="button-secondary" name="posts" value="' . $posts . ' ' . ($posts == 1 ? __('Post', 'ym') : __('Posts', 'ym')) . '" />
	<input type="submit" class="button-primary" name="edit" value="' . __('Edit', 'ym') . '" />
	<input type="image" name="delete_bundle" src="' . YM_PLUGIN_DIR_URL . '/images/cross.png" alt="Delete" class="deletelink" value="delete" />
</form>
';
			echo '</td>';

			echo '</tr>';
		}
	} else {
		echo '
		<tr>
			<td colspan="9">' . __('There are currently no bundles in the database.', 'ym') . '</td>
		</tr>';
	}
	echo '
	</tbody>
</table>
';

	echo ym_end_box();

	/**
	Creating
	*/
	echo ym_start_box(__('Create New Bundle', 'ym'));
	$bundle = ym_get_bundle('');
	ym_bundle_form($bundle, __('Create Bundle', 'ym'));
	echo ym_end_box();

	/**
	Gifting
	*/
	echo ym_start_box(__('Gift a Bundle', 'ym'));

	global $wpdb, $ym_formgen;

	echo '<form action="" method="post">';
	echo '<table style="width: 100%;"><tr><td>' . __('Select a User', 'ym') . '</td><td>' . __('Select a Bundle', 'ym') . '</td></tr><tr><td>';

	$user_sql = 'SELECT DISTINCT(ID) AS value, user_login AS label
			FROM ' . $wpdb->users . ' u
			ORDER BY user_login';
	$ym_formgen->render_combo_from_query('user_to_gift', $user_sql);

	echo '</td><td>';
	echo '<select name="bundle_to_gift">';
	$bundles = ym_get_bundles();
	foreach ($bundles as $bundle) {
		echo '<option value="' . $bundle->id . '">' . $bundle->name . '</option>';
	}
	echo '</select>';
	echo '</td><td>';
	echo '<input type="submit" class="button-secondary" value="' . __('Gift Bundle', 'ym') . '" />';
	echo '</td></tr></table>';
	echo '</form>';

	echo ym_end_box();

	echo ym_start_box(__('Bundle Purchases Made', 'ym'));

	echo '<table style="width: 100%;" class="form-table widefat">
	<tr>
		<th>' . __('Member', 'ym') . '</th>
		<th>' . __('(ID) Name', 'ym') . '</th>
		<th>' . __('Purchase Expiry', 'ym') . '</th>
		<th>' . __('Date Purchased', 'ym') . '</th>
		<th>' . __('Payment Method', 'ym') . '</th>
		<th>' . __('Delete', 'ym') . '</th>
	</tr>';
	foreach (ym_get_bundle_purchases() AS $purchase) {
		echo '<tr>';
		echo '<td>(' . $purchase->user_id . ') ' . $purchase->display_name . '</td>';
		echo '<td>(' . $purchase->pack_id . ') ' . $purchase->name . '</td>';

		$expires = ($purchase->purchaseexpire * 86400) + $purchase->purchasetime;

		echo '<td>' . ($purchase->purchaseexpire ? date(YM_DATE, $expires) : __('No Expire', 'ym')) . '</td>';
		echo '<td>' . date(YM_DATE, $purchase->purchasetime) . '</td>';
		echo '<td>' . $purchase->payment_method . '</td>';
		echo '<td><form action="" method="post">
<input type="hidden" name="delete_bundle_purchase" value="' . $purchase->purchase_id . '" />
<input type="image" src="' . YM_PLUGIN_DIR_URL . '/images/cross.png" alt="Delete" class="deletelink" />
</form></td>';
		echo '</tr>';
	}
	echo '</table>';

	echo ym_end_box();


	echo ym_start_box(__('Bundles Purchased Count', 'ym'));

	echo '<table style="width: 100%;" class="form-table widefat">
	<tr>
		<th>' . __('(ID) Name', 'ym') . '</th>
		<th>' . __('Cost', 'ym') . '</th>
		<th>' . __('Count', 'ym') . '</th>
	</tr>';
	foreach ($bundles as $bundle) {
		$count = ym_bundle_purchased_count($bundle->id);
		echo '<tr>';
		echo '<td>(' . $bundle->id . ') ' . $bundle->name . '</td>';
		echo '<td>' . number_format($bundle->cost, 2) . ' ' . ym_get_currency() . '</td>';
		echo '<td>' . $count . '</td>';
		echo '</tr>';
	}
	echo '</table>';

	echo ym_end_box();

echo '</div>';
