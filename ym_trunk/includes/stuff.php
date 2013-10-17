<?php

function ym_register($user_id) {
	global $wpdb;

	if (!isset($_SESSION['error_on_page'])) {
		get_currentuserinfo();

		$ym_user = new YourMember_User($user_id);
		$ym_user->status = YM_STATUS_NULL;
		$ym_user->save();

		if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') === false) {
		   	// check if subscription option is in the registration form
			$subs_option = false;
			$user_pass = false;
						
			// save the custom fields if there are any
			$fld_obj = get_option('ym_custom_fields');
			$entries = $fld_obj->entries;
			$order = $fld_obj->order;
						
			if (!empty($order)) {
				if (strpos($order, ';') !== false) {
					$orders = explode(';', $order);
				} else {
					$orders = array($order);
				}

				$data = array();
				
				foreach ($orders as $order) {
					foreach ($entries as $entry) {
						if ($order == $entry['id']) {
							if ($entry['name'] == 'subscription_options') {
								$subs_option = true;
							} else if ($entry['name'] == 'subscription_introduction' || $entry['name'] == 'terms_and_conditions') {
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
								global $ym_upload_root;
								if ($ym_upload_root) {
									$dir = trailingslashit(trailingslashit($ym_upload_root) . 'ym_custom_field_' . $entry['name']);
									if (!is_dir($dir)) {
										mkdir($dir);
									}
									if (is_dir($dir)) {
										// all good
										$name = 'ym_field-' . $entry['id'];
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
								if (!$ok) {
									echo '<div id="message" class="error"><p>' . __('An Error Occured whilst Uploading', 'ym') . '</p></div>';
								}
							} else {
								$field_name = 'ym_field-'. $entry['id'];
															
								if (in_array($entry['name'], array('first_name', 'last_name'))) {
									update_user_meta($user_id, $entry['name'], $_POST[$field_name]);
								}

								$data[$entry['id']] = ym_post($field_name, '');
							}
						}
					}
				}

				update_user_option($user_id, 'ym_custom_fields', $data, true);
			}

			if (!$user_pass = ym_post('ym_password')) {
				$user_pass = substr(md5(uniqid(microtime())), 0, 7);
			}
						
			$user_pass_md5 = md5($user_pass);

			$wpdb->query("UPDATE $wpdb->users SET user_pass = '$user_pass_md5' WHERE ID = '$user_id'");

			wp_new_user_notification($user_id, $user_pass);
						
			// redirect to ym_subscribe
			$userdata = get_userdata($user_id);						
			$redirect = add_query_arg(array('username'=>$userdata->user_login, 'ym_subscribe'=>1), get_option('siteurl') );				
				
			if (ym_post('ym_autologin')) {
				$redirect = add_query_arg(array('ym_autologin'=>1), $redirect);
			}
						
			$redirector = ym_post('ym_redirector', ym_post('redirect_to'));
			if ($redirector) {
				$redirect = add_query_arg(array('redirector'=>$redirector), $redirect);
			}
						
			$another_page_needed = ym_request('another_page_needed');
			if ($page = ym_request('ym_page', 1)) {
				$redirect = add_query_arg(array('ym_page'=>$page), $redirect);
				if ($another_page_needed) {
					$redirect = add_query_arg(array('another_page_needed'=>$another_page_needed), $redirect);
				}
			}
						
			if ($subs_option) {
				$redirect = add_query_arg(array('pack_id'=>$_POST['ym_subscription']), $redirect);
			}

			if (!headers_sent()) {
				header('location: ' . $redirect);
			} else {
				echo '<script>document.location="' . $redirect . '";</script>';
			}

			exit;
		} else {
			return $user_id;
		}
	}
}
/**
end_of ym_register()
*/

//function argument variable names kept for consistency...
//another_page_needed = page to show
//page = page coming from
function ym_get_additional_registration_form_page($another_page_needed, $page=false) {
		$html = '';
		
		if (!$page) {
			$page = ym_request('ym_page');
		}
		
		if ($page > 1) {
			$wp_error = new WP_Error();
			ym_register_post(ym_request('username'), '', $wp_error, $page); //error checking
			
			if ($wp_error->get_error_code()) {
				$errors = '';
				$messages = '';
				foreach ( $wp_error->get_error_codes() as $code ) {
					$severity = $wp_error->get_error_data($code);
					foreach ( $wp_error->get_error_messages($code) as $error ) {
						if ( 'message' == $severity ) {
							$messages .= '	' . $error . "<br />\n";
						} else {
							$errors .= '	' . $error . "<br />\n";
						}
					}
				}
				if ( !empty($errors) ) {
					$html .= '<div id="login_error">' . apply_filters('login_errors', $errors) . "</div>\n";
				}
				if ( !empty($messages) ) {
					$html .= '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
				}
							
			$another_page_needed = $page;
			$page--;
		}
	}
	
	$action = trailingslashit(get_option('siteurl')) . '?ym_subscribe=1&ym_page=' . $page . '&username=' . ym_get('username') . (ym_get('subs') ? '&subs=' . ym_get('subs'):'') . (ym_get('pack_id') ? '&pack_id=' . ym_get('pack_id'):'');
	
	$html .= '<form action="' . $action . '" method="post" enctype="multipart/form-data" name="registerform" id="registerform">
		<div style="clear: both;">';
		
	$html .= ym_register_form(true, $another_page_needed);
	
	$html .= '</div>
		<div class="ym_clear">&nbsp;</div>
		<p class="submit">';
		
	$previous_page = ym_get_previous_custom_field_page($another_page_needed);
	
	if ($previous_page > 1) {
		$html .= '<input class="button-primary" type="button" value="' . __('&laquo Previous', 'ym') . '" onclick="document.location=\'' . $action . '&another_page_needed=' . $previous_page . '\';" />';
	}
	
	$html .= '	<input class="button-primary" type="submit" name="submit" value="Next &raquo;" />
		</p>';
				
	$html .= '</form>';
		
		return $html;
}

// This adds custom fields into the registration form
function ym_register_form($return = false, $page=1, $pack_id=false, $hide_custom_fields=false, $hide_further_pages=false, $autologin=false) {
	global $duration_str, $ym_sys, $ym_res;
	
	$html = '';
	$fld_obj = get_option('ym_custom_fields');
	$hide = $ym_sys->hide_custom_fields;
	$user_id = ym_get_user_id();
		
	$hide_custom_fields = explode(',', $hide_custom_fields);
	if (!is_array($hide_custom_fields)) {
		$hide_custom_fields = array($hide_custom_fields);
	}

	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	if (empty($order)) {
		return;
	}

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}
		
	$html .= '<div style="clear:both; height: 1px;">&nbsp;</div>';
							   
	if ($redirect_to = ym_get('ym_redirector')) {
		$html .= '<input type="hidden" name="ym_redirector" value="' . urlencode($redirect_to) . '" />';
	}
	if ($autologin) {   
		$html .= '<input type="hidden" name="ym_autologin" value="1" />';
	}
		
	$another_page = false;
	$lowest_page = ym_get_last_custom_field_page()+1; //must be higher than the highest page
		
	$values = array();
	if ($username = ym_get('username')) {
		$values = ym_get_custom_fields_by_username($username);
	}
 	
	foreach ($orders as $order) {
		foreach ($entries as $entry) {
			if ($order == $entry['id']) {
				if (in_array($entry['id'], $hide_custom_fields)) {
					continue;
				}
							
				$entry['page'] = (!isset($entry['page']) ? 1:$entry['page']);
				if ($page == $entry['page']) {
					if (isset($_POST['hide_ym_field-'. $entry['id']])) {
						$entry['type'] = 'hidden';
						//will hide the field if the appropriate post data is present.
						//This is intended to go with hard coded signups where the register page will act as stage 2
					}

					$value = false;
					$row = '';
					$hide_label = false;

					if (isset($values[$entry['id']])) {
						if (trim($values[$entry['id']])) {
							$value = trim($values[$entry['id']]);
						}
					} else {
						$value = ym_post('ym_field-' . $entry['id']);
					}
								
					if ($value) {
						$entry['value'] = $value;
					}
								
					if ($value = $entry['value']) {
						if (strpos($value, ':') !== false) {
							$array = explode(':', $value);

							if (count($array)) {
								switch($array[0]) {
									case 'cookie':
										$entry['value'] = ym_cookie($array[1], '');
										break;
									case 'session':
										$entry['value'] = ym_session($array[1], '');
										break;
									case 'get':
										$entry['value'] = ym_get($array[1], '');
										break;
									case 'post':
										$entry['value'] = ym_post($array[1], '');
										break;
									case 'request':
									case 'qs':
										$entry['value'] = ym_request($array[1], '');
										break;
									default:
										$entry['value'] = '';
										break;
								}
							}
						}
					}

					if (($entry['name'] == 'terms_and_conditions') && (!empty($ym_res->tos))) {
						$row .= '<p>
								<textarea name="tos" cols="29" rows="5" readonly="readonly">' . $ym_res->tos . '</textarea>';
						$row .= '</p>';
						$row .= '<p>
								<label class="ym_label" for="ym_tos">
									<div><input type="checkbox" class="checkbox" name="ym_tos" id="ym_tos" value="1" />
									' . __('I agree to the Terms and Conditions.','ym') . '</div>
								</label>
							</p>' . "\n";
					} else if (($entry['name'] == 'subscription_introduction') && (!empty($ym_res->subs_intro))) {
						$row .= '<div class="ym_subs_intro">'. $ym_res->subs_intro .'</div>';
					} else if ($entry['name'] == 'subscription_options') {
						if (ym_request('ym_subscription')) {
							// pre selected!
							// could be from a ym_register and the reg is hidden so showing the selector here is bad
							$row .= '<input type="hidden" name="ym_subscription" value="' . ym_request('ym_subscription') . '" />';
							$hide_label = TRUE;
						} else {
							global $ym_packs;
							$packs = $ym_packs->packs;
							
							$active_modules = get_option('ym_modules');

							if (empty($active_modules)) {
								$row .= '<p>' . __('There are no payment gateways active. Please contact the administrator.','ym') . '</p>';
							} else {
								// RENDER
								$packs_shown = 0;

								if ($existing_data = ym_request('ym_subscription')) {
									$default = $existing_data;
								} else {
									$default = ym_get_default_pack();
								}

								$did_checked = FALSE;

								foreach ($packs as $pack) {
									if (!$pack['hide_subscription']) {
										$row .= '<div class="ym_register_form_subs_row">
													<div class="ym_reg_form_pack_radio">
														<input type="radio" ';
										if ($pack['id'] == $default && !$did_checked) {
											$row .= 'checked="checked"';
											$did_checked = TRUE;
										}
										$packs_shown++;
										$row .= ' class="checkbox" id="ym_subscription_' . $pack['id'] . '" name="ym_subscription" value="'. $pack['id'] .'" />
													</div>
													<label for="ym_subscription_' . $pack['id'] . '" class="ym_subs_opt_label ym_reg_form_pack_name">' . ym_get_pack_label($pack['id']) . '</label>
												</div>';
									}
								}

								if (!$packs_shown) {
									$hide_label = true;
								} else {
									if ($entry['caption']) {
										$row = '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>' . $row;
									}
								}
								// END RENDER
							}
						}
					} else if ($entry['name'] == 'birthdate' && !$hide) {
						$birthdate_fields = ym_birthdate_fields('ym_birthdate');
						$row .= '<p>'. $birthdate_fields .'</p>';

					} else if ($entry['name'] == 'country' && !$hide) {
						$countries_sel = ym_countries_list('ym_country');
						$row .= '<p>'. $countries_sel .'</p>';

					} else if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide) {
						$ro = ($entry['readonly'] ? 'readonly="readonly"':'');

						if ($entry['type'] == 'text') {
							$fld = '<input type="text" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
											} else if ($entry['type'] == 'hidden') {
												$fld = '<input type="hidden" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" />';
												$hide_label = true;
											} else if ($entry['type'] == 'yesno') {
							$fld = '<select class="ym_reg_select" name="ym_field-'. $entry['id'] .'" '. $ro .'>';

							$options = array('Yes', 'No');

							foreach ($options as $option) {
								$fld .= '<option value="' . $option . '" ' . (trim($option) == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
							}

							$fld .= '</select>';
											} else if ($entry['type'] == 'password') {
												// TODO: seriosuly?
							$fld = '<input type="password" name="ym_password" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
						} else if ($entry['type'] == 'html') {
												$fld = '<div class="ym_reg_html">' . $entry['value'] . '</div>';
						} else if ($entry['type'] == 'textarea') {
							$fld = '<textarea class="ym_reg_textarea" name="ym_field-'. $entry['id'] .'" cols="29" rows="5" '. $ro .'>' . $entry['value'] . '</textarea>';
						} else if ($entry['type'] == 'select') {
							$fld = '<select class="ym_reg_select" name="ym_field-'. $entry['id'] .'" '. $ro .'>';

							$options = explode(';', $entry['available_values']);

							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $val . '</option>';
								} else {
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
								}
							}

							$fld .= '</select>';
						} else if ($entry['type'] == 'multiselect') {
							$fld = '<select class="ym_reg_multiselect" name="ym_field-' . $entry['id'] . '[]" ' . $ro . ' multiple="multiple">';
							
							$options = explode(';', $entry['available_values']);

							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $val . '</option>';
								} else {
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
								}
							}

							$fld .= '</select>';
						} else if ($entry['type'] == 'file') {
							$fld = '<input type="file" name="ym_field-'. $entry['id'] .'" />';
							if ($entry['available_values'] == 'image') {
								$fld .= $entry['value'];
							}
						} else {
							if (!$fld = apply_filters('ym_generate_custom_field_type_' . $entry['type'], '', 'ym_field-'. $entry['id'], $entry, $value)) {
								$fld = '<input type="text" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
							}
						}
						
						if ($entry['required']) {
							$fld .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_required">' . $ym_sys->required_custom_field_symbol . '</div>';
						}
						if ($entry['caption']) {
							$fld .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>';
						}
											
						$row .= '<p>' . $fld . '</p>';
					}


					////Adding of the row
					if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide && !$hide_label) {
						$html .= '<div class="ym_register_form_row" id="' . str_replace(' ', '_', $entry['name']) . '_row">';
						$label = $entry['label'];
						$html .= '<label class="ym_label">'. $label .'</label>';
					}
									
					$html .= $row;
									
					if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide && !$hide_label) {
						$html .= '<div class="ym_clear">&nbsp;</div>';
						$html .= '</div>';
					}
					////End adding of the row
								
				}
								
				if (!$hide_further_pages) {
					if ($entry['page'] > $page) {
						if ($entry['page'] < $lowest_page) {
							$lowest_page = $entry['page'];
						}
									
						$another_page = true;
					}
				}
			}
		}
	}
		
		$html .= '<input type="hidden" name="ym_page" value="' . $page . '" />'; //so that the update function knows which pages to validate
		
		if ($another_page) {
			$html .= '<input type="hidden" name="another_page_needed" value="' . $lowest_page . '" />'; //so that the rendering function knows to add another page before sending off to the gateway
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}		
}

// Check required custom fields
function ym_register_post($user_login, $user_email, $errors, $page=1) {
	global $ym_res;

	if (isset($_SESSION)) {
		unset($_SESSION['error_on_page']);
	}

	$fld_obj = get_option('ym_custom_fields');

	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	if (empty($order)) {
		return;
	}

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}
		
		//$page = ym_request('ym_page', 1);
		
		$values = array();
		if ($username = ym_get('username')) {
			$values = ym_get_custom_fields_by_username($username);
		}		

	foreach ($orders as $order) {
		foreach ($entries as $entry) {
						$entry['page'] = (isset($entry['page']) ? $entry['page']:1);
						if ($entry['page'] == $page) {
							
							if ($order == $entry['id']) {
									if (($entry['name'] == 'terms_and_conditions') && (!empty($ym_res->tos))) {
											if (!isset($_POST['ym_tos'])) {
													$errors->add('ym_tos', '<strong>ERROR</strong>: ' . __('You must accept the Terms and Conditions.', 'ym'));
											}
									} else if ($entry['name'] == 'subscription_introduction') {
											continue;
									} else if ($entry['name'] == 'subscription_options') {
											if ( (!isset($_POST['ym_subscription'])) || (empty($_POST['ym_subscription'])) ) {
													$errors->add('ym_subscription', __('<strong>ERROR</strong>: You must select a Subscription Type.','ym'));
											}
									} else if ($entry['name'] == 'birthdate') {
											if ($entry['required'] && (empty($_POST['ym_birthdate_month']) || empty($_POST['ym_birthdate_day']) || empty($_POST['ym_birthdate_year']))) {
													$errors->add('ym_birthdate', __('<strong>ERROR</strong>: Birthdate is required','ym'));
											}
									} else if (strtolower($entry['name']) == __('password','ym') ) {
											if ($entry['required'] && empty($_POST['ym_password'])) {
													$errors->add('ym_password', __('<strong>ERROR</strong>: Password is required','ym'));
											}
									} else if ($entry['name'] == 'country') {
											if ($entry['required'] && empty($_POST['ym_country'])) {
													$errors->add('ym_country', __('<strong>ERROR</strong>: Country is required','ym'));
											}
									} else {
	
											$required = $entry['required'];
											$field_name = 'ym_field-'. $entry['id'];
											$name = $entry['name'];
											$value = ym_post($field_name);
											
											if (isset($entry['label']) && $entry['label']) {
												$label = $entry['label'];
											} else {
												$label = $entry['name'];
											}											
											
											if (isset($values[$entry['id']])) {
												if (trim($values[$entry['id']])) {
													$value = trim($values[$entry['id']]);
												}
											}
											
											if ($required && empty($value) && (ym_get('action') == 'register' && !$entry['profile_only'])) {
													$errors->add($field_name, sprintf(__('<strong>ERROR</strong>: %s is a required field','ym'),$label));
											} else if ($entry['available_values'] != '' && $entry['type'] != 'select' && $entry['type'] != 'multiselect') {
													if (strpos($entry['available_values'], ':')) {
														$values = array();
														$options = explode(';', $entry['available_values']);
														foreach ($options as $option) {
															list($v, $option) = explode(':', $option);
															$values[] = $v;
														}
													} else {
														$values = explode(';', $entry['available_values']);
													}
													die;
													if (!in_array($value, $values)) {
														$errors->add($field_name, sprintf(__('<strong>ERROR 2323</strong>: %s is not valid','ym'),$label));
													}
											}
											
											$errors = apply_filters('ym_custom_field_single_validation', $errors, $entry);
											
											$pack_id = ym_post('ym_subscription', FALSE);
											
											$errors = apply_filters('ym_post_additional', $errors, $user_login, $user_email, $pack_id);
									}
							}
						}
		}
	}
		
		$errors = apply_filters('ym_custom_field_group_validation', $errors, $_POST);

	if (count($errors->errors)) {
		$_SESSION['error_on_page'] = true;
	}

	return $errors;
}

// show buttons of modules available for upgrade/downgrade
function ym_upgrade_buttons($return=false, $pack_id=false, $user_id=false) {
	global $wpdb, $duration_str, $current_user, $ym_res, $ym_sys, $ym_packs;
	get_currentuserinfo();

	if (!$user_id) {
		$user_id = $current_user->ID;
	}

	if ($pack_id == 'all') {
		global $ym_packs;

		$html = '';
		foreach ($ym_packs->packs as $pack) {
			if (!$pack['hide_subscription']) {
				$html .= ym_upgrade_buttons(TRUE, $pack['id']);
			}
		}

		if ($return) {
			return $html;
		} else {
			echo $html;
			return;
		}
	}
		
	$html = '';
	$ym_home = get_option('siteurl');
		
	if (!$user_id) {
		$html = $ym_res->msg_header . __('Sorry but you must be logged in to upgrade your account', 'ym') . $ym_res->msg_footer;
	} else {
		$user_data = new YourMember_User($user_id);

		$account_type = ym_get_user_account_type(false, true);
		$packs = $ym_packs->packs;
		$trial_taken = get_user_meta($user_id, 'ym_trial_taken', TRUE);

		$active_modules = get_option('ym_modules');
		$modules_dir = YM_MODULES_DIR;
		$base = add_query_arg(array('ym_subscribe'=>1, 'ud'=>1, 'username'=>$current_user->user_login), $ym_home);

		if ((!isset($_POST['submit']) || !isset($_POST['subs_opt'])) && !$pack_id) {
			// TODO: Does this code even run?
			$html = '<p class="message register">' . __('Choose an Account Type', 'ym') . '</p>';
			$html .= '<form action="" method="post" class="ym"><div style="clear: both; overflow: auto; padding-bottom: 10px;">';
	
			// RENDER2
			$packs_shown = 0;

			if ($existing_data = ym_request('ym_subscription')) {
				$default = $existing_data;
			} else {
//				$default = ym_get_default_pack();
				$default = $user_data->pack_id;
			}

			$did_checked = FALSE;

			foreach ($packs as $pack) {
				if (!$pack['hide_subscription']) {
					$html .= '<div class="ym_register_form_subs_row">
								<div class="ym_reg_form_pack_radio">
									<input type="radio" ';
					if ($pack['id'] == $default && !$did_checked) {
						$html .= 'checked="checked"';
						$did_checked = TRUE;
					}
					$packs_shown++;
					$html .= ' class="checkbox" id="ym_subscription_' . $pack['id'] . '" name="ym_subscription" value="'. $pack['id'] .'" />
							</div>
							<label for="ym_subscription_' . $pack['id'] . '" class="ym_subs_opt_label ym_reg_form_pack_name">' . ym_get_pack_label($pack['id']) . '</label>
						</div>';
				}
			}

			if (!$packs_shown) {
				$hide_label = true;
			} else {
				if (isset($entry['caption']) && $entry['caption']) {
					$html .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>' . $row;
				}
			}

			// END RENDER2
	
			if ($packs_shown) {
//				$html .= '</div><input type="hidden" name="ref" value="'. md5($user_data->amount .'_'. $user_data->duration .'_'. $user_data->duration_type .'_'. $user_data->account_type) .'" />';
				$html .= '<p class="submit"><input type="submit" name="submit" value="' . __('Next &raquo;', 'ym') . '" /></p>';
			} else {
				$html .= '<p>' . __('Sorry there are currently no upgrade/downgrade options available to you.', 'ym') . '</p>';
			}
					
			$html .= '</form>';
		} else if (!ym_post('subs_opt') && $pack_id != ym_post('ym_subscription')) {
				global $ym_res;
				
				$html = '<form action="" method="post" class="ym_upgrade_shortcode">';
				$html .= '<input type="hidden" name="ym_subscription" value="' . $pack_id . '" />';
//				$html .= '<input type="hidden" name="ref" value="'. md5($user_data->amount .'_'. $user_data->duration .'_'. $user_data->duration_type .'_'. $user_data->account_type) .'" />';
				$html .= ym_get_pack_label($pack_id);
				$html .= '&nbsp;<a href="#nowhere" onClick="jQuery(this).parents(\'form\').submit();">Upgrade</a>';
				$html .= '</form>';
				
				return $html;
			} else {
				$pack = ym_get_pack_by_id($pack_id);
					$cost = $pack['cost'];
	
					if (!$pack_id) {
						$html .= '<br /><table width="100%" cellpadding="3" cellspacing="0" border="0" align="center" class="form-table">';
	
						if ($cost == 0 || $account_type == 'free') {
								$html .= '<tr><th>' . __('Create a free account: ','ym') . ucwords($account_type) . '</th></tr>';
						} else {
								$html .= '<tr><th>' . __('Select Payment Gateway','ym') . '</th></tr>';
								$html .= '<tr><th>' . ym_get_pack_label($pack['id']) . '</th></tr>';
						}
					}
	
					if (count($active_modules)) {
							$buttons_shown = array();
							foreach ($active_modules as $module) {
									if ($module == 'ym_free' && $pack['cost'] > 0) {
											continue;
									}
									
									require_once($modules_dir . $module .'.php');
									$obj = new $module();
	
									$string = $obj->getButton($pack['id']);
									if ($string) {
										$buttons_shown[] = $module;
										$html .= $string;
									}
									$string = false;
									$obj = null;
							}
							
							if (count($buttons_shown) == 1) {
									$module = array_pop($buttons_shown);
									
									$form_code = '<div style="display:none;">' . $html . '</div>';
											
									$js = 'document.forms["' . $module . '_form"].submit();';
									$html = '  <html>
													<head>
														<title>Redirecting...</title>
														<script type="text/javascript">
															function load() {
																' . $js . '
															}
														</script>
													</head>
													<body onload="load();">';
													
									$html .= '  <div style="color: #333333; font-size: 14px; margin: 30px 10px; font-family: tahoma; text-align: center; padding: 50px; border: 1px solid silver;">';
									
									$html .= '  <div>' . __('You are being redirected. If this page does not refresh in 5 seconds then click', 'ym') . ' <a onclick="document.forms[\'' . $module . '_form\'].submit();">here</a>.</div>
											   <div style="margin-top: 10px;"><img alt="" src="' . YM_IMAGES_DIR_URL . 'loading.gif" /></div>';
									$html .= '  </div>';
									
									$html .= $form_code;
												
									$html .= '	  </body>
												</html>';
									
									echo $html;
									die;
							}
					} else {
							$html .= __('There are no gateways available at this time.','ym');
					}
			}
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
}