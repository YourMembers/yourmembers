<?php

/*
* $Id: ym-custom_fields.include.php 2608 2013-02-22 10:47:57Z bcarlyon $
* $Revision: 2608 $
* $Date: 2013-02-22 10:47:57 +0000 (Fri, 22 Feb 2013) $
*/

$ym_custom_field_types = apply_filters('ym_custom_field_types',array(
	'text'			=> array('label' => __('Text Field (Single Line)', 'ym')),
	'textarea'		=> array('label' => __('Text Area (Multi-Line)', 'ym')),
	'html'			=> array('label' => __('Free Text/HTML', 'ym')),
	'yesno'			=> array('label' => __('Yes/No', 'ym')),
	'yesnocheckbox'	=> array('label' => __('Yes/No with a Checkbox', 'ym')),
	'select'		=> array('label' => __('Select (Drop down box)', 'ym')),
	'password'		=> array('label' => __('Password', 'ym')),
	'hidden'		=> array('label' => __('Hidden', 'ym')),
	'multiselect'	=> array('label' => __('Multi Select', 'ym')),
	'file'			=> array('label' => __('Upload Field', 'ym')),
	'callback'		=> array('label' => __('PHP Callback', 'ym')),
));

//function dump for functions that deal with custom fields

//ym.php #187
function ym_update_custom_fields_by_page() {
	$page = ym_request('page', 1);
	if (isset($_REQUEST['page'])) {
		return;
	}
	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;
	$user_id = ym_get_user_id();
	$cf = get_user_meta($user_id, 'ym_custom_fields', true);

	$skip_array = array(
		'terms_and_conditions',
		'subscription_introduction',
		'subscription_options'
	);
	
	if (!empty($order)) {
		if (strpos($order, ';') !== false) {
			$orders = explode(';', $order);
		} else {
			$orders = array($order);
		}
	
		$data = array();
		
		foreach ($orders as $order) {
			foreach ($entries as $entry) {
				if (!$entry['page']) {
					$entry['page'] = 1;
				}
				
				if ($page == $entry['page']) {
					if ($order == $entry['id']) {
						if (in_array($entry['name'], $skip_array)) {
							continue;
						} else if ($entry['name'] == 'birthdate') {
							if ((!empty($_POST['ym_birthdate_month'])) && (!empty($_POST['ym_birthdate_day'])) && (!empty($_POST['ym_birthdate_year']))) {
								$data[$entry['id']] = $_POST['ym_birthdate_month'] .'-'. $_POST['ym_birthdate_day'] .'-'. $_POST['ym_birthdate_year'];
							}
						} else if ($entry['name'] == 'country') {
							if (!empty($_POST['ym_country'])) {
								$data[$entry['id']] = $_POST['ym_country'];
							}
						} else if ($entry['type'] == 'file') {
							$ok = FALSE;
							$name = 'ym_field-' . $entry['id'];
							global $ym_upload_root;
							if ($ym_upload_root) {
								$dir = trailingslashit(trailingslashit($ym_upload_root) . 'ym_custom_field_' . $entry['name']);
								if (!is_dir($dir)) {
									mkdir($dir);
								}
								if (is_dir($dir)) {
									// all good
									if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
										$tmp = $_FILES[$name]['tmp_name'];
										$target = $dir . ym_get_user_id() . '_' . $_FILES[$name]['name'];
										if (move_uploaded_file($tmp, $target)) {
											global $ym_upload_url;
											$data[$entry['id']] = trailingslashit($ym_upload_url) . 'ym_custom_field_' . $entry['name'] . '/' . ym_get_user_id() . '_' . $_FILES[$name]['name'];
											$ok = TRUE;
										}
									}
								}
							}
							if (!$ok && isset($_FILES[$name])) {
								echo '<div id="message" class="error"><p>' . __('An Error Occured whilst Uploading (a)', 'ym') . '</p></div>';
							}
						} else if ($entry['type'] == 'callback') {
							$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_save';
							if (function_exists($callback)) {
								$data[$entry['id']] = $callback($entry);
							}
						} else {
							$field_name = 'ym_field-'. $entry['id'];

							// also update core profile
							if (in_array($entry['name'], array('first_name', 'last_name'))) {
								update_user_meta($user_id, $entry['name'], $_POST[$field_name]);
							}
							
							if (isset($_POST[$field_name])) {
								if ($entry['type'] == 'multiselect') {
									$_POST[$field_name] = implode(';', $_POST[$field_name]);
								}
								$data[$entry['id']] = $_POST[$field_name];
							}
						}
					}
				}
			}
		}
		
		if (is_array($cf)) {
			foreach ($cf as $key=>$value) {
				if (!isset($data[$key])) {
					$data[$key] = $value;
				}
			}
		}
	
		ym_update_user_custom_fields(ym_get_user_id(), $data);
	}
}

function ym_update_custom_fields() {
	$ID = ym_get_user_id();

	$fld_obj = get_option('ym_custom_fields');
	$return = false;
	
	if (strlen($fld_obj->order)) {
		if (strpos($fld_obj->order, ';') !== false) {
			$orders = explode(';', $fld_obj->order);
		} else {
			$orders = array($fld_obj->order);
		}

		$data = get_user_meta($ID, 'ym_custom_fields', true);
		$skip_array = array(
			'terms_and_conditions',
			'subscription_introduction',
			'subscription_options'
		);

		foreach ($orders as $order) {
			foreach ($fld_obj->entries as $entry) {
				if ($order == $entry['id']) {
					if (in_array($entry['name'], $skip_array)) {
						continue;
					} else if ($entry['name'] == 'birthdate') {
						if ((!empty($_POST['ym_birthdate_month'])) && (!empty($_POST['ym_birthdate_day'])) && (! empty($_POST['ym_birthdate_year']))) {
							$data[$entry['id']] = $_POST['ym_birthdate_month'] .'-'. $_POST['ym_birthdate_day'] .'-'. $_POST['ym_birthdate_year'];
						}
					} else if ($entry['name'] == 'country') {
						$data[$entry['id']] = ym_post('ym_country', (isset($data[$entry['id']]) && $data[$entry['id']] ? $data[$entry['id']] : ''));
					} else if ($entry['type'] == 'file') {
						$ok = FALSE;
						$name = $entry['name'];
						if (!isset($_FILES[$name])) {
							$name = 'ym_field-' . $entry['id'];
						}
						if (isset($_FILES[$name])) {
							global $ym_upload_root;
							if ($ym_upload_root) {
								$dir = trailingslashit(trailingslashit($ym_upload_root) . 'ym_custom_field_' . $entry['name']);
								if (!is_dir($dir)) {
									mkdir($dir);
								}
								if (is_dir($dir)) {
									// all good
									if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
										$tmp = $_FILES[$name]['tmp_name'];
										$target = $dir . ym_get_user_id() . '_' . $_FILES[$name]['name'];
										if (move_uploaded_file($tmp, $target)) {
											global $ym_upload_url;
											$data[$entry['id']] = trailingslashit($ym_upload_url) . 'ym_custom_field_' . $entry['name'] . '/' . ym_get_user_id() . '_' . $_FILES[$name]['name'];
											$ok = TRUE;
										}
									}
								}

							}
							if (!$ok && isset($_FILES[$name])) {
								echo '<div id="message" class="error"><p>' . __('An Error Occured whilst Uploading (b)', 'ym') . '</p></div>';
							}
						}
					} else if ($entry['type'] == 'callback') {
						$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_save';
						if (function_exists($callback)) {
							$data[$entry['id']] = $callback($entry);
						}
					} else {
						$data[$entry['id']] = ym_post($entry['name'], ym_post('ym_field-' . $entry['id'], isset($data[$entry['id']]) ? $data[$entry['id']] : ''));
					}
				}
			}
		}
		$return = ym_update_user_custom_fields($ID, $data);
	}
	return $return;
}

function ym_edit_custom_fields($user_ID=false, $submit_row=false, $return=false, $fields_to_show = FALSE, $fields_to_hide = FALSE) {
	global $ym_sys;
	if (!$user_ID) {
		$user_ID = ym_get_user_id();
	}
	
	if (is_object($user_ID)) {
		$user_ID = $user_ID->ID;
	}	

	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;
	$html = false;

	$userfields = get_user_meta($user_ID, 'ym_custom_fields', TRUE);

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}

	if ($fields_to_hide) {
		// TODO: YM 11.0.8 depricate
		if (strpos($fields_to_hide, '|')) {
			$fields_to_hide = explode('|', $fields_to_hide);
		} else {
			$fields_to_hide = explode(',', $fields_to_hide);
		}
		foreach ($fields_to_hide as $field) {
			if (!is_numeric($field)) {
				$id = ym_get_custom_field_by_name($field);
				$id = $id['id'];
			} else {
				$id = $field;
			}

			if (in_array($id, $orders)) {
				$key = array_search($id, $orders);
				unset($orders[$key]);
			}
		}
	}
	
	if ($fields_to_show) {
		// TODO: YM 11.0.8 depricate
		if (strpos($fields_to_show, '|')) {
			$fields_to_show = explode('|', $fields_to_show);
		} else {
			$fields_to_show = explode(',', $fields_to_show);
		}
		$show = array();
		foreach ($fields_to_show as $field) {
			if (!is_numeric($field)) {
				$id = ym_get_custom_field_by_name($field);
				$id = $id['id'];
			} else {
				$id = $field;
			}
			if (in_array($id, $orders)) {
				$show[] = $id;
			}
		}
		$orders = $show;
	}

	if (count($orders)) {

		$fields = array();
		$html = '';
		$to_ignore = array(
			'terms_and_conditions',
			'subscription_introduction',
			'subscription_options',
			'coupon'
		);


		foreach ($orders as $order) {
			foreach ($entries as $entry) {
				$field = '';
				if ($order == $entry['id']) {

					$value = false;
					if (isset($userfields[$entry['id']]) && $userfields[$entry['id']]) {
						$value = $userfields[$entry['id']];
					} else {
						$value = ym_post('ym_field-'. $entry['id']);
					}
					if (!$value && $entry['builtin']) {
						$value = get_user_meta($user_ID, $entry['name'], true);
						if (!$value) {
							$user = get_userdata($user_ID);
							$key = $entry['name'];
							if (isset($user->$key)) {
								$value = $user->$key;
							}
						}
					}

					if (in_array($entry['name'], $to_ignore)) {
						continue;
					} else if ($entry['name'] == 'birthdate') {
						if ($value) {
							$date = explode('-', $value);
							$field = ym_birthdate_fields('ym_birthdate', $date[0], $date[1], $date[2]);
						} else {
							$field = ym_birthdate_fields('ym_birthdate');
						}
					} else if ($entry['name'] == 'country') {
						$field = ym_countries_list('ym_country', $value);

					} else if (!$entry['no_profile']) {
						$ro = ($entry['readonly'] == true ? 'readonly="readonly"':false);
						if ($entry['type'] == 'text') {
							$field = '<input name="ym_field-'. $entry['id'] .'" value="'. $value .'" '. $ro .' class="input" style="width:250px;" />';
						} else if ($entry['type'] == 'hidden' && ym_superuser()) {
							// not sure the sginificacnce of ym_useruser here
							$field = '<input type="text" name="ym_field-'. $entry['id'] .'" value="'. $value .'" '. $ro .' class="input" style="width:250px;" />';
						} else if ($entry['type'] == 'password') {
							$field = '<input type="password" name="ym_field-'. $entry['id'] .'" class="input" style="width:250px;" />';
						} else if ($entry['type'] == 'textarea') {
							$field = '<textarea name="ym_field-'. $entry['id'] .'" cols="40" rows="5" '. $ro .'>'. $value .'</textarea>';
						} else if ($entry['type'] == 'select' || $entry['type'] == 'multiselect') {
							$field = '<select name="ym_field-'. $entry['id'];
							if ($entry['type'] == 'multiselect') {
								$field .= '[]" multiple="multiple';
								$field .= ' class="ym_cf_multiselect" ';
							} else {
								$field .= '" class="ym_cf_select" ';
							}
							$field .= '" '. $ro .'>';

							$options = explode(';', $entry['available_values']);
							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
								} else {
									$val = $option;
								}

								$thisvalue = $value;
								if (is_array($thisvalue)) {
									foreach ($thisvalue as $v) {
										if ($option == $v) {
											$thisvalue = $v;
											break;
										}
									}
								}

								$field .= '<option value="' . $option . '" ' . ($option == $thisvalue ? 'selected="selected"':'') . '>' . $val . '</option>';
							}

							$field .= '</select>';
						} else if ($entry['type'] == 'yesno') {
							$field = '<select class="ym_reg_select" name="ym_field-'. $entry['id'] .'" '. $ro .'>';

							$options = array('Yes', 'No');

							foreach ($options as $option) {
								$field .= '<option value="' . $option . '" ' . (trim($option) == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
							}
							$field .= '</select>';
						} else if ($entry['type'] == 'yesnocheckbox') {
							$field = '<input type="checkbox" class="ym_reg_checbox" name="ym_field-'. $entry['id'] .'" '. $ro .' value="1" ' . ($option ? 'checked="checked"' : '') . ' />';
						} else if ($entry['type'] == 'file') {
							$field = '<input type="file" name="ym_field-'. $entry['id'] .'" ' . $ro . ' />';
							if ($entry['available_values'] == 'image') {
								$field .= '<img src="' . $value . '" style="width: 100px;" />';
							}
						} else if ($entry['type'] == 'callback') {
							$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_editor';
							if (function_exists($callback)) {
								$field .= $callback($entry);
							}
						}
					}

					if ($field) {
						if (($entry['type'] == 'hidden' && ym_superuser()) || $entry['type'] != 'hidden') {
							// DITTO ym_superuser
							$fields[] = array(
							'label'=>$entry['label']
							, 'field'=>$field
							, 'caption'=>$entry['caption']
							, 'required'=>$entry['required']
							);
						}
					}
				}
			}
		}
// TODO: shortcode arg pass in what to display/what to hide
		if (count($fields)) {
			$html = '<table class=\'form-table\' style="width: 100%;">
						<col style="width:125px;"/>';

			foreach ($fields as $i=>$row) {
				$html .= '	<tr>
								<th style="text-align: left; vertical-align: top;">' . $row['label'];
								if ($row['caption']) {
									$html .= '<div class="ym_register_form_caption">' . $row['caption'] . '</div>';
								}
								$html .= '</th>
								<td style="text-align: left; vertical-align: top;">' . $row['field'];
								if ($row['required']) {
									$html .= '<div class="ym_required_text">' . $ym_sys->required_custom_field_symbol . '</div>';
								}
								$html .= '</td>
							</tr>';
			}

			if ($submit_row) {
				$html .= '	<tr>
								<td style="text-align: right;" colspan="2">
									<input name="update_ym_custom_fields_submit" type="hidden" value="1" />
									<input name="submit" type="submit" value="' . __('Update your profile', 'ym') . '" class="button"/>
								</td>
							</tr>';
			}

			$html .= '</table>';
		}
	}

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ym_edit_custom_field_standalone($args) {
	$show_fields = isset($args['show_custom_fields']) ? $args['show_custom_fields'] : FALSE;
	$hide_fields = isset($args['hide_custom_fields']) ? $args['hide_custom_fields'] : FALSE;

	$html = '';

	$html .= '	<div class="ym_custom_fields_standalone">';

	if (isset($_POST['update_ym_custom_fields_submit'])) {
		$r = ym_update_custom_fields();
		if ($r) {
			$html .= '<div class="ym_feedback updated fade" id="message">' . __('Your profile has been updated successfully', 'ym') . '</div>';
			// here
			if ($r == 'password') {
				$html .= '<div class="ym_feedback updated fade" id="message">' . __('You need to login again as your password has changed', 'ym') . '</div>';
				$html .= do_shortcode('[ym_login]');
				return $html;
			}
		}
	}

	$html .= '<form method="post" enctype="multipart/form-data">';
	$html .= ym_edit_custom_fields(false, true, true, $show_fields, $hide_fields);
	$html .= '</form></div>';

	return $html;
}

function ym_get_custom_field_array_by_username($username) {
	$user = ym_get_user_by_username($username);
	
	return ym_get_custom_field_array($user->ID);
}

function ym_get_custom_fields_by_username($username) {
	$user = ym_get_user_by_username($username);
	
	return ym_get_custom_fields($user->ID);
}

function ym_get_custom_fields($user_ID) {
	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	$skip_array = array(
		'terms_and_conditions',
		'subscription_introduction',
		'subscription_options'
	);

	$userfields = get_user_meta($user_ID, 'ym_custom_fields', TRUE);

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}

	foreach ($orders as $order) {
		foreach ($entries as $entry) {
			if ($order == $entry['id']) {
				if (in_array($entry['name'], $skip_array)) {
					continue;
				} else {
					$return[$entry['id']] = isset($userfields[$entry['id']]) ? $userfields[$entry['id']] : '';
				}
			}
		}
	}

	return $return;
}

function ym_get_custom_field_array($user_ID) {
	$return = array();

	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	$skip_array = array(
		'terms_and_conditions',
		'subscription_introduction',
		'subscription_options'
	);

	$userfields = get_user_meta($user_ID, 'ym_custom_fields', TRUE);

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}

	foreach ($orders as $order) {
		foreach ($entries as $entry) {
			if ($order == $entry['id']) {
				if (in_array($entry['name'], $skip_array)) {
					continue;
				} else {
					if (isset($userfields[$entry['id']])) {
						$return[strtolower(str_replace(' ','_',$entry['name']))] = $userfields[$entry['id']];
					} else {
						$return[strtolower(str_replace(' ','_',$entry['name']))] = '';
					}
				}
			}
		}
	}

	if (isset($return['birthdate']) && $return['birthdate'] != '') {
		$bday_array = explode('-', $return['birthdate']);
		$return['birthdate_unixtime'] = strtotime($bday_array[2] . '-' . $bday_array[0] . '-' . $bday_array[1]);
	}

	return $return;
}

function ym_update_custom_field_partial() {
	global $ym_res;

	$ID = ym_get_user_id();

	$fld_obj = get_option('ym_custom_fields');
	$old_data = get_user_meta('ym_custom_fields', $ID, TRUE);
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;
	$return = false;

	if (strlen($order)) {
		if (strpos($order, ';') !== false) {
			$orders = explode(';', $order);
		} else {
			$orders = array($order);
		}

		$data = array();

		$skip_array = array(
			'terms_and_conditions',
			'subscription_introduction',
			'subscription_options'
		);


		foreach ($orders as $order) {
			foreach ($entries as $entry) {
				if ($order == $entry['id']) {
					if (isset($old_data[$entry['id']])) {
						$old = $old_data[$entry['id']];

						if (in_array($entry['name'], $skip)) {
							continue;
						} else if ($entry['name'] == 'birthdate') {
							if ((!empty($_POST['ym_birthdate_month'])) && (!empty($_POST['ym_birthdate_day'])) && (! empty($_POST['ym_birthdate_year']))) {
								$data[$entry['id']] = $_POST['ym_birthdate_month'] .'-'. $_POST['ym_birthdate_day'] .'-'. $_POST['ym_birthdate_year'];
							}
						} else if ($entry['name'] == 'country') {
							$data[$entry['id']] = ym_post('ym_country', $old);
						} else if ($entry['type'] == 'file') {
							$ok = FALSE;
							$name = 'ym_field-' . $entry['id'];
							global $ym_upload_root;
							if ($ym_upload_root) {
								$dir = trailingslashit(trailingslashit($ym_upload_root) . 'ym_custom_field_' . $entry['name']);
								if (!is_dir($dir)) {
									mkdir($dir);
								}
								if (is_dir($dir)) {
									// all good
									if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
										$tmp = $_FILES[$name]['tmp_name'];
										$target = $dir . ym_get_user_id() . '_' . $_FILES[$name]['name'];
										if (move_uploaded_file($tmp, $target)) {
											global $ym_upload_url;
											$data[$entry['id']] = trailingslashit($ym_upload_url) . 'ym_custom_field_' . $entry['name'] . '/' . ym_get_user_id() . '_' . $_FILES[$name]['name'];
											$ok = TRUE;
										}
									}
								}

							}
							if (!$ok && isset($_FILES[$name])) {
								echo '<div id="message" class="error"><p>' . __('An Error Occured whilst Uploading (c)', 'ym') . '</p></div>';
							}
						} else if ($entry['type'] == 'callback') {
							$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_save';
							if (function_exists($callback)) {
								$data[$entry['id']] = $callback($entry);
							}
						} else {
							$data[$entry['id']] = ym_post($entry['name'], ym_post('ym_field-' . $entry['id'], $old));
						}
					}
				}
			}
		}

		$return = ym_update_user_custom_fields($ID, $data);
	}

	return $return;
}

function ym_get_last_custom_field_page() {
    $max_page = 1;
    
    if ($fields = get_option('ym_custom_fields')) {
        foreach ($fields as $i => $field) {
            $page = (isset($field['page']) ? $field['page']:1);
            if ($page >= $max_page) {
                $max_page = $page;
            }
        }
    }
    
    return $max_page;
}

function ym_get_previous_custom_field_page($this_page = 1) {
    $last_page = 1;
    
    if ($fld_obj = get_option('ym_custom_fields')) {
	$entries = $fld_obj->entries;
	$order = $fld_obj->order;
        
	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}
        
        foreach ($entries as $field) {
            if (in_array($field['id'], $orders)) {
    
                $page = (isset($field['page']) ? $field['page']:1);
                
                if ($page < $this_page) {
                    if ($page > $last_page) {
                        $last_page = $page;
                    }
                }
            }
        }
    }
    
    return $last_page;
}

global $ym_update_user_custom_fields_called;
$ym_update_user_custom_fields_called = false;
function ym_update_user_custom_fields($user_id, $data = array()) {
	global $ym_update_user_custom_fields_called;
	if ($ym_update_user_custom_fields_called) {
		return;
	}
	$ym_update_user_custom_fields_called = TRUE;
	// master sync
	// email first name last name password
	$core = array(
		'ID'	=> $user_id,
	);

	$return = 1;

	foreach ((array)$data as $id => $item) {
		$field = ym_get_custom_field_by_id($id);

		if ($item) {
			switch ($field['name']) {
				case 'user_email':
				case 'user_url':
					$core[$field['name']] = $item;
					break;

				case 'first_name':
				case 'last_name':
					update_user_meta($user_id, $field['name'], $item);
					break;

				case 'ym_password':
					// don't store the password
					unset($data[$field['name']]);
					if (!empty($item)) {
						wp_set_password($item, $user_id);
						$return = 'password';
					}
					break;
				
				case 'user_description':
					update_user_meta($user_id, 'description', $item);
					break;
			}
		}
	}

	if (count($core) > 1) {
		wp_update_user($core);
	}

	// explicity clean/stop save of ym_password
	$id = ym_get_custom_field_by_name('ym_password');
	$data[$id['id']] = '';

	update_user_meta($user_id, 'ym_custom_fields', $data);

	return $return;
}

/**
SPECIAL FORMS
*/

function ym_birthdate_fields($name, $selected_month = '', $selected_day = '', $selected_year = '') {
	global $wp_locale;
	$month_sel = (empty($selected_month)) ? 'selected="selected"':'';
	$month_opts = '<option value="" '. $month_sel .'>' . __('Month','ym') . '</option>';

	foreach ($wp_locale->month as $key=>$value) {
		$month_sel = ($selected_month == $key) ? 'selected="selected"':'';
		$month_opts .= '<option value="'. $key .'" '. $month_sel .'>'. $value .'</option>';
	}

	$day_sel = (empty($selected_day)) ? 'selected="selected"':'';
	$day_opts = '<option value="" '. $day_sel .'>' . __('Day','ym') . '</option>';

	for ($x = 1; $x <= 31; $x++) {
		$day_sel = ($selected_day == $x) ? 'selected="selected"':'';
		$day_opts .= '<option value="'. $x .'" '. $day_sel .'>'. $x .'</option>';
	}

	$year_sel = (empty($selected_year)) ? 'selected="selected"':'';
	$year_opts = '<option value="" '. $year_sel .'>' . __('Year','ym') . '</option>';

	for ($x = date("Y"); $x >= 1900; $x--) {
		$year_sel = ($selected_year == $x) ? 'selected="selected"':'';
		$year_opts .= '<option value="'. $x .'" '. $year_sel .'>'. $x .'</option>';
	}

	$month_select = '<select style="width:80px;" name="'. $name .'_month">'. $month_opts .'</select>';
	$day_select = '<select style="width:80px;" name="'. $name .'_day">'. $day_opts .'</select>';
	$year_select = '<select style="width:80px;" name="'. $name .'_year">'. $year_opts .'</select>';

	return $month_select.'&nbsp;'. $day_select .'&nbsp;'. $year_select;
}
function ym_countries_list($name, $selected=false, $multiple=false, $value='name') {
	$options = '';
	$sel = (empty($selected) || $selected <= 0) ? 'selected="selected"':'';
	$options = '<option value="" '. $sel .'>' . __('Select Country','ym') . '</option>';

	// sort the countries
	$countries = array(
'af' => 'Afghanistan',
'ax' => 'Aland Islands',
'al' => 'Albania',
'dz' => 'Algeria',
'as' => 'American Samoa',
'ad' => 'Andorra',
'ao' => 'Angola',
'ai' => 'Anguilla',
'aq' => 'Antarctica',
'ag' => 'Antigua and Barbuda',
'ar' => 'Argentina',
'am' => 'Armenia',
'aw' => 'Aruba',
'au' => 'Australia',
'at' => 'Austria',
'az' => 'Azerbaijan',
'bs' => 'Bahamas',
'bh' => 'Bahrain',
'bd' => 'Bangladesh',
'bb' => 'Barbados',
'by' => 'Belarus',
'be' => 'Belgium',
'bz' => 'Belize',
'bj' => 'Benin',
'bm' => 'Bermuda',
'bt' => 'Bhutan',
'bo' => 'Bolivia',
'ba' => 'Bosnia and Herzegovina',
'bw' => 'Botswana',
'bv' => 'Bouvet Island',
'br' => 'Brazil',
'io' => 'British Indian Ocean Territory',
'vg' => 'British Virgin Islands',
'bn' => 'Brunei',
'bg' => 'Bulgaria',
'bf' => 'Burkina Faso',
'bi' => 'Burundi',
'kh' => 'Cambodia',
'cm' => 'Cameroon',
'ca' => 'Canada',
'cv' => 'Cape Verde',
'ky' => 'Cayman Islands',
'cf' => 'Central African Republic',
'td' => 'Chad',
'cl' => 'Chile',
'cn' => 'China',
'cx' => 'Christmas Island',
'cc' => 'Cocos (Keeling) Islands',
'co' => 'Colombia',
'km' => 'Comoros',
'cg' => 'Congo (Brazzaville)',
'cd' => 'Congo (Kinshasa)',
'ck' => 'Cook Islands',
'cr' => 'Costa Rica',
'hr' => 'Croatia',
'cu' => 'Cuba',
'cy' => 'Cyprus',
'cz' => 'Czech Republic',
'dk' => 'Denmark',
'dj' => 'Djibouti',
'dm' => 'Dominica',
'do' => 'Dominican Republic',
'tl' => 'East Timor',
'ec' => 'Ecuador',
'eg' => 'Egypt',
'sv' => 'El Salvador',
'gq' => 'Equatorial Guinea',
'er' => 'Eritrea',
'ee' => 'Estonia',
'et' => 'Ethiopia',
'fk' => 'Falkland Islands',
'fo' => 'Faroe Islands',
'fj' => 'Fiji',
'fi' => 'Finland',
'fr' => 'France',
'gf' => 'French Guiana',
'pf' => 'French Polynesia',
'tf' => 'French Southern Territories',
'ga' => 'Gabon',
'gm' => 'Gambia',
'ge' => 'Georgia',
'de' => 'Germany',
'gh' => 'Ghana',
'gi' => 'Gibraltar',
'gr' => 'Greece',
'gl' => 'Greenland',
'gd' => 'Grenada',
'gp' => 'Guadeloupe',
'gu' => 'Guam',
'gt' => 'Guatemala',
'gg' => 'Guernsey',
'gn' => 'Guinea',
'gw' => 'Guinea-Bissau',
'gy' => 'Guyana',
'ht' => 'Haiti',
'hm' => 'Heard Island and McDonald Islands',
'hn' => 'Honduras',
'hk' => 'Hong Kong S.A.R., China',
'hu' => 'Hungary',
'is' => 'Iceland',
'in' => 'India',
'id' => 'Indonesia',
'ie' => 'Ireland',
'im' => 'Isle of Man',
'il' => 'Israel',
'it' => 'Italy',
'ci' => 'Ivory Coast',
'jm' => 'Jamaica',
'jp' => 'Japan',
'je' => 'Jersey',
'jo' => 'Jordan',
'kz' => 'Kazakhstan',
'ke' => 'Kenya',
'ki' => 'Kiribati',
'kw' => 'Kuwait',
'kg' => 'Kyrgyzstan',
'la' => 'Laos',
'lv' => 'Latvia',
'lb' => 'Lebanon',
'ls' => 'Lesotho',
'lr' => 'Liberia',
'ly' => 'Libya',
'li' => 'Liechtenstein',
'lt' => 'Lithuania',
'lu' => 'Luxembourg',
'mo' => 'Macao S.A.R., China',
'mk' => 'Macedonia',
'mg' => 'Madagascar',
'mw' => 'Malawi',
'my' => 'Malaysia',
'mv' => 'Maldives',
'ml' => 'Mali',
'mt' => 'Malta',
'mh' => 'Marshall Islands',
'mq' => 'Martinique',
'mr' => 'Mauritania',
'mu' => 'Mauritius',
'yt' => 'Mayotte',
'mx' => 'Mexico',
'fm' => 'Micronesia',
'md' => 'Moldova',
'mc' => 'Monaco',
'mn' => 'Mongolia',
'me' => 'Montenegro',
'ms' => 'Montserrat',
'ma' => 'Morocco',
'mz' => 'Mozambique',
'mm' => 'Myanmar',
'na' => 'Namibia',
'nr' => 'Nauru',
'np' => 'Nepal',
'nl' => 'Netherlands',
'an' => 'Netherlands Antilles',
'nc' => 'New Caledonia',
'nz' => 'New Zealand',
'ni' => 'Nicaragua',
'ne' => 'Niger',
'ng' => 'Nigeria',
'nu' => 'Niue',
'nf' => 'Norfolk Island',
'mp' => 'Northern Mariana Islands',
'no' => 'Norway',
'om' => 'Oman',
'pk' => 'Pakistan',
'pw' => 'Palau',
'ps' => 'Palestinian Territory',
'pa' => 'Panama',
'pg' => 'Papua New Guinea',
'py' => 'Paraguay',
'pe' => 'Peru',
'ph' => 'Philippines',
'pn' => 'Pitcairn',
'pl' => 'Poland',
'pt' => 'Portugal',
'pr' => 'Puerto Rico',
'qa' => 'Qatar',
're' => 'Reunion',
'ro' => 'Romania',
'ru' => 'Russia',
'rw' => 'Rwanda',
'sh' => 'Saint Helena',
'kn' => 'Saint Kitts and Nevis',
'lc' => 'Saint Lucia',
'pm' => 'Saint Pierre and Miquelon',
'vc' => 'Saint Vincent and the Grenadines',
'ws' => 'Samoa',
'sm' => 'San Marino',
'st' => 'Sao Tome and Principe',
'sa' => 'Saudi Arabia',
'sn' => 'Senegal',
'rs' => 'Serbia',
'cs' => 'Serbia And Montenegro',
'sc' => 'Seychelles',
'sl' => 'Sierra Leone',
'sg' => 'Singapore',
'sk' => 'Slovakia',
'si' => 'Slovenia',
'sb' => 'Solomon Islands',
'so' => 'Somalia',
'za' => 'South Africa',
'gs' => 'South Georgia and the South Sandwich Islands',
'kr' => 'South Korea',
'es' => 'Spain',
'lk' => 'Sri Lanka',
'sd' => 'Sudan',
'sr' => 'Suriname',
'sj' => 'Svalbard and Jan Mayen',
'sz' => 'Swaziland',
'se' => 'Sweden',
'ch' => 'Switzerland',
'tw' => 'Taiwan',
'tj' => 'Tajikistan',
'tz' => 'Tanzania',
'th' => 'Thailand',
'tg' => 'Togo',
'tk' => 'Tokelau',
'to' => 'Tonga',
'tt' => 'Trinidad and Tobago',
'tn' => 'Tunisia',
'tr' => 'Turkey',
'tm' => 'Turkmenistan',
'tc' => 'Turks and Caicos Islands',
'tv' => 'Tuvalu',
'vi' => 'U.S. Virgin Islands',
'ug' => 'Uganda',
'ua' => 'Ukraine',
'ae' => 'United Arab Emirates',
'uk' => 'United Kingdom',
'us' => 'United States',
'um' => 'United States Minor Outlying Islands',
'uy' => 'Uruguay',
'uz' => 'Uzbekistan',
'vu' => 'Vanuatu',
'va' => 'Vatican',
've' => 'Venezuela',
'vn' => 'Vietnam',
'wf' => 'Wallis and Futuna',
'eh' => 'Western Sahara',
'ye' => 'Yemen',
'zm' => 'Zambia'
	);
	//foreach ($results as $id=>$country) {
	foreach ($countries as $code=>$country) {
		$sel = ($selected == $code ? 'selected="selected"':'');
		$options .= '<option value="'. $code .'" '. $sel .'>'. $country .'</option>';
	}

	return '<select style="width: 250px;" ' . ($multiple ? ' multiple="1"':'') . ' name="'. $name .'">'. $options .'</select>';
}

//Validate custom field data
function ym_validate_custom_field_data($value=false,$values=array()){
	//Default sanity check
	if(!$value || !$values || !is_array($values)) return false;

	$return = false;
	if(in_array($value, $values)) $return = true;
	else{
		foreach($values as $item)
			{
				$item = stripslashes($item);
				$value = stripslashes($value);
				//Validate against Regular Expression
					if(@preg_match($item, $value)) 
					{
						$return = true;
						break;
					}
				
			}
	}
	//just to be on the safe side let's pass this through a filter
	return apply_filters('ym_custom_field_data', $return, $value, $values);
	
}

/**
End Special Forms
*/

function ym_get_custom_field_by_name($name){
	$name = strtolower($name);
	$fieldlist = get_option('ym_custom_fields');
	$entries = $fieldlist->entries;

	foreach ($entries as $field) {
		if (strtolower($field['name']) == strtolower($name)) {
			return $field;
		}
	}
	return FALSE;
}
function ym_get_custom_field_by_id($id) {
	$fieldlist = get_option('ym_custom_fields');
	$entries = $fieldlist->entries;

	foreach ($entries as $field){
		if ($field['id'] == $id) {
			return $field;
		}
	}
	return FALSE;
}

/**
new functions
*/
function ym_get_custom_field($value) {
	if (is_numeric($value)) {
		$key = 'id';
	} else {
		$key = 'name';
	}

	$fieldlist = get_option('ym_custom_fields');
	foreach ($fieldlist->entries as $field) {
		if ($field[$key] == $value) {
			return $field;
		}
	}
	return FALSE;
}
function ym_custom_value($id, $alt = '') {
	// get field
	$custom_field = ym_get_custom_field($id);

	global $ym_user;
	if (!isset($ym_user->ID)) {
		return $alt;
	}
	$user_fields = ym_get_custom_fields($ym_user->ID);

	foreach ($user_fields as $id => $data) {
		if ($custom_field['id'] == $id) {
			return $data;
		}
	}
	return $alt;
}

function ym_register_custom_field($name, $label = false, $enable = true, $type = 'hidden', $additional = false) {
	$name = strtolower($name);
	// validate html safe-ish
	if (!validate_custom_field_label($name)) {
		return false;
	}

	// label check
	if (!$label) {
		$label = $name;
	}
	$label = stripcslashes($label);

	// exists
	$fields = get_option('ym_custom_fields');
	foreach ($fields->entries as $entry) {
		$entry['label'] = isset($entry['label']) ? $entry['label'] : '';
		if ($entry['name'] == $name || $entry['label'] == $label) {
			return false;
		}
	}

	if (!$additional) {
		$additional = array();
	}
	$defaults = array(
		'caption'			=> false,
		'available_values'	=> '',
		'value'				=> '',
		'page'				=> 1,

		'required'			=> false,
		'readonly'			=> false,
		'profile_only'		=> false,
		'no_profile'		=> false,
	);
	// defaults
	foreach ($defaults as $default => $value) {
		if (!isset($additional[$default]) || empty($additional[$default])) {
			$additional[$default] = $value;
		} else {
			$additional[$default] = stripcslashes($additional[$default]);
		}
	}

	$id = $fields->next_id;
	$fields->next_id = $id + 1;

	$entry = array(
		'id'				=> $id,
		'name'				=> $name,
		'label'				=> $label,
		'caption'			=> $additional['caption'],
		'available_values'	=> $additional['available_values'],
		'type'				=> $type,
		'required'			=> $additional['required'],
		'value'				=> $additional['value'],
		'page'				=> $additional['page'],
		'readonly'			=> $additional['readonly'],
		'profile_only'		=> $additional['profile_only'],
		'no_profile'		=> $additional['no_profile'],
		'builtin'			=> FALSE
	);
	$fields->entries[] = $entry;

	if ($enable) {
		$fields->order .= ';' . $id;
	}

	update_option('ym_custom_fields', $fields);

	return true;
}
