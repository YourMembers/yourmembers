<?php

/*
* $Id: ym-membership-registration_flows.php 2539 2013-01-18 14:04:18Z bcarlyon $
* $Revision: 2539 $
* $Date: 2013-01-18 14:04:18 +0000 (Fri, 18 Jan 2013) $
*/

global $wpdb, $ym_formgen;
$here = str_replace('.php', '', basename(__FILE__));
$thispage = YM_ADMIN_URL . '&ym_page=' . $here;

$flows_table = $wpdb->prefix . 'ym_register_flows';
$pages_table = $wpdb->prefix . 'ym_register_pages';

$action = ym_get('action', ym_post('action'));

$what = $id = '';

echo '<div class="wrap" id="poststuff">';

// delete logic
switch ($action) {
	case 'delete':
		$what = ym_get('what');
		$id = ym_get('id');
		
		if ($what && $id) {
			if ($what == 'page') {
				// need to remove the page from flows it is in
				$query = 'SELECT * FROM ' . $flows_table;
				foreach ($wpdb->get_results($query) as $row) {
					$flows = unserialize($row->flow_pages);
					foreach ($flows as $index => $page) {
						if ($page == $id) {
							unset($flows[$index]);
							$flows = serialize($flows);
							$query = 'UPDATE ' . $flows_table . ' SET flow_pages = \'' . $flows . '\' WHERE flow_id = ' . $row->flow_id;
							$wpdb->query($query);
							break;
						}
					}
				}
			}

			$idfield = $what . '_id';
			$what .= 's_table';

			$query = 'DELETE FROM ' . $$what . ' WHERE ' . $idfield . ' = ' . $id;

			if ($wpdb->query($query)) {
				echo '<div id="message" class="updated"><p>' . __('Removed OK', 'ym') . '</p></div>';
			} else {
				echo '<div id="message" class="error"><p>' . __('Removal Failed', 'ym') . '</p></div>';
			}
		}
		
		$action = '';
}
// creation logic
switch ($action) {
	case 'generateflow':
		$load = ym_post('flow_controller');
		$load = str_replace('page[]=', '', $load);
		$load = explode('&', $load);
		
		$flows = array();
		foreach ($load as $page) {
			if ($page) {
				$flows[] = $page;
			}
		}
		if (count($flows)) {
			$flows = serialize($flows);
		} else {
			$flows = '';
		}
		
		$flow_id = ym_post('flow_id');
		$flow_name = ym_post('flow_name');
		$complete_url = ym_post('complete_url');
		$complete_text = ym_post('complete_text');
		$complete_button = ym_post('complete_button', __('Complete', 'ym'));

		if (empty($complete_button)) {
			$complete_button = __('Complete', 'ym');
		}

		$insert_id = FALSE;
		if ($flow_id) {
			$query = 'UPDATE ' . $flows_table . ' SET flow_name = \'' . $flow_name . '\', flow_pages = \'' . $flows . '\', complete_url = \'' . $complete_url . '\', complete_text = \'' . $complete_text . '\', complete_button = \'' . $complete_button . '\' WHERE flow_id = ' . $flow_id;
			if ($wpdb->query($query)) {
				$insert_id = $flow_id;
			}
		} else {
			$query = 'INSERT INTO ' . $flows_table . '(flow_name, flow_pages, complete_url, complete_text, complete_button) VALUES (\'' . $flow_name . '\', \'' . $flows . '\', \'' . $complete_url . '\', \'' . $complete_text . '\', \'' . $complete_button . '\')';
			if ($wpdb->query($query)) {
				$insert_id = $wpdb->insert_id;
			}
		}
		
		if ($insert_id) {
			echo '<div id="message" class="updated"><p>' . __('Your Flow was saved', 'ym') . '</p></div>';
			
			if (!$flow_name) {
				$flow_name = 'flow_' . $insert_id;
				$query = 'UPDATE '. $flows_table . ' SET flow_name = \'' . $flow_name . '\' WHERE flow_id = ' . $insert_id;
				$wpdb->query($query);
			}
		} else {
			echo '<div id="message" class="error"><p>' . __('Failed to save your Flow', 'ym') . '</p></div>';
		}
		
		$action = '';
		break;

	case 'generatepage':
		$page_items = array();
		
		$cycle_fields = array(
			'names',
			'types',
			'required',
			'classes',
			'options',

			'iflogic',
			'iflogic_quantity_loggedin',
			'iflogic_quantity_pack',
			'iflogic_quantity_custom',
			'iflogic_quantity_custom_compare',
			'iflogic_quantity_field',
			'iflogic_quantity_entry',
			'iflogic_quantity_memberfor_value',
			'iflogic_quantity_memberfor_unit',
			'iflogic_showhide',
		);
		
		$post_labels = array();
		if($_POST['labels']) $post_labels = $_POST['labels'];
		foreach ($post_labels as $index => $entry) {
			$label = $entry;
			
			$packet = array(
				'label' => urlencode(stripslashes($label))
			);
			foreach ($cycle_fields as $field) {
				if ($field == 'required') {
					$packet[$field] = ($_POST['required'][$index] ? 1 : 0);
				}else if($field == 'options'){
					//inssert option code
					// this needs cleaning up but is working

					if($_POST['names'] == 'ym_password'){
						$packet[$field] = ($_POST['options'][$index] ? 1 : 0);
					}
					else{
						$packet[$field] = isset($_POST[$field][$index]) ? $_POST[$field][$index] : '';
					}
				} else {
					$packet[$field] = $_POST[$field][$index];
				}
			}
			// construct
			$page_items[] = $packet;			
		}	
		$page = addslashes(serialize($page_items));
		
		$page_name = ym_post('page_name');
		$button_text = ym_post('button_text', __('Next', 'ym'));
		if (empty($button_text)) {
			$button_text = __('Next', 'ym');
		}

		$page_id = ym_post('page_id');
		$insert_id = FALSE;
		if ($page_id) {
			// should already have a name....
			$query = 'UPDATE ' . $pages_table . ' SET page_name = \'' . $page_name . '\', page_fields = \'' . $page . '\', button_text = \'' . $button_text . '\' WHERE page_id = ' . $page_id;
			if ($wpdb->query($query)) {
				$insert_id = $page_id;
			}
		} else {
			$query = 'INSERT INTO ' . $pages_table . '(page_name, page_fields, button_text) VALUES (\'' . $page_name . '\', \'' . $page . '\', \'' . $button_text . '\')';
			if ($wpdb->query($query)) {
				$insert_id = $wpdb->insert_id;
			}
		}
		
		if ($insert_id) {
			echo '<div id="message" class="updated"><p>' . __('Your Flow page was saved', 'ym') . '</p></div>';
			
			if (!$page_name) {
				$page_name = 'page_' . $insert_id;
				$query = 'UPDATE '. $pages_table . ' SET page_name = \'' . $page_name . '\' WHERE page_id = ' . $wpdb->insert_id;
				$wpdb->query($query);
			}
		} else if ($page_id) {
			echo '<div id="message" class="error"><p>' . __('Failed to update your Flow page, or no changes needed', 'ym') . '</p></div>';
		} else {
			echo '<div id="message" class="error"><p>' . __('Failed to save your Flow page', 'ym') . '</p></div>';
		}
		
		$action = '';
		break;
}

if ($action == 'edit') {
	$what = ym_get('what');
	$id = ym_get('id');

	if ($what == 'flow' && $id) {
		$action = 'createflow';
	} else if ($what == 'page' && $id) {
		$action = 'createpage';
	}
}

switch ($action) {
	case 'createflow':
		
		$flow = $flow_name = $complete_url = $complete_text = $complete_button = '';
		if ($what) {
			$query = 'SELECT * FROM ' . $flows_table . ' WHERE flow_id = ' . $id;
			$flow = $wpdb->get_results($query);
			$flow = $flow[0];

			$flow_name = $flow->flow_name;
			$complete_url = $flow->complete_url;
			$complete_text = $flow->complete_text;
			$complete_button = $flow->complete_button;

			if ($complete_text) {
				//complete_text_tab.selected
			}

			ym_box_top(__('Editing a Registration Flow', 'ym'));
		} else {
			ym_box_top(__('Creating a Registration Flow', 'ym'));
			$complete_button = __('Complete', 'ym');
		}
		
		echo '
<form action="' . $thispage . '&action=generateflow" method="post" style="width: 100%;">
	<fieldset>
		';

		if ($what) {
			echo '<input type="hidden" name="flow_id" value="' . $id . '" />';
		}

		echo '
		<label for="flow_name">' . __('Flow Name', 'ym') . '</label>
		<input type="text" name="flow_name" id="flow_name" value="' . $flow_name . '" />

		<p>' . __('Upon completion of a Registration Flow, you can either Redirect users to a page, or show them some text', 'ym') . '</p>

		<div id="complete_options">
			<ul>
				<li><a href="#complete_url_tab">Use URL</a></li>
				<li><a href="#complete_text_tab">Use Text</a></li>
			</ul>
			<div id="complete_url_tab">

		<label for="complete_url">' . __('Flow Complete Redirect URL:', 'ym') . '</label><br />
		<strong>' . site_url() . '</strong><input type="text" name="complete_url" id="complete_url" style="width: 300px;" value="' . $complete_url . '" />

			</div><div id="complete_text_tab">

		<label for="complete_text">' . __('Flow Complete Text:', 'ym') . '</label><br />
		<textarea name="complete_text" id="complete_text" rows="5" cols="60" style="width: 600px;">' . $complete_text . '</textarea>

			</div>
		</div>

		<p>
			<label for="complete_button">' . __('For cases where a Complete Button is shown, you can choose that text:', 'ym') . '
				<input type="text" name="complete_button" id="complete_button" value="' . $complete_button . '" />
			</label>
		</p>

		<p>' . __('In order to create your Flow, drag and drop pages from the right into the left, where you can also reorder the pages', 'ym') . '</p>
		
		<p class="flow_page_creator_texts">' . __('Pages in this Flow', 'ym') . '</p>
		<p class="flow_page_creator_texts" style="margin-left: 20px;">' . __('Available Pages for this Flow', 'ym') . '</p>

		<ul id="flow_pages_flow" class="flow_page_creator">
		';

		$ignore = array();
		if ($what) {
			$data = $flow->flow_pages;
			$data = unserialize($data);
			foreach ((array)$data as $id => $page_id) {
				if ($page_id) {
					$sub = 'SELECT * FROM ' . $pages_table . ' WHERE page_id = ' . $page_id;
					$row = $wpdb->get_results($sub);
					$row = $row[0];
					echo '
					<li id="page_' . $page_id . '">';
					ym_box_top($row->page_name . ' - ' . $row->page_id);
					$fields = $row->page_fields;
					$fields = unserialize($fields);
					$fields = count($fields);
					echo sprintf(__('This page has %s fields', 'ym'), $fields);
					ym_box_bottom();
					echo '
					</li>';
					$ignore[] = $page_id;
				}
			}
		}

		echo '
		</ul>
		
		<ul id="flow_pages_source" class="flow_page_creator">
		';
		
		// get pages
		if ($what) {
			$query = 'SELECT * FROM ' . $pages_table;
			if (count($ignore)) {
				$query .= ' WHERE ';
				foreach ($ignore as $id) {
					if ($id) {
						$query .= ' page_id != ' . $id . ' AND ';
					}
				}
				$query = substr($query, 0, -4);
			}

			$query .= ' ORDER BY page_id ASC';
		} else {
			$query = 'SELECT * FROM ' . $pages_table . ' ORDER BY page_id ASC';
		}
		foreach ($wpdb->get_results($query) as $row) {
			echo '
			<li id="page_' . $row->page_id . '">';
			ym_box_top($row->page_name . ' - ' . $row->page_id);
			$fields = $row->page_fields;
			$fields = unserialize($fields);
			$fields = count($fields);
			echo sprintf(__('This page has %s fields', 'ym'), $fields);
			ym_box_bottom();
			echo '
			</li>';
		}
		
		echo '
		</ul>
		
		<input type="hidden" name="flow_controller" id="flow_controller" value="" />
		<input type="button" name="flowcreatepageform" id="flowcreatepageform" class="button-primary" style="clear: both; float: right;" value="';
		if ($what) {
			echo __('Edit Flow', 'ym');
		} else {
			echo __('Create Flow', 'ym');
		}
		echo '" />
	</fieldset>
</form>	
';

echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		jQuery('#complete_options').tabs({
			";

			if (!$complete_url) {
				echo 'selected: 1';
			}

			echo "
		});
	});
</script>
";
		
		ym_box_bottom();
		
		break;

	case 'createpage':

		$pre_disable = array();

		$page = $page_name = $button_text = '';
		if ($what) {
			$query = 'SELECT * FROM ' . $pages_table . ' WHERE page_id = ' . $id;
			$page = $wpdb->get_row($query);

			$page_name = $page->page_name;
			$button_text = $page->button_text;
			$page = $page->page_fields;

			if ($page = unserialize($page)) {
				foreach ($page as $item => $field) {
					foreach ($field as $i => $f) {
						$page[$item][$i] = stripslashes(urldecode($f));
					}
				}
			} else {
				$page = array();
			}

			ym_box_top(__('Editing a Flow Page', 'ym'));
		} else {
			ym_box_top(__('Creating a Flow Page', 'ym'));
			$button_text = __('Next', 'ym');
		}

		echo '<div style="overflow: hidden"><div style="width: 100%;">';
		
		echo '
<form action="' . $thispage . '&action=generatepage" method="post" style="float: left;" id="generateform">
	<fieldset>';

		if ($what) {
			echo '<input type="hidden" name="page_id" value="' . $id . '" />';
		}

		echo '
		<label for="page_name">' . __('Page Name - A Handy name for reference', 'ym') . '</label>
		<input type="text" name="page_name" id="page_name" value="' . $page_name . '" />
		
		<div id="ym_form_builder" class="isconnected">
		';

		if ($page) {
			foreach ($page as $item) {
				/*
				[label] => block_logic
            [names] => 1
            [types] => block_logic
            [required] => 0
            [classes] =>
            [iflogic_type] => logicblock
            [iflogic_logic] => 
            [iflogic_parent] => 
            [iflogic] => loggedin
            [iflogic_quantity_loggedin] => 1
            [iflogic_quantity_pack] => 1
            [iflogic_quantity_custom] => Guest
            [iflogic_quantity_custom_compare] => 
            [iflogic_quantity_field] => 
            [iflogic_quantity_entry] => 
            [iflogic_quantity_memberfor_value] => 
            [iflogic_quantity_memberfor_unit] => hour
            */
				echo '
<div class="ym_form_element">
	';

			$field = ym_reg_flow_field_by_label($item['names']);
			if ($item['label'] == 'page_logic') {
				ym_box_top(__('Page Logic', 'ym'));
				echo '
					<input type="hidden" name="labels[]" value="' . $item['label'] . '" />
					<input type="hidden" name="names[]" class="logicindex" value="' . $item['names'] . '" />
					<input type="hidden" name="types[]" value="block_logic" />
					<input type="hidden" name="required[]" value="" />
					<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
					<input type="hidden" name="thisid" class="thisid" value="' . $item['label'] . '" />
					';
				echo ym_reg_flow_logic_options(false, $item);
				echo '<p>' . __('Page Logic is always Processed first.', 'ym') . '</p>';

				$field['name'][0] = 'page_logic';
				$field['name'][1] = 1;
				$field['single'] = 1;
			} else if ($item['label'] == 'freetext') {
				ym_box_top(__('FreeText', 'ym'));
				echo '
				<div style="display: block;">
					<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
					<input type="hidden" name="labels[]" value="freetext" />
					<label>' . __('Use this area for any text to display to the User, it is not a User Entry Field', 'ym') . '<br />
						<textarea rows="4" cols="60" name="names[]" class="freetextarea">' . $item['names'] . '</textarea>
					</label>
					<input type="hidden" name="types[]" value="freetext" />
					<input type="hidden" name="required[]" value="" />
									
					<input type="hidden" name="thisid" class="thisid" value="' . $item['label'] . '" />
				</div>
				';
				echo '<label style="float: left;">' . __('CSS Class', 'ym') . ' <input type="text" name="classes[]" value="' . $item['classes'] . '" /></label>';
				echo ym_reg_flow_logic_options(false, $item);
			} else {
				echo ym_box_top(__('Field', 'ym') . ' ' . $item['label']);
				echo $field['name'][0];
				echo '
				<div style="display: block;">

					<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
					<input type="text" name="labels[]" value="' . $item['label'] . '" readonly="' . ($field['nice'][1] ? 'readonly' : '') . '">
					<input type="text" name="names[]" value="' . $item['names'] . '" readonly="' . ($field['name'][1] ? 'readonly' : '') . '">
					';
					if ($field['type'][0] == 'payment' && !$field['type'][1]) {
						echo '<select name="types[]"><option value="payment_button" ' . ($item['types'] == 'payment_button' ? 'selected="selected"' : '') . ' >' . __('Payment Button', 'ym') . '</option><option value="payment_action" ' . ($item['types'] == 'payment_action' ? 'selected="selected"' : '') . ' >' . __('Payment Action', 'ym') . '</option></select>';
					} else if ($field['name'][0] == 'coupon') {
						echo '<select name="types[]">
							<option value="coupon_register" ' . ($item['types'] == 'coupon_register' ? 'selected="selected"' : '') . ' >' . __('Allow Registration Coupons', 'ym') . '</option>
							<option value="coupon_upgrade" ' . ($item['types'] == 'coupon_upgrade' ? 'selected="selected"' : '') . ' >' . __('Allow Upgrade Coupons', 'ym') . '</option>
							<option value="coupon_both" ' . ($item['types'] == 'coupon_both' ? 'selected="selected"' : '') . ' >' . __('Allow Both', 'ym') . '</option>
						</select>';
					} else if ($field['name'][0] == 'ym_password') {
						echo '<label>' . __('Confirm Password', 'ym') . ': <input type="checkbox" name="options[]" ' . ($item['options']['confirm_password'] ? 'checked="checked"' : '') . ' ' . ($field['options'][1]['confirm_password'] ? 'disabled="disabled"' : '') . '></label>
											<input type="hidden" name="types[]" value="custom" />';
					} else {
						echo '<input type="text" name="types[]" value="' . $field['type'][0] . '" readonly="' . ($field['type'][1] ? 'readonly' : '') . '" />
								<input type="hidden" name="options[]" value="" />';

					}

					echo '
					<label>' . __('Required', 'ym') . ': <input type="checkbox" name="required[]" ' . ($item['required'] ? 'checked="checked"' : '') . ' ' . ($field['required'][1] ? 'disabled="disabled"' : '') . '></label>
					<input type="hidden" name="thisid" class="thisid" value="' . $item['names'] . '">
				</div>
				';

				echo '<label style="float: left;">' . __('CSS Class', 'ym') . ' <input type="text" name="classes[]" value="' . $item['classes'] . '" /></label>';

				echo ym_reg_flow_logic_options(false, $item);
			}
				echo ym_box_bottom();
				echo '</div>';

				if ($field['single'] == 1) {
					$pre_disable[] = $field['name'];
				}
			}
		}

		echo '
		</div>
		
		<label for="button_text">' . __('Text to use on the Next Button:', 'ym') . '</label>
		<input type="text" name="button_text" id="button_text" value="' . $button_text . '" />

		<input type="button" id="munch" class="button-primary" style="float: right;" value="' . __('Process Flow Page', 'ym') . '" />
	</fieldset>
</form>
';

		$fields = ym_reg_flow_fields();

		$messages = array(
			'custom' => __('Custom Fields can be controlled under <strong>Members -> Custom Registration Fields</strong>', 'ym'),
		);
		
		// buttons
		echo '
<form action="" method="post" style="width: 320px; float: right;">
	<div id="ym_available_form_elements_tabs">
		<ul>
		';
		foreach ($fields as $index => $children) {
			echo '<li><a href="#' . $index . '">' . ucwords(str_replace('_', ' ', $index)) . '</a></li>';
		}
		echo '
		</ul>
		';
		
		foreach ($fields as $index => $children) {
			echo '<div id="' . $index . '">';
			echo '<ul>';
			foreach ($children as $id => $field) {
				// only time no name is when its page_logic block....
				echo '<li>';
				echo '<input type="button" class="ym_available_form_elements button-secondary ';
				if (isset($field['name']) && $field['name'] && in_array($field['name'], $pre_disable)) {
					echo 'ym_reg_flow_disabled';
				}
				echo '" id="' . $id . '" value="' . $field['nice'] . '" single="' . $field['single'] . '" ';
				if (isset($field['name']) && $field['name'] && in_array($field['name'], $pre_disable)) {
					echo ' disabled="disabled" readonly="readonly" ';
				}
				echo '/>';
				echo '</li>';
			}
			echo '</ul>';

			if (isset($messages[$index])) {
				echo '<p>' . $messages[$index] . '</p>';
			}

			echo '</div>';
		}
		
		echo '
	</div>
</form>
';
		
		// sources
		echo '
<form action="" method="post" style="display: none;">
	<fieldset>
';
		foreach ($fields as $index => $children) {
			foreach ($children as $id => $field) {
				if ($id == 'page_logic') {
					echo '<div id="source_' . $id . '">';
					ym_box_top($field['nice']);
					echo '
						<input type="hidden" name="labels[]" value="' . $id . '" />
						<input type="hidden" name="names[]" class="logicindex" value="" />
						<input type="hidden" name="types[]" value="block_logic" />
						<input type="hidden" name="required[]" value="" />
						<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
						<input type="hidden" name="thisid" class="thisid" value="' . $id . '" />
						';
					echo ym_reg_flow_logic_options(1);
					echo '<p>' . __('Page Logic is always Processed first.', 'ym') . '</p>';
					ym_box_bottom();
					echo '</div>';
				} else if ($id == 'freetext') {
					echo '
						<div id="source_' . $id . '">
							';
							ym_box_top(__('FreeText', 'ym'));
							echo '
				<div style="display: block;">
							<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
							<input type="hidden" name="labels[]" value="freetext" />
							<label>' . __('Use this area for any text to display to the User, it is not a User Entry Field', 'ym') . '<br />
								<textarea rows="4" cols="60" name="names[]" class="freetextarea"></textarea>
							</label>
							<input type="hidden" name="types[]" value="freetext" />
							<input type="hidden" name="required[]" value="" />
							
							<input type="hidden" name="thisid" class="thisid" value="' . $id . '" />
				</div>
							';
							echo '<label style="float: left;">' . __('CSS Class', 'ym') . ' <input type="text" name="classes[]" value="' . $field['classes'] . '" /></label>';
							echo ym_reg_flow_logic_options();
							ym_box_bottom();
							echo '
						</div>
					';
				} else {
					echo '
		<div id="source_' . $id . '">
			';
			ym_box_top(__('Field', 'ym')  . ' ' . $field['text'][0]);
			echo '
				<div style="display: block;">
			<span class="ym_cross ym_delete_field" style="cursor: pointer; float: right;"></span>
			<input type="text" name="labels[]" value="' . $field['text'][0] . '" readonly="' . ($field['text'][1] ? 'readonly' : '') . '" />
			<input type="text" name="names[]" value="' . $field['name'][0] . '" readonly="' . ($field['name'][1] ? 'readonly' : '') . '" />
			';
			if ($field['type'][0] == 'payment' && !$field['type'][1]) {
				echo '<select name="types[]"><option value="payment_button">' . __('Payment Button', 'ym') . '</option><option value="payment_action">' . __('Payment Action', 'ym') . '</option></select>';
			} else if ($field['name'][0] == 'coupon') {
				echo '<select name="types[]">
					<option value="coupon_register">' . __('Allow Registration Coupons', 'ym') . '</option>
					<option value="coupon_upgrade">' . __('Allow Upgrade Coupons', 'ym') . '</option>
					<option value="coupon_both">' . __('Allow Both', 'ym') . '</option>
				</select>';
			} else if ($field['name'][0] == 'ym_password') {
						echo '<label>' . __('Confirm Password', 'ym') . ': <input type="checkbox" name="options[]" ' . ($item['options']['confirm_password'] ? 'checked="checked"' : '') . ' ' . ($field['options'][1]['confirm_password'] ? 'disabled="disabled"' : '') . '></label>';
			} else {
				echo '<input type="text" name="types[]" value="' . $field['type'][0] . '" readonly="' . ($field['type'][1] ? 'readonly' : '') . '" />';
			}
			echo '
			<label>Required: <input type="checkbox" name="required[]" ' . ($field['required'][0] ? 'checked="checked"' : '') . ' ' . ($field['required'][1] ? 'disabled="disabled"' : '') . ' /></label>
			<input type="hidden" name="thisid" class="thisid" value="' . $id . '" />
			</div>
			';
			echo '<label style="float: left;">' . __('CSS Class', 'ym') . ' <input type="text" name="classes[]" value="' . $field['classes'] . '" /></label>';

			echo ym_reg_flow_logic_options();
			ym_box_bottom();
			echo '
		</div>
';
				}
			}
		}
		echo '
	</fieldset>
</form>
';
		echo '</div></div>';
		ym_box_bottom();
		break;

	case 'update_facebook_widget':
		update_option('ym_register_flow_fb_app_id', ym_post('ym_register_flow_fb_app_id'));
		update_option('ym_register_flow_fb_secret', ym_post('ym_register_flow_fb_secret'));
		ym_display_message(__('Updated Register Flow Facebook Register Widget', 'ym'));

	default:
		ym_box_top(__('Registration Flows', 'ym'));
echo '<p>' . __('You can create custom Registration Flows for use with the [ym_register] shortcode', 'ym') . '</p>';

echo '<table class="form-table widefat">
<tr>
	<th>' . __('Flow ID', 'ym') . '</th>
	<th>' . __('Flow Name', 'ym') . '</th>
	<th>' . __('Pages in Flow', 'ym') . '</td>
	<th></th>
</tr>
';

$query = 'SELECT * FROM ' . $flows_table . ' ORDER BY flow_id ASC';
foreach ($wpdb->get_results($query) as $row) {
	echo '<tr>';
	echo '<td>' . $row->flow_id . '</td>';
	echo '<td>' . $row->flow_name . '</td>';
	$flow_pages = $row->flow_pages;
	if ($flow_pages = unserialize($flow_pages)) {
		$flow_pages = count($flow_pages);
	} else {
		$flow_pages = 0;
	}
	echo '<td>' . $flow_pages . '</td>';
	echo '<td><a href="' . $thispage . '&action=edit&what=flow&id=' . $row->flow_id . '" class="button-primary">' . __('Edit', 'ym') . '</a>
	<a href="' . $thispage . '&action=delete&what=flow&id=' . $row->flow_id . '" class="deletelink button-secondary">' . __('Delete', 'ym') . '</a></td>';
	echo '</tr>';
}
if (!$wpdb->num_rows) {
	echo '<tr><td colspan="4" style="text-align: center;">' . __('There are currently no flows', 'ym') . '</td></tr>';
}
echo '<tr>
<td colspan="3">&nbsp;</td>
<td><a href="' . $thispage . '&action=createflow" class="button-primary">' . __('Create Flow', 'ym') . '</a></td>
</tr>';

echo '</table>';

ym_box_bottom();
ym_box_top(__('Registration Flow Pages', 'ym'));

echo '<p>' . __('You can create Flow Pages to go in a Registration Flow', 'ym');
echo '<table class="form-table widefat">
<tr>
	<th>' . __('Page ID', 'ym') . '</th>
	<th>' . __('Page Name', 'ym') . '</th>
	<th>' . __('Fields in Page', 'ym') . '</td>
	<th></th>
</tr>
';

$query = 'SELECT * FROM ' . $pages_table . ' ORDER BY page_id ASC';
foreach ($wpdb->get_results($query) as $row) {
	echo '<tr>';
	echo '<td>' . $row->page_id . '</td>';
	echo '<td>' . $row->page_name . '</td>';
	$page_fields = $row->page_fields;

	if ($page_fields = unserialize($page_fields)) {
		$page_fields = count($page_fields);
	} else {
		$page_fields = 0;
	}
	echo '<td>' . $page_fields . '</td>';
	echo '<td><a href="' . $thispage . '&action=edit&what=page&id=' . $row->page_id . '" class="button-primary">' . __('Edit', 'ym') . '</a>
	<a href="' . $thispage . '&action=delete&what=page&id=' . $row->page_id . '" class="deletelink button-secondary">' . __('Delete', 'ym') . '</a></td>';
	echo '</tr>';
}
if (!$wpdb->num_rows) {
	echo '<tr><td colspan="4" style="text-align: center;">' . __('There are currently no flow pages', 'ym') . '</td></tr>';
}

echo '<tr>
<td colspan="3">&nbsp;</td>
<td><a href="' . $thispage . '&action=createpage" class="button-primary">' . __('Create Page', 'ym') . '</a></td>
</tr>';
echo '</table>';

		ym_box_bottom();

		ym_box_top(__('Widget Settings', 'ym'));

		echo '<p>' . __('Some Registration Flow Page elements have settings, you can set them below', 'ym') . '</p>';

		echo '
<form action="" method="post">
	<fieldset>
		<input type="hidden" name="action" value="update_facebook_widget" />

		<table class="form-table">
			<tr>
				<th>' . __('Facebook App ID', 'ym') . '</th>
				<td><input type="text" name="ym_register_flow_fb_app_id" value="' . get_option('ym_register_flow_fb_app_id') . '" /></td>
			</tr><tr>
				<th>' . __('Facebook Secret', 'ym') . '</th>
				<td><input type="text" name="ym_register_flow_fb_secret" value="' . get_option('ym_register_flow_fb_secret') . '" /></td>
			</tr>';
/*
			$fields = array(
				'birthday',
				'gender',
				'location',
				'password',
				'captcha',
				'first_name',
				'last_name'
			);
*/
			echo '
		</table>
		<input type="submit" class="button-primary" style="float: right;" value="' . __('Update Facebook Widget Settings', 'ym') . '" />
	</fieldset>
</form>
';
//				'settings'	=> array(
//					'client_id' => array('Client ID', ''),
//					'fields' => array('Fields', 'name,birthday,gender,location,email,password,captcha,first_name,last_name'),
//				)

}

if ($action) {
	echo '<p><a href="' . $thispage . '" class="button-secondary">' . __('Back to flow control', 'ym') . '</a></p>';
}

echo '</div>';

function ym_reg_flow_fields() {
		// commons abstracted out here for clarity
		$commons = array(
			'login'		=> array(
				'nice'		=> 'Login',
				'text'		=> array('Login', 0),
				'name'		=> array('login', 0),
				'type'		=> array('widget', 1),
				'required'	=> array('0', 1),
				'single'	=> 1,
				'classes'	=> '',
				'options'	=> array()
			),
			'freetext'	=> array(
				'nice'		=> 'FreeText',
				'text'		=> array('', 0),
				'name'		=> array('freetext', 0),
				'type'		=> array('textarea', 1),
				'required'	=> array('0', 1),
				'single'	=> 0,
				'classes'	=> '',
			),
			'username'	=> array(
				'nice'		=> 'Username',
				'text'		=> array('Username', 0),
				'name'		=> array('username', 1),
				'type'		=> array('text', 1),
				'required'	=> array('1', 1),
				'single'	=> 1,
				'classes'	=> '',
			),
			'email_address'	=> array(
				'nice'		=> 'Email Address',
				'text'		=> array('Email Address', 0),
				'name'		=> array('email_address', 1),
				'type'		=> array('text', 1),
				'required'	=> array('1', 1),
				'single'	=> 1,
				'classes'	=> '',
			),

			'page_logic'		=> array(
				'nice'		=> __('Page Logic', 'ym'),
				'text'		=> array('page_logic', 1),
				'name'		=> array('page_logic', 1),
				'type'		=> array('page_logic', 1),
				'required'	=> array('0', 1),
				'single'	=> 1,
				'classes'	=> '',
			),

			'register_facebook'	=> array(
				'nice'		=> __('Register with Facebook', 'ym'),
				'text'		=> array('Register with Facebook', 0),
				'name'		=> array('register_facebook', 0),
				'type'		=> array('widget', 1),
				'required'	=> array('0', 1),
				'single'	=> 1,
				'classes'	=> '',
			),
		);
		
		// load custom fields that are active/enabled
		$fld_obj = get_option('ym_custom_fields');
		$entries = $fld_obj->entries;
		$order = explode(';', $fld_obj->order);
		
		$req_lock = array(
			'subscription_introduction',
			'subscription_options',
			'terms_and_conditions',
			'coupon',
		);
		$exclude = array(
			'user_email',
		);

		$customs = array();
		foreach ($order as $id) {
			$entry = ym_get_custom_field_by_id($id);

			if (in_array($entry['name'], $exclude)) {
				continue;
			}
			
			if (!in_array($entry['name'], $req_lock)) {
				$req = 0;
			} else {
				$req = 1;
			}

			$customs[$entry['name']] = array(
				'id'		=> $id,
				'nice'		=> $entry['label'],
				'text'		=> array($entry['label'], 1),
				'name'		=> array($entry['name'], 1),
				'type'		=> array('custom', 1),
				'required'	=> array($entry['required'], $req),
				'single'	=> 1,
				'classes'	=> '',
			);
			if($entry['name'] == 'ym_password'){
				$customs['ym_password']['options']['confirm_password'] = 0;
			}
		}
		
		// load active payment gateways
		$gateways = array();
		$activated = get_option('ym_modules');
		
		foreach ($activated as $gateway) {
			$class = new $gateway;
			$name = $class->name;
			
			$gateways[$gateway . '_button'] = array(
				'nice'		=> $name,
				'text'		=> array($name, 0),
				'name'		=> array($gateway, 1),
				'type'		=> array('payment', 0),
				'required'	=> array('0', 1),
				'single'	=> 0,
				'classes'	=> '',
			);
		}
		
		// package buy now buttons
		$buy_nows = array();


		// name is AKA label for the field
		$fields = array(
			'common'			=> $commons,
			'custom'			=> $customs,
			'payment'			=> $gateways,
//			'buy_now'			=> $buy_nows,
		);

		return $fields;
}

function ym_reg_flow_field_by_label($label) {
	$fields = ym_reg_flow_fields();

	foreach ($fields as $type => $els) {
		foreach ($els as $field) {
			if (isset($field['name'][0]) && $field['name'][0] == $label) {
				return $field;
			}
		}
	}
	return FALSE;
}

function ym_reg_flow_logic_options($intro = FALSE, $item = FALSE) {
	$data = ym_reg_flow_fields();
	$customs = $data['custom'];
	$r = '<div style="display: block; clear: both;">';

	if (!$item) {
		$item = array(
			'iflogic' => '',
			'iflogic_quantity_loggedin' => '',
			'iflogic_quantity_pack' => '',
			'iflogic_quantity_custom' => '',
			'iflogic_quantity_custom_compare' => '',
			'iflogic_quantity_field' => '',
			'iflogic_quantity_entry' => '',
			'iflogic_quantity_memberfor_value' => '',
			'iflogic_quantity_memberfor_unit' => '',
			'iflogic_showhide' => '',
		);
	}

	if (!$intro) {
		$r .= '<p>' . __('You can apply display logic to decide wheather to show/hide', 'ym') . '</p>';
	}

								$r .= '
								<p>' . __('For Fields you can use * to match something or leave blank to match empty', 'ym') . '</p>
								<div class="iflogiccontrol">
								
								' . __('If user', 'ym') . ' <select name="iflogic[]" class="iflogic">
								';

								$r .= '
									<option value="">' . __('Select', 'ym') . '</option>
									<option value="loggedin" ' . ($item['iflogic'] == 'loggedin' ? 'selected="selected"' : '') . ' >' . __('Is Logged In', 'ym') . '</option>
									<option value="buying" ' . ($item['iflogic'] == 'buying' ? 'selected="selected"' : '') . ' >' . __('Buying Pack', 'ym') . ' </option>
									<option value="currentlyon" ' . ($item['iflogic'] == 'currentlyon' ? 'selected="selected"' : '') . ' >' . __('Currently On Pack', 'ym') . '</option>
									<option value="accounttype" ' . ($item['iflogic'] == 'accounttype' ? 'selected="selected"' : '') . ' >' . __('Account Type is', 'ym') . '</option>
									<option value="filledin" ' . ($item['iflogic'] == 'filledin' ? 'selected="selected"' : '') . ' >' . __('Custom Field', 'ym') . '</option>
									<option value="servervar" ' . ($item['iflogic'] == 'servervar' ? 'selected="selected"' : '') . ' >' . __('$_SERVER Variable', 'ym') . '</option>
									<option value="getvar" ' . ($item['iflogic'] == 'getvar' ? 'selected="selected"' : '') . ' >' . __('$_GET Variable', 'ym') . '</option>
									<option value="postvar" ' . ($item['iflogic'] == 'postvar' ? 'selected="selected"' : '') . ' >' . __('$_POST Variable', 'ym') . '</option>
									<option value="cookievar" ' . ($item['iflogic'] == 'cookievar' ? 'selected="selected"' : '') . ' >' . __('$_COOKIE Variable', 'ym') . '</option>';
//<!--								<option value="memberfor">' . __('Been a Member for at least', 'ym') . '</option>-->
								$r .= '
									<option value="registeredfor" ' . ($item['iflogic'] == 'registeredfor' ? 'selected="selected"' : '') . ' >' . __('Been Registered for at least', 'ym') . '</option>
									<option value="expiresin" ' . ($item['iflogic'] == 'expiresin' ? 'selected="selected"' : '') . ' >' . __('Expires in more than', 'ym') . '</option>
								</select>
								
								<select name="iflogic_quantity_loggedin[]" class="logicoption loggedin" style="' . ($item['iflogic'] == 'loggedin' ? '' : 'display: none;') . '">
									<option value="1" ' . ($item['iflogic_quantity_loggedin'] == 1 ? 'selected="selected"' : '') . ' >Yes</option>
									<option value="0" ' . ($item['iflogic_quantity_loggedin'] == 0 ? 'selected="selected"' : '') . ' >No</option>
								</select>
								<select name="iflogic_quantity_pack[]" class="logicoption buying" style="' . ($item['iflogic'] == 'buying' ? '' : 'display: none;') . '">
								';

								$packoptions = '';

								global $ym_packs;
								foreach ($ym_packs->packs as $pack) {
									$label = ym_get_pack_label($pack['id']);
									$packoptions .= '<option value="' . $pack['id'] . '"';
									if ($pack['id'] == $item['iflogic_quantity_pack']) {
										$packoptions .= 'selected="selected"';
									}
									$packoptions .= '>(' . $pack['id'] . ') ' . $label . '</option>';
								}
								$r .= $packoptions;

								$r .= '
								</select>

								<select name="iflogic_quantity_pack[]" class="logicoption currentlyon" style="' . ($item['iflogic'] == 'currentlyon' ? '' : 'display: none;') . '">
								';

								$r .= '<option value="0">' . __('No Pack', 'ym') . '</option>';
								$r .= $packoptions;

								$r .= '
								</select>

								<select name="iflogic_quantity_custom[]" class="logicoption accounttype" style="' . ($item['iflogic'] == 'accounttype' ? '' : 'display: none;') . '">
								';

								global $ym_package_types;
								foreach ($ym_package_types->types as $type) {
									$r .= '<option value="' . $type . '"';
									if ($type == $item['iflogic_quantity_custom']) {
										$r .= ' selected="selected" ';
									}
									$r .= '>' . $type . '</option>';
								}

								$r .= '
								</select>

								<select name="iflogic_quantity_custom[]" class="logicoption filledin" style="' . ($item['iflogic'] == 'filledin' ? '' : 'display: none;') . '">
								';

								// customs
								$exclude = array(
									'subscription_introduction',
									'subscription_options',
									'terms_and_conditions'
								);
								$ok = FALSE;
								foreach ($customs as $custom) {
									$nice = $custom['nice'];
									$cid = $custom['id'];

									if (!in_array($label, $exclude)) {
										$r .= '<option value="' . $cid . '" ';
										if ($cid == $item['iflogic_quantity_custom']) {
											$r .= ' selected="selected" ';
										}
										$r .= '>' . $nice . '</option>';
										$ok = TRUE;
									}
								}
								if (!$ok) {
									$r .= '<option value="">No Available Fields</option>';
								}

								$r .= '
								</select>
								<label class="logicoption filledin" style="' . ($item['iflogic'] == 'filledin' ? '' : 'display: none;') . '">' . __('Field Value:', 'ym') . ' 
									<input type="text" name="iflogic_quantity_custom_compare[]" value="' . $item['iflogic_quantity_custom_compare'] . '" /></label>
								
								<label class="logicoption servervar getvar postvar cookievar" style="' . (substr($item['iflogic'], -3, 3) == 'var' ? '' : 'display: none;') . '">' . __('Field Name:', 'ym') . ' 
									<input type="text" name="iflogic_quantity_field[]" value="' . $item['iflogic_quantity_field'] . '" /></label>
								<label class="logicoption servervar getvar postvar cookievar" style="' . (substr($item['iflogic'], -3, 3) == 'var' ? '' : 'display: none;') . '">' . __('Field Value:', 'ym') . ' 
									<input type="text" name="iflogic_quantity_entry[]" value="' . $item['iflogic_quantity_entry'] . '" /></label>
								
								<input class="logicoption memberfor registeredfor expiresin" style="' . ($item['iflogic'] == 'memberfor' || $item['iflogic'] == 'registeredfor' || $item['iflogic'] == 'expiresin' ? '' : 'display: none;') . '" type="text" name="iflogic_quantity_memberfor_value[]" />
								<select class="logicoption memberfor registeredfor expiresin" name="iflogic_quantity_memberfor_unit[]" style="' . ($item['iflogic'] == 'memberfor' || $item['iflogic'] == 'registeredfor' || $item['iflogic'] == 'expiresin' ? '' : 'display: none;') . '">
									<option value="hour" ' . ($item['iflogic_quantity_memberfor_unit'] == 'hour' ? 'selected="selected"' : '') . ' >' . __('Hour', 'ym') . '</option>
									<option value="day" ' . ($item['iflogic_quantity_memberfor_unit'] == 'day' ? 'selected="selected"' : '') . ' >' . __('Day', 'ym') . '</option>
									<option value="week" ' . ($item['iflogic_quantity_memberfor_unit'] == 'week' ? 'selected="selected"' : '') . ' >' . __('Week', 'ym') . '</option>
									<option value="month" ' . ($item['iflogic_quantity_memberfor_unit'] == 'month' ? 'selected="selected"' : '') . ' >' . __('Month', 'ym') . '</option>
									<option value="year" ' . ($item['iflogic_quantity_memberfor_unit'] == 'year' ? 'selected="selected"' : '') . ' >' . __('Year', 'ym') . '</option>
								</select>
								
								<span class="logicoption loggedin buying currentlyon accounttype filledin servervar getvar postvar cookievar memberfor registeredfor expiresin" style="' . ($item['iflogic'] != '' ? '' : 'display: none;') . '">
									' . __(' then ', 'ym') . '

									<select name="iflogic_showhide[]">
										<option value="show" ' . ($item['iflogic_showhide'] == 'show' ? 'selected="selected"' : '') . '>' . __('Show', 'ym') . '</option>
										<option value="hide" ' . ($item['iflogic_showhide'] == 'hide' ? 'selected="selected"' : '') . '>' . __('Hide', 'ym') . '</option>
									</select>
								</span>

								</div>
';

	$r .= '</div>';

	return $r;
}
