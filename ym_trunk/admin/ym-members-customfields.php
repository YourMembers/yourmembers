<?php

/*
* $Id: ym-members-customfields.php 2525 2013-01-15 14:05:25Z bcarlyon $
* $Revision: 2525 $
* $Date: 2013-01-15 14:05:25 +0000 (Tue, 15 Jan 2013) $
*/

global $ym_sys, $ym_custom_field_types;

if (isset($_GET['mode']) && ($_GET['mode'] == 'del')) {
	$id = $_GET['id'];
	$fld_obj = get_option('ym_custom_fields');

	// remove from orders
	$orders = $fld_obj->order;

	if (strpos($orders, ';') !== false) {
		$orders = explode(';', $orders);

		$key = array_search($id, $orders);

		if ($key) {
			$orders[$key] = null;
			unset($orders[$key]);
		}

		$orders = implode(';', $orders);
	} else {
		if ($id == $orders) {
			$orders = '';
		}
	}

	$fld_obj->order = $orders;

	// remove the field
	$entries = $fld_obj->entries;

	foreach ($entries as $key => $entry) {
		if ($entry['id'] == $id) {
			$entries[$key] = null;
			unset($entries[$key]);
			break;
		}
	}

	$fld_obj->entries = $entries;

	update_option('ym_custom_fields', $fld_obj);

	$msg = __('Custom Field Deleted','ym');
}

if ( (isset($_POST['new'])) && (! empty($_POST['new'])) ) {

	$name = strtolower(ym_post('name'));
	$label = stripcslashes(ym_post('label'));
	$caption = stripcslashes(ym_post('caption'));

	$type = ym_post('type');
	$available_values = ym_post('available_values');
	$value = stripcslashes(ym_post('value'));
	$page = ym_post('page', 1);

	$required = ym_post('required', false);
	$readonly = ym_post('readonly', false);
	$profile_only = ym_post('profile_only', false);
	$no_profile = ym_post('no_profile', false);

	$new_error = '';

	if (empty($name)) {
		$new_error = __('Name is required','ym');
	} else if (!validate_custom_field_label($name)) {
		// failed chars
		$new_error = __('Name can only contain a-z, 0-9, and _ characters', 'ym');
	} else if (!validate_custom_field_label_length($name)) {
		echo '<div id="message" class="error"><p>' . __('You have created a name that is longer than 10 Characters and cannot be synced with MailChimp', 'ym') . '</p></div>';
	}
	
	// check to see if the custom field already exists
	// name and label must be unique
	$fields = get_option('ym_custom_fields');
	foreach ($fields->entries as $entry) {
		$entry['label'] = isset($entry['label']) ? $entry['label'] : '';
		if ($entry['name'] == $name || $entry['label'] == $label) {
			$new_error = __('New Custom Field must have a Unique name and label', 'ym');
		}
	}

	if (empty($new_error)) {
		$id = $fields->next_id;

		$entry = array(
			'id'=>$id,
			'name'=>$name,
			'label'=>$label,
			'caption'=>$caption,
			'available_values'=>$available_values,
			'type'=>$type,
			'required'=>$required,
			'value'=>$value,
			'page'=>$page,
			'readonly'=>$readonly,
			'profile_only'=>$profile_only,
			'no_profile'=>$no_profile,
			'builtin' => FALSE
		);

		$fields->entries[] = $entry;
		$fields->next_id = $id + 1;

		$enable = ym_post('enable', FALSE);
		if ($enable) {
			$fields->order .= ';' . $id;
		}

		update_option('ym_custom_fields', $fields);
		$new_msg = __('Custom Field Created','ym');
	}
	
} elseif ((isset($_POST['sort'])) && (!empty($_POST['sort']))) {
	$order = $_POST['order'];

	if (!empty($order)) {
		if (strpos($order, '&') !== false) {
			$arr_order = explode('&', $order);
		} else {
			$arr_order = array($order);
		}

		$ord = array();
		$arr_order = array_unique($arr_order); //to stop duplication

		foreach ($arr_order as $o) {
			$o = explode('=', $o);
			// check is checked
			if (ym_post('enable_' . $o[1])) {
				$ord[] = $o[1];
			}
		}

		$order = implode(';', $ord);
	}

	$fld_obj = get_option('ym_custom_fields');
	$fld_obj->order = $order;
	update_option('ym_custom_fields', $fld_obj);

	$sort_msg = __('Custom Fields Sorting Updated','ym');
} elseif ((isset($_POST['update'])) && (!empty($_POST['update']))) {
	$id = $_POST['id'];

	$name = strtolower(ym_post('name'));
	$label = stripcslashes(ym_post('label'));
	$caption = stripcslashes(ym_post('caption'));

	$type = ym_post('type');
	$available_values = ym_post('available_values');
	$value = stripcslashes(ym_post('value'));
	$page = ym_post('page', 1);

	$required = ym_post('required', false);
	$readonly = ym_post('readonly', false);
	$profile_only = ym_post('profile_only', false);
	$no_profile = ym_post('no_profile', false);

	$new_error = '';

	if (empty($name)) {
		$new_error = __('Name is required','ym');
	} else if (!validate_custom_field_label($name)) {
		// failed chars
		$new_error = __('Name can only contain a-z, 0-9, and _ characters', 'ym');
	} else if (!validate_custom_field_label_length($name)) {
		echo '<div id="message" class="error"><p>' . __('You have created a name that is longer than 10 Characters and cannot be synced with MailChimp', 'ym') . '</p></div>';
	}

	if (empty($new_error)) {
		$fld_obj = get_option('ym_custom_fields');
		$entries = $fld_obj->entries;

		foreach ($entries as $key => $entry) {
			if ($id == $entry['id']) {
				$old = ym_get_custom_field_by_id($id);

				$entry = array(
					'id'=>$id,
					'name'=>$name,
					'label'=>$label,
					'caption'=>$caption,
					'available_values'=>$available_values,
					'type'=>$type,
					'required'=>$required,
					'value'=>$value,
					'page'=>$page,
					'readonly'=>$readonly,
					'profile_only'=>$profile_only,
					'no_profile'=>$no_profile,
					'builtin' => $old['builtin']
				);
				
				$fld_obj->entries[$key] = $entry;

				$sort_msg = __('Custom Field Updated','ym');
				$_GET['mode'] = '';
				$_GET['id'] = '';
			}
		}
		update_option('ym_custom_fields', $fld_obj);
	}
}

$fields = get_option('ym_custom_fields');
$entries = $fields->entries;
$ordering = $fields->order;

if ((isset($msg)) && (! empty($msg))) {
	ym_display_message($msg);
}

if (isset($error) && !empty($error)) {
	ym_display_mesage($error, 'error');
}

if ((isset($sort_msg)) && (! empty($sort_msg))) {
	ym_display_message($sort_msg);
}

if (isset($sort_error) && !empty($sort_error)) {
	ym_display_mesage($sort_error, 'error');
}


if (!$ym_sys->modified_registration) {
	ym_display_message(__('Modified registration is currently turned off. Custom fields will only show on the profile', 'ym'), 'error');
}

echo '
<div class="wrap" id="poststuff">
';

if (ym_get('mode') != 'edit') {

echo ym_start_box('&nbsp;');

echo '<form name="frm" action="" method="post" onsubmit="ym_process_custom_field_sort();">
 <table width="60%" border="0" cellpadding="5" cellspacing="5" align="center" class="form-table" id="sorttable">';
 $head = '
 <tr>
	<th scope="col">' . __('Enable', 'ym') . '</th>
 <th scope="col">' . __('ID','ym') . '</th>
 <th scope="col">' . __('Name','ym') . '</th>
 <th scope="col">' . __('Label','ym') . '</th>
 <th scope="col">' . __('Caption','ym') . '</th>
 <th scope="col">' . __('Type','ym') . '</th>
 <th scope="col">' . __('Required','ym') . '</th>
 <th scope="col">' . __('Read Only','ym') . '</th>
 <th scope="col">' . __('Profile Only','ym') . '</th>
 <th scope="col">' . __('Hide On Profile','ym') . '</th>
 <th scope="col">' . __('Page','ym') . '</th>
 <th scope="col" colspan="2">' . __('Action','ym') . '</th>
 </tr>
';
echo '<thead>' . $head . '</thead>';
echo '<tfoot>' . $head . '</tfoot>';

$ordering = explode(';', $ordering);

$rows = array();
if (count($entries) > 0) {
	$style = '';
	
	foreach ($entries as $entry) {
		$style = ($style ? '':'class="alternate"');
		$req = ($entry['required'] == true) ? __('Yes','ym') : __('No','ym');
		$readonly = ($entry['readonly'] == true) ? __('Yes','ym') : __('No','ym');
		$page = (isset($entry['page']) ? $entry['page']:1);
		$profile_only = ($entry['profile_only'] == true) ? __('Yes','ym') : __('No','ym');
		$no_profile = ($entry['no_profile'] == true) ? __('Yes','ym') : __('No','ym');

//		if ($entry['name'] == 'terms_and_conditions' || $entry['name'] == 'subscription_introduction') {
//			$links = '<a href="'.YM_ADMIN_URL.'&ym_page=ym-advanced-messages">' . __('Edit','ym') . '</a>';
//		} elseif ($entry['name'] == 'subscription_options') {
//			$links = '<a href="'.YM_ADMIN_URL.'&ym_page=ym-membership-packages">' . __('Edit','ym') . '</a>';
//		} elseif ($entry['name'] == 'birthdate' || $entry['name'] == 'country') {
//			$links = '';
		if ($entry['name'] == 'birthdate' || $entry['name'] == 'country' || $entry['name'] == 'coupon') {
			$edit_link = YM_ADMIN_URL.'&ym_page=ym-members-customfields&mode=edit&id='.$entry['id'];
			$links = '<a href="' . $edit_link . '" class="ym_edit" title="' . __('Edit','ym') . '"></a>';
			$links .= '</td><td align="center">';
		} else {
			$edit_link = YM_ADMIN_URL.'&ym_page=ym-members-customfields&mode=edit&id='.$entry['id'];
			$links = '<a href="' . $edit_link . '" class="ym_edit" title="' . __('Edit','ym') . '"></a>';

			$links .= '</td><td align="center">';

			if (isset($entry['builtin']) && $entry['builtin']) {
			} else {
				$links .= '<a href="' . YM_ADMIN_URL.'&ym_page=ym-members-customfields&mode=del&id=' . $entry['id'] . '" class="delete_link ym_delete" title="' . __('Delete','ym') . '"></a>';
			}
		}
		
		$inarray = '';
		if (in_array($entry['id'], $ordering)) {
			$inarray = ' checked="checked" ';
		}
		
		$rows[$entry['id']] = '
 <tr class="sorttablesort item" id="item_'. $entry['id'] .'" '. $style .' valign="top">
<td><input type="checkbox" name="enable_' . $entry['id'] . '" value="1" ' . $inarray . ' /></td>
 <td><strong>'. $entry['id'] .'</strong></td>
 <td>'. $entry['name'] .'</td>
 <td>'. (isset($entry['label']) ? $entry['label'] : '') .'</td>
 <td>'. (isset($entry['caption']) ? $entry['caption'] : '') .'</td>
 <td align="center">'. strtoupper($entry['type']) .'</td>
 <td align="center">'. $req .'</td>
 <td align="center">'. $readonly .'</td>
 <td align="center">'. $profile_only .'</td>
 <td align="center">'. $no_profile .'</td>
 <td align="center">'. $page .'</td>
 <td align="center">'. $links .'</td>
 </tr>
 ';
	}
	
	foreach ($ordering as $order) {
		echo $rows[$order];
		unset($rows[$order]);
	}
	foreach ($rows as $row) {
		echo $row;
	}

} else {
	echo '<tr><td colspan="4" align="center"><strong>' . __('There are no custom fields set','ym') . '</strong></td></tr>';
}
echo '
</table>
';
echo '<p>' . __('To reorder fields drag and drop them to their new location', 'ym') . '</p>';
?>
<input type="hidden" name="order" id="order" class="order" value="" />
<p style="text-align: right;" class="submit">
<input type="submit" name="sort" value="Update" class="button-primary" />
</p>
</form>
<script type="text/javascript">
jQuery('#sorttable').sortable({
	items:			'.sorttablesort',
	placeholder:	'ui-state-highlight',
	start:			function(event, ui) {
		jQuery('.ui-state-highlight').html('<td colspan="12"> </td>');
	},
});
jQuery('#sorttable').disableSelection();

function ym_process_custom_field_sort() {
	var order = jQuery('#sorttable').sortable('serialize');
	document.getElementById('order').value = order;
	return;
}
</script>
<style type="text/css">
	tr {
		border: 1px solid #9F9F9F;
		cursor: move;
	}
	tr.ui-state-highlight {
		border: 1px dashed #000000;
		margin: 5px;
	}
	tr.ui-state-highlight td {
		height: 20px;
	}
</style>
 
 <?php

echo ym_end_box();
}
 
 ?>
 
</div>
<?php
if ((isset($new_msg)) && (! empty($new_msg))) {
?>
<div id="message" class="updated fade">
 <p><strong><?php echo $new_msg; ?></strong></p>
</div>
<?php
}

if (isset($new_error) && !empty($new_error)) {
?>
<div id="message" class="error">
 <p><strong><?php echo $new_error; ?></strong></p>
</div>
<?php
}

// Edit Form
if (isset($_GET['mode']) && $_GET['mode'] == 'edit') {
	$id = $_GET['id'];
	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;

	$field = array();
	foreach ($entries as $entry) {
		if ($entry['id'] == $id) {
			$field = $entry;
			break;
		}
	}

	$disabled = ($field['name'] == 'birthdate' || $field['name'] == 'country') ? 'disabled="disabled"' : '';
	$ro = ($field['name'] == 'birthdate' || $field['name'] == 'country' ) ? 'readonly="readonly"' : '';
	$disabled = (isset($field['builtin']) && $field['builtin']) ? 'disabled="disabled"' : '';
	$req_disabled = $disabled;
	$ro = (isset($field['builtin']) && $field['builtin']) ? 'readonly="readonly"' : '';

	// build in corrects
	$corrects_ignores = array(
		'first_name',
		'last_name',
		'ym_password',
		'birthdate',
		'coupon',
		'country',
		'user_url',
		'user_description',
	);
	if (isset($field['builtin']) && $field['builtin'] && in_array($field['name'], $corrects_ignores)) {
		$req_disabled = FALSE;
	}

echo '<div class="wrap" id="poststuff">';
echo '<div id="edit_form">';
echo ym_start_box(__('Edit Custom Field', 'ym'));
?>

 <form action="" method="post">

 <p><label><?php _e('Name:','ym') ?><br />
<?php
if (isset($field['builtin']) && $field['builtin']) {
	echo '<input type="hidden" class="ym_long_input" name="name" value="' . $field['name'] . '" ' . $ro . ' />' . $field['name'];
	echo '<input type="hidden" name="type" value="' . $field['type'] . '" />';
} else {
 	echo '<input type="text" class="ym_long_input" name="name" value="' . $field['name'] . '" ' . $ro . ' />';
 	echo '<br />';
 	echo __('Name can only contain a-z, 0-9, and _ characters and is limited to 10 Characters in length, anything not matching this rule will not be synced with MailChimp', 'ym');
}
?>
</label></p>

 <p><label><?php _e('Label:','ym') ?><br />
 <input type="text" class="ym_long_input" name="label" value="<?php echo htmlentities(stripslashes($field['label'])); ?>" /></label></p>

 <p><label><?php _e('Caption/Hint Text:','ym') ?><br />
 <input type="text" class="ym_long_input" name="caption" value="<?php echo htmlentities(stripslashes(@$field['caption'])); ?>" /></label></p>

 <p><label><?php _e('Allowed Values:','ym') ?><br />
 <input type="text" name="available_values" class="ym_long_input" value="<?php echo $field['available_values']; ?>" <?php echo $ro; ?> /></label>
<br />
<?php _e('(use pattern: value1;value2;value3 or Leave blank for any, if using a MultiSelect or Select(Drop down Box), you can use key1:value1;key2:value2;key3:value3)', 'ym'); ?></p>

 <p><label><?php _e('Input Type:','ym') ?><br />

<select name="type" <?php echo $disabled; ?>>
<?php

foreach ($ym_custom_field_types as $type => $data) {
	echo '<option value="' . $type . '" ';
	if ($field['type'] == $type) {
		echo 'selected="selected"';
	}
	echo '>' . $data['label'] . '</option>';
}


	$additional_types = array();
	$additional_types = apply_filters('ym_custom_fields_additional_types', $additional_types);
	
	foreach ($additional_types as $additional_type_name=>$additional_type_label) {
		echo '<option value="' . $additional_type_name . '" ' . ($field['type'] == $additional_type_name ? 'selected="selected"':'') . '>' . __($additional_type_label,'ym') . '</option>';
	}
 ?>
 
 </select></label></p>
 
  <p><label><?php _e('Show on Page:','ym') ?><br />
<?php
echo __('If you select a value other than 1, multiple registration pages will be used', 'ym');
?>

 <select name="page" <?php echo $disabled; ?>>
 <?php
 
$field['page'] = isset($field['page']) ? $field['page'] : 1;

 for ($i = 1; $i <= 10; $i++) {
	echo '<option value="' . $i . '" ' . ($field['page'] == $i ? 'selected="selected"':'') . '>' . $i . '</option>';
 }
 ?>
 </select></label></p>
 
 <p><label><?php _e('Default Value:','ym') ?><br />
 <textarea name="value" rows="2" cols="40" <?php echo $disabled; ?>><?php echo $field['value']; ?></textarea></label>
 <div style="font-size: 10px; color: gray;"><?php _e('You can use the following dynamic strings to autofill the value of any field. get:test, post:test, request:test, cookie:test, session:test where test is the name of the variable you wish to use and the first part being the array to call it from.', 'ym'); ?></div>
 </p>

<p><?php __('Field Properties:', 'ym'); ?></p>
 <p><label><input <?php echo $disabled; ?> type="checkbox" class="checkbox" name="readonly" value="1" <?php echo ($field['readonly'] == true) ? 'checked="checked"' : ''; ?> /> <?php _e('Readonly','ym') ?></label></p>
 <p><label><input <?php echo $req_disabled; ?> type="checkbox" class="checkbox" name="required" value="1" <?php echo ($field['required'] == true) ? 'checked="checked"' : ''; ?> <?php echo ($field['readonly'] === true) ? 'checked="checked"' : ''; ?> /> <?php _e('Required Field','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="profile_only" value="1" <?php echo ($field['profile_only'] == true) ? 'checked="checked"' : ''; ?> /> <?php _e('Show on profile only (hide from registration)','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="no_profile" value="1" <?php echo ($field['no_profile'] == true) ? 'checked="checked"' : ''; ?> /> <?php _e('Hide from profile (Registration only)','ym') ?></label></p>

 <p class="submit">
 <input type="hidden" name="id" value="<?php echo $id; ?>" />
 <input type="submit" name="update" class="button-primary" value="<?php _e('Update Custom Field','ym') ?>" />
 </p>

 </form>

<form action="<?php echo YM_ADMIN_URL . '&ym_page=ym-members-customfields'; ?>" method="post">
	<p class="submit" style="float: right;" >
		<input type="submit" class="button-secondary" value="<?php _e('Back to Custom Fields','ym') ?>" />
	</p>
</form>
 
<?php
	
echo ym_end_box();

?>
</div>
</div>
<?php
} else {
	// New Form
?>
<div class="wrap" id="poststuff">

<?php
	
ym_box_top(__('Add Custom Field', 'ym'), TRUE);

?>

 <form action="" method="post">

 <p><label><?php _e('Name:','ym') ?><br />
 <input type="text" name="name" style="width: 250px;"/></label></p>
<?php
 	echo __('Name can only contain a-z, 0-9, and _ characters and is limited to 10 Characters in length, anything not matching this rule will not be syncing with MailChimp', 'ym');
 ?>
 <p><label><?php _e('Label:','ym') ?><br />
 <input type="text" name="label" style="width: 250px;"/></label></p>
 
 <p><label><?php _e('Caption/Hint Text:','ym') ?><br />
 <input type="text" name="caption" style="width: 250px;"/></label></p>

 <p><label><?php _e('Allowed Values:','ym') ?><br />
 <input type="text" style="width: 250px;" name="available_values" /></label>
<br />
 <div style="font-size: 10px; color: gray;"><?php _e('(use pattern: value1;value2;value3 or Leave blank for any, if using a MultiSelect or Select(Drop down Box), you can use key1:value1;key2:value2;key3:value3). Alternatively you can use Regular Expression.', 'ym'); ?></p>
</div></p>

 <p><label><?php _e('Input Type:','ym') ?><br />
<select name="type">
<?php

foreach ($ym_custom_field_types as $type => $data) {
	echo '<option value="' . $type . '">' . $data['label'] . '</option>';
}

	$additional_types = array();
	$additional_types = apply_filters('ym_custom_fields_additional_types', $additional_types);
	
	foreach ($additional_types as $additional_type_name=>$additional_type_label) {
		echo '<option value="' . $additional_type_name . '" >' . __($additional_type_label,'ym') . '</option>';
	}
 ?>
 
 </select>
 </label></p>
 
  <p><label><?php _e('Show on Page:','ym') ?><br />
<?php
echo __('If you select a value other than 1, multiple registration pages will be used', 'ym');
?>
 <select name="page">
 <?php
 
 for ($i = 1; $i <= 10; $i++) {
	echo '<option value="' . $i . '" ' . ($i == 1 ? 'selected="selected"':'') . '>' . $i . '</option>';
 }
 ?>
 </select></label></p>

 <p><label><?php _e('Default Value:','ym') ?><br />
 <textarea name="value" rows="2" cols="40"></textarea></label>
 <div style="font-size: 10px; color: gray;">You can use the following dynamic strings to autofill the value of any field. get:test, post:test, request:test, cookie:test, session:test where test is the name of the variable you wish to use and the first part being the array to call it from.</div>
 </p>

<p><?php __('Field Properties:', 'ym'); ?></p>
 <p><label><input type="checkbox" class="checkbox" name="readonly" value="1" /> <?php _e('Readonly','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="required" value="1" /> <?php _e('Required Field','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="profile_only" value="1" /> <?php _e('Show on profile only (hide on registration)','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="no_profile" value="1" /> <?php _e('Hide from profile (Registration only)','ym') ?></label></p>
 <p><label><input type="checkbox" class="checkbox" name="enable" value="1" /> <?php _e('Enable','ym') ?></label></p>

 <p class="submit">
 <input type="submit" name="new" value="<?php _e('Create Custom Field','ym') ?>" style="width:auto;" />
 </p>
 </form>
 
<?php
	
echo ym_end_box();

?>
 
</div>
<?php
}

function validate_custom_field_label($label) {
	if (preg_match("/^[a-zA-Z0-9_]+$/", $label)) {
		return TRUE;
	}
	return FALSE;
}
function validate_custom_field_label_length($label) {
	if (strlen($label) <= 10) {
		return TRUE;
	}
	return FALSE;
}
