<?php

/*
* $Id: ym-content-mass_content_setup.php 2186 2012-05-29 15:05:35Z bcarlyon $
* $Revision: 2186 $
* $Date: 2012-05-29 16:05:35 +0100 (Tue, 29 May 2012) $
*/

$ym_custom_post_types_ignores = array(
	'attachment',
	'revision',
	'nav_menu_item'
);

if (isset($_GET['do_munch'])) {
	$start = ym_get('start', 0);
	$limit = ym_get('limit', 10);
	$string = unserialize(ym_get('string'));

	$hide_posts = $ym_sys->ym_hide_posts;
	$update_count = $private_count = 0;

	$text = FALSE;
	foreach ($string as $cat => $accounts) {
		$cposts = get_posts(array('category' => $cat, 'numberposts' => $limit, 'offset' => $start));
			
		foreach ($cposts as $post) {
			$post_id = $post->ID;
			echo sprintf(__('Munched Post ID %s From Category ID %s changed to "%s"', 'ym'), $post_id, $cat, ($accounts ? $accounts : __('None', 'ym'))) . '<br />';
			ym_mass_post_do_post($post_id, $accounts, $hide_posts, $update_count, $private_count);
			$text = TRUE;
		}
	}
	if ($text) {
		echo '<br />' . sprintf(__('This Run Updated %s posts and added Private tags to %s posts', 'ym'), $update_count, $private_count) . '<br /><br />';
	}
	exit;
}

global $wpdb, $ym_sys;

function ym_mass_post_do_post($post_id, $account_type_string, $hide_posts, &$update_count, &$private_count) {
	global $wpdb;
	
	if (update_post_meta($post_id, '_ym_account_type', $account_type_string)) {
		$update_count++;
	}
	
	if (!$hide_posts) {
		$sql = 'SELECT COUNT(ID)
					FROM ' . $wpdb->posts . '
					WHERE 
						post_content LIKE "%[private]%"
						AND ID = ' . $post_id;
		if (!$wpdb->get_var($sql)) {
			$private_count++;

			$sql = 'UPDATE ' . $wpdb->posts . '
						SET post_content = CONCAT("[private]", post_content, "[/private]")
						WHERE ID = ' . $post_id;
			$wpdb->query($sql);
		}
	}
}

if (ym_post('migrate')) {
	$limit = 10;

	$update_count = $private_count = 0;
	$hide_posts = $ym_sys->ym_hide_posts;
	
	if ($categorys = ym_post('categorys')) {
		$strings = array();
		foreach ($categorys as $category) {
			$string = ym_post('account_types_c_' . $category);
			$string = $string ? $string : array();
			$account_type_string = implode(';', $string);
			$strings[$category] = $account_type_string;
		}
		$string = serialize($strings);

		echo '<div class="wrap" id="poststuff">';
		ym_box_top(__('Processing', 'ym'));
		echo '<div id="ym_munch_status" style="font-weight: 700;">' . __('Waiting', 'ym') . '</div><br /><div id="ym_munch_return"></div><br /><a href="' . site_url('wp-admin/admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-content-mass_content_setup') . '" id="ym_return_link" style="display: none;">' . __('Return to Mass Post Setup', 'ym') . '</a>';;
		ym_box_bottom();
		echo '</div>';

		echo '
<script type="text/javascript">
	var start = 0;
	var limit = ' . $limit . ';
	var load = \'' . site_url('wp-admin/admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-content-mass_content_setup') . '&do_munch=1&string=' . $string . '&limit=\' + limit + \'&start=\';

	jQuery(\'document\').ready(function() {
		setTimeout(\'runit()\', 1500);
	});
	function runit() {
		jQuery(\'#ym_munch_status\').html(\'' . __('Loading', 'ym') . '\');
		url = load + start;
		start = start + limit;

		jQuery.get(url, function(data) {
			if (!data) {
				jQuery(\'#ym_munch_status\').html(\'' . __('Complete', 'ym') . '\');
				jQuery(\'#ym_return_link\').show();
				return;
			}
			jQuery(\'#ym_munch_status\').html(\'' . __('Sleeping', 'ym') . '\');
			jQuery(\'#ym_munch_return\').html(jQuery(\'#ym_munch_return\').html() + data);
			setTimeout(\'runit()\', 5000);
		});
	}
</script>
';
		return;
	}
	
	if ($posts = ym_post('posts')) {
		foreach ($posts as $post_id) {
			$string = ym_post('account_types_' . $post_id);
			$string = $string ? $string : array();
			$account_type_string = implode(';', $string);
			
			ym_mass_post_do_post($post_id, $account_type_string, $hide_posts, $update_count, $private_count);
		}
	}
	
	if ($update_count || $private_count) {
		ym_display_message(__('Successfully setup YM data for ' . $update_count . ' Posts/Pages' . (!$hide_posts ? ' of which ' . $private_count . ' have had private tags added':''),'ym'));
	} else {
		ym_display_message(__('Please select at least one post or page to setup/nothing was changed/updated','ym'), 'error');
	}
}

global $ym_package_types;

echo '<div class="wrap" id="poststuff">';
echo '<form method="post">';

echo '
<div id="masspostsetuptabs">
<ul>
';

$types = get_post_types();
foreach ($types as $type) {
	if (!in_array($type, $ym_custom_post_types_ignores)) {
		echo '<li><a href="#masspostsetup_' . $type . '">' . ucwords($type) . '</a></li>';
	}
}

echo '
	<li><a href="#masspostsetup_category">Category</a></li>
</ul>
';

echo __('<p>Selecting A short for all, will select all items in that column, N short for None, will deselect all selected items in that column</p>', 'ym');

$tbl_head = '
		<tr>
			<th>&nbsp;<br /><input type="checkbox" /> (ID) Title</th>';

foreach ($ym_package_types->types as $type) {
	$tbl_head .= '<th><div style="width: 100%; white-space: nowrap; overflow: hidden; text-overflow:ellipsis; text-align: center;">' . $type . '<br />' . ym_all_none('typepost_' . strtolower($type)) . '</div></th>';
}
$tbl_head .= '
		</tr>
';

$limit = 20;
$offset = ym_get('offset', 0);

// all post types
foreach ($types as $type) {
	if (!in_array($type, $ym_custom_post_types_ignores)) {
		$tbl_head = str_replace('YMHEREHERE', $type, $tbl_head);

		echo '<div id="masspostsetup_' . $type . '">';
		echo ym_start_box('&nbsp;');
		echo '<div style="font-weight: bold; margin-top: 10px;">' . sprintf(__('Please select one or more <b>%s</b> to update', 'ym'), $type) . '</div>';

		$posts = get_posts(array(
			'numberposts' => $limit,
			'offset' => $offset,
			'post_type' => $type,
			'post_status' => array('publish', 'draft', 'pending', 'future', 'private')
		));

		if ($post_count = count($posts)) {
			echo '
	<div id="tableDiv' . $type . '" class="ym_migrate_table_div">
	<table id="tabletable' . $type . '" class="ym_migrate_table">
		<thead>
		';
			echo $tbl_head .= '
		</thead>
		<tbody>';
			foreach ($posts as $post) {
				echo '<tr>';
				echo '<td><input type="checkbox" class="posts" id="post_' . $post->ID . '" name="posts[]" value="' . $post->ID . '" /> (' . $post->ID . ') ';
				if (strlen($post->post_title) > 20) {
					echo substr($post->post_title, 0, 20) . '..';
				} else {
					echo $post->post_title;
				}
				echo '</td>';
				
				foreach ($ym_package_types->types as $type) {
					echo '<td>';
					echo '<div style="text-align: center;">';
					echo '<input type="checkbox" class="ymselectpost typepost_' . str_replace(' ', '_', strtolower($type)) . '" id="accounttypes_' . $post->ID . '" name="account_types_' . $post->ID . '[]" value="' . $type . '" ';
					
					$selected = get_post_meta($post->ID, '_ym_account_type', true);
					$checked = explode(';', $selected);
					if (in_array($type, $checked)) {
					 	echo 'checked="checked" ';
				    }
					
					echo '/>';
					echo '</div>';
					echo '</td>';
				}
				
				echo '</tr>
				';
			}
			echo '
		</tbody>
		<tfoot>
			' . $tbl_head . '
		</tfoot>
	</table>
	</div>
	<div style="clear: both;">&nbsp;</div>
		<p class="submit"><input type="submit" name="migrate" class="button-primary" value="' . __('Setup', 'ym') . '" style="float: right;"/>
	';

			if ($post_count == $limit) {
				echo '<a href="' . YM_ADMIN_URL . '&ym_page=ym-content-mass_content_setup&offset=' . ($offset + $limit) . '" class="button-secondary" style="float: left;">' . __('Next Page of Content', 'ym') . '</a>';
			}

			echo '</p>';
		} else {
			echo '<div style="color: gray; font-style: italic;">' . sprintf(__('There are no <strong>%s</strong> posts available to mass update.', 'ym'), $type) . '</div>';
		}

		echo ym_end_box();
		echo '</div>';

		$tbl_head = str_replace($type, 'YMHEREHERE', $tbl_head);
	}
}

echo '<div id="masspostsetup_category">';
echo ym_start_box('&nbsp;');

$categories = get_categories();
if (count($categories)) {	
	echo '<div style="font-weight: bold; margin-top: 10px;">' . __('Please select one or more <b>Categories</b> to update, this will update all posts within that Category', 'ym') . '</div>';
	
	$tbl_head = str_replace('YMHEREHERE', 'category', $tbl_head);
	
	echo '
	<div id="tableDivCategorys" class="ym_migrate_table_div">
	<table id="tabletablecategorys" class="ym_migrate_table">
		<thead>' . $tbl_head . '</thead>
		<tbody>
		';

		foreach ($categories as $category) {
			echo '<tr>';
			echo '<td><input type="checkbox" class="categorys" id="category_' . $category->cat_ID . '" name="categorys[]" value="' . $category->cat_ID . '" /> ' . $category->category_nicename . '</td>';
			
			foreach ($ym_package_types->types as $type) {
				echo '<td>';
				echo '<div style="text-align: center;">';
				echo '<input type="checkbox" class="ymselectcategory typecategory_' . strtolower($type) . '" id="accounttypes_c_' . $category->cat_ID . '" name="account_types_c_' . $category->cat_ID . '[]" value="' . $type . '" />';
				echo '</div>';
				echo '</td>';
			}
			
			echo '</tr>';
		}

	echo '
		</tbody>
		<tfoot>' . $tbl_head . '</tfoot>
	</table>
	</div>
	';
	echo '<div style="clear: both;">&nbsp;</div>
		<p class="submit" style="text-align: right;"><input type="submit" name="migrate" class="button-primary" value="' . __('Setup', 'ym') . '" /></p>';
} else {
	echo '<div style="color: gray; font-style: italic;">' . __('There are no Categories available to mass update.', 'ym') . '</div>';
}
echo ym_end_box();
echo '</div>';

echo '
</div>
';

echo '</form>';
echo '</div>';

echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		jQuery('#masspostsetuptabs').tabs();
	});
</script>
";

function ym_all_none($id) {
	$r = '<div style="color: gray; margin-bottom: 10px; display: inline;">
<a style="cursor:pointer;" onclick="jQuery(\'input[@type=checkbox].' . $id . '\').attr(\'checked\', \'checked\');jQuery(\'input[@type=checkbox].posts\').attr(\'checked\', \'checked\')">A</a>/<a style="cursor:pointer;" onclick="jQuery(\'input[@type=checkbox].' . $id . '\').removeAttr(\'checked\');jQuery(\'input[@type=checkbox].posts\').attr(\'checked\', \'checked\')">N</a>
		</div>';
	return $r;
}
