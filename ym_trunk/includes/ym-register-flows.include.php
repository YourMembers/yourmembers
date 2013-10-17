<?php

/*
* $Id: ym-register-flows.include.php 2608 2013-02-22 10:47:57Z bcarlyon $
* $Revision: 2608 $
* $Date: 2013-02-22 10:47:57 +0000 (Fri, 22 Feb 2013) $
*/

$current_page = $next_page = $the_flow_id = 0;

function ym_register_flow($flow_id, $pack_id = false, $widget = false) {
	global $current_page, $next_page, $the_flow_id, $wpdb, $ym_res, $ym_sys;
	global $post_data, $pack_data;

	$html = $form_top = '';
	$payment_gateway_detected = false;

	if (!is_singular() && !$widget) {
		return __('A Register Flow Error Occurred (Type 0) Not on a Flow Page', 'ym');
	}
	
	if (!$flow_id) {
		return __('A Register Flow Error Occurred (Type 1) No Flow Selected', 'ym');
	}

	if (ym_post('flowcomplete')) {
		// complete
		$query = 'SELECT complete_text FROM ' . $wpdb->prefix . 'ym_register_flows WHERE flow_id = ' . $flow_id;
		$complete_text = $wpdb->get_var($query);
		if ($complete_text) {
			return '<p>' . $complete_text . '</p>';
		} else {
			return '<p>' . __('Registration/Upgrade is complete', 'ym') . '</p>';
		}
	}
	
	$flow_pages = 'SELECT flow_pages, complete_button FROM ' . $wpdb->prefix . 'ym_register_flows WHERE flow_id = ' . $flow_id;
	$flow_pages = $wpdb->get_row($flow_pages);

	if (!$flow_pages) {
		return __('A Register Flow Error Occurred (Type 2) Flow Not Found', 'ym');
	}

	$complete_button = $flow_pages->complete_button;

	$the_flow_id = $flow_id;
	// have a flow
	$flow_pages = unserialize($flow_pages->flow_pages);

	$last_page = ym_post('ym_register_flow_page', 0);
	$current_page = ym_post('ym_register_flow_next_page', 0);
	$flowcomplete = ym_post('flowcomplete', 0);
	
	if (!$current_page) {
		$copy = $flow_pages;
		$current_page = array_shift($copy);
	}
	
	$next_page = 0;
	while ($next_page == 0 && count($flow_pages)) {
		$page = array_shift($flow_pages);
		if ($page == $current_page) {
			$next_page = array_shift($flow_pages);
		}
	}

	//$permalink = get_permalink();
	$permalink = '';
	if(ym_superuser()){
		echo '<div class="ym_message"><p class="ym_message_liner">' . __('Warning, entering this flow may change your WordPress role', 'ym') . '</p></div>';
	}
	
	echo '
<style type="text/css">
	label {
		display: block;
	}
</style>
';
	$form = '
<form action="'.$permalink.'" method="post" enctype="multipart/form-data" id="ym_register_flow_form">
';
	$html .= $form;
	$form_top .= $form;

	$custom_data = get_option('ym_custom_fields');
	$custom_data = $custom_data->entries;
	
	// required?
	$required_data = isset($_POST['required']) ? $_POST['required'] : array();
	$ok = true;
	$email = true;
	$useremail = true;
	$username = true;
	$coupon = true;
	$dupepassword = true;

	$dont_hidden = array(
		'email_address',
		'username',
		'signed_request',
	);

	// maintaint
	$post_data = array();
	foreach ($_POST as $field => $entry) {
		if ($field != 'ym_register_flow_page' && $field != 'ym_register_flow_next_page' && $field != 'required' && $field != 'flowcomplete') {
			if (isset($required_data[$field]) && $required_data[$field] == 1 && !$entry) {
				$ok = false;
			}
			if ($field == 'email_address' && !is_email($entry)) {
				$email = false;
				$entry = '';
			} else if ($field == 'email_address') {
				// verify unique
				if (email_exists($entry)) {
					$useremail = false;
				}
			}
			if ($field == 'username') {
				if (username_exists($entry)) {
					$username = false;
				}
			}

			if ($field == 'coupon' && $entry) {
				$type = ym_post('coupon_type');
				if ($type == 'coupon_register') {
					$type = array(0);
				} else if ($type == 'coupon_upgrade') {
					$type = array(1);
				} else if (!is_int($type)) {
					// both
					$type = array(0, 1);
				}
				$value = false;
				$coupon_type = '';
				foreach ($type as $t) {
					$value = ym_validate_coupon($entry, $t);
					if ($value) {
						$coupon_type = $t;
						// TODO: register coupon use
						break;
					}
				}
				if ($value) {
					//valid
					$post_data['coupon_value'] = $value;
					$form = '<input type="hidden" name="coupon_value" value="' . $value . '" />';
					$post_data['coupon_type'] = $coupon_type;
					$form = '<input type="hidden" name="coupon_type" value="' . $coupon_type . '" />';
					$coupon = true;
				} else {
					// not valid
					$coupon = false;
				}
			}
			//YM duplicate password check
			if($field == 'ym_password'){
				if(ym_post('ym_password_check') || ym_post('ym_password_dupe')){
					$dupepassword = false;
					if(ym_post('ym_password') == ym_post('ym_password_check')){
						$dupepassword = true;
					}
				}
			}

			if (!isset($post_data[$field])) {
				$post_data[$field] = ym_post($field);//$entry;
				if (!in_array($field, $dont_hidden)) {
					$form = '<input type="hidden" name="' . $field . '" value="' . $entry . '" />
';
					$html .= $form;
					$form_top .= $form;
				}
			}
		}
	}
	
	$call_login = 0;
	if ($_POST) {
		if (!$ok || !$email || !$useremail || !$username || !$coupon || !$dupepassword) {
			$next_page = $current_page;
			$current_page = $last_page;
			
			if (!$email) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_email_invalid . '</p></div>';
			}
			if (!$useremail) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_email_inuse . '</p></div>';
			}
			if (!$username) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_username_inuse . '</p></div>';
			}
			if (!$ok) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_required_fields . '</p></div>';
			}
			if (!$coupon) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_invalid_coupon . '</p></div>';
			}
			if (!$dupepassword) {
				$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_invalid_password . '</p></div>';
			}

			$ok = false;
		}

		global $current_user;
		get_currentuserinfo();
		
		$username = $password = $fb_widget_ok = false;
		// check registation
		if ($ok) {
			if (!$current_user->ID) {
				$email = isset($post_data['email_address']) ? $post_data['email_address'] : '';
				$username = isset($post_data['username']) ? $post_data['username'] : '';
				$password = isset($post_data['password']) ? $post_data['password'] : '';
				
				if ($email) {
					// minimum for registeration
					if (!$username) {
						$username = $email;
					}
					if (username_exists($username)) {
						// register failed
						$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_username_inuse . '</p></div>';
					} else {
						// able to registers
						$ym_user = new YourMember_User();
						$user_id = $ym_user->create($email, false, true, $username, $password);
						wp_set_current_user($user_id);
						$call_login = 1;
					}
				} else if (ym_post('signed_request')) {
					$data = ym_facebook_uncode(ym_post('signed_request'));
					if ($data) {
						if ($data->registration) {
							// register!
							if (
								email_exists($data->registration->email)
								||
								username_exists($data->registration->email)
							) {
								$html .= '<div class="ym_message"><p class="ym_message_liner">' . $ym_res->registration_flow_email_inuse . '</p></div>';
							} else {
								$ym_user = new YourMember_User();
								$user_id = $ym_user->create(
									$data->registration->email,
									false, true,
									$data->registration->email,
									$data->registration->password,
									array(
										'first_name' => $data->registration->first_name,
										'last_name' => $data->registration->last_name,
									)
								);

								wp_set_current_user($user_id);
								$call_login = 1;

								$fb_widget_ok = true;
							}
						} else {
							$html .= '<div class="ym_message"><p class="ym_message_liner">' . __('Faecbook Registration Error (2)', 'ym') . '</p></div>';
						}
					} else {
						$html .= '<div class="ym_message"><p class="ym_message_liner">' . __('Faecbook Registration Error (1)', 'ym') . '</p></div>';
					}
				}
			} else {
				// update key user entries
				if (isset($post_data['username'])) {
					if ($username = $post_data['username']) {
						$query = 'UPDATE ' . $wpdb->users . ' SET user_login = \'' . $username . '\' WHERE ID = ' . $current_user->ID;
						$wpdb->query($query);
					}
				}
				if (isset($post_data['password'])) {
					if ($password = $post_data['password']) {
						$pw_hash = wp_hash_password($password);
						$query = 'UPDATE ' . $wpdb->users . ' SET user_pass = \'' . $pw_hash . '\' WHERE ID = ' . $current_user->ID;
						$wpdb->query($query);
						$call_login = 1;
					}
				}
			}
			
			// customs
			ym_update_custom_fields();
		}
	}

	$gateway_return = ym_request('gateway_return', false);
	if ($gateway_return) {
		// return from gateway into flow
		// all details dropped :-(
		$to_remove = array(
			'gateway_return',
			'item',
			'ym_register_flow_page',
			'ym_register_flow_next_page',
			'user_id'
		);

		$query = $_SERVER['QUERY_STRING'];
		foreach ($to_remove as $remove) {
			$query = preg_replace('/' . $remove . '\=' . "([a-zA-Z0-9_]+)/", '', $query);
		}
		while (substr($query, -1, 1) == '&') {
			$query = substr($query, 0, -1);
		}
		$html = str_replace('<form action=""', '<form action="?' . $query . '"', $html);

		$user_id = ym_request('user_id', false);
		if ($user_id) {
			$call_login = 1;
		}
	}
	$ym_register_user_id = ym_request('ym_register_user_id', false);
	if ($ym_register_user_id) {
		$call_login = 1;
		$user_id = $ym_register_user_id;
	}

	if ($call_login) {
		// temp login
		wp_set_current_user($user_id);
		$html .= '<input type="hidden" name="ym_register_user_id" value="' . $user_id . '" />';
	}
	
	unset($username);
	unset($password);

	$form = '
	<input type="hidden" name="ym_register_flow_page" value="' . $current_page . '" />
	<input type="hidden" name="ym_register_flow_next_page" value="' . $next_page . '" />
	';
	$html .= $form;
	$form_top .= $form;

	// data maintain whats left
	foreach ($post_data as $key => $item) {
		if (!in_array($key, $dont_hidden)) {
			$form = '
	<input type="hidden" name="' . $key . '" value="' . $item . '" />
	';
			$html .= $form;
			$form_top .= $form;
		}
	}
	
	// load
	$page = 'SELECT page_fields, button_text FROM ' . $wpdb->prefix . 'ym_register_pages WHERE page_id = ' . $current_page;
	$page = $wpdb->get_row($page);
	if (!$page) {
		return __('A Register Flow Error Occurred (Type 3) Page Not Found', 'ym');
	}

	$page_data = $page->page_fields;
	$next_button = $page->button_text;
	
	$page_data = unserialize($page_data);
	foreach ($page_data as $item => $field) {
		foreach ($field as $i => $f) {
			$page_data[$item][$i] = stripslashes(urldecode($f));
		}
	}


	$block_logic = array();

	// parse pack data
	$pack_data = false;
	if (isset($post_data['pack_id'])) {
		// load from form
		$pack_id = $post_data['pack_id'];
		// pass thru....
	}
	if ($pack_id) {
		$pack_data = ym_get_pack_by_id($pack_id);
	} else {
		// no pack id :-(
		// default
		$pack_order = ym_get_packs();
		$pack_data = array_shift($pack_order);
	}

	$first_button = true;

	foreach ($page_data as $index => $field_data) {
		$display = true;

		
		if ($field_data['iflogic']) {
			// block has logic
			$display = false;
			
			// evaulate the block logic result
			// is it a then or a else?
			switch ($field_data['iflogic']) {
				case 'loggedin':
					$match = $field_data['iflogic_quantity_loggedin'];
					$logged_in = is_user_logged_in();
					if ($logged_in && $match) {
						// user is logged on and the match is for logged in
						$this_logic = 'then';
					} else if (!$logged_in && !$match) {
						$this_logic = 'then';
					} else {
						$this_logic = 'else';
					}
					break;
					
				case 'buying':
					$match = $field_data['iflogic_quantity_pack'];

					if (isset($post_data['pack_id']) && $post_data['pack_id'] == $match) {
						$this_logic = 'then';
					} else {
						$this_logic = 'else';
					}
					break;
				
				case 'currentlyon':
					$match = $field_data['iflogic_quantity_pack'];

					if (is_user_logged_in()) {
						global $ym_user;
						$pack_id = $ym_user->pack_id ? $ym_user->pack_id : 0;

						if ($pack_id == $match) {
							$this_logic = 'then';
						} else {
							$this_logic = 'else';
						}
					} else {
						$this_logic = 'else';
					}
					break;
				
				case 'accounttype':
					$match = $field_data['iflogic_quantity_pack'];
					$match = strtolower($match);

					if (is_user_logged_in()) {
						global $ym_user;
						$account_type = $ym_user->account_type ? $ym_user->account_type : '';
						$account_type = strtolower($account_type);

						if ($account_type == $match) {
							$this_logic = 'then';
						} else {
							$this_logic = 'else';
						}
					} else {
						$this_logic = 'else';
					}
					break;
				
				case 'filledin':
					// custom field
					$field = $field_data['iflogic_quantity_custom'];
					$value = $field_data['iflogic_quantity_custom_compare'];

					if (is_user_logged_in()) {
						$customs = get_user_meta($current_user->ID, 'ym_custom_fields', true);

						$test = $customs->$field;
						if ($test == $value) {
							$this_logic = 'then';
						} else {
							$this_logic = 'else';
						}
					} else {
						$this_logic = 'else';
					} 

					break;

				case 'servervar':
				case 'getvar':
				case 'postvar':
				case 'cookievar':
					$source = '_' . substr($field_data['iflogic'], 0, -3);

					$match_name = $field_data['iflogic_quantity_field'];
					$match_value = $field_data['iflogic_quantity_entry'];

					$current_value = $source[$match_name];

					if ($current_value == $match_value) {
						$this_logic = 'then';
					} else {
						$this_logic = 'else';
					}
					break;

				case 'registeredfor':
//				case 'memberfor':
				case 'expiresin':
					$match_value = $field_data['iflogic_quantity_memberfor_value'];
					$match_unit = $field_data['iflogic_quantity_memberfor_unit'];

					if (is_user_logged_in()) {
						global $ym_user;

						if ($field_data['iflogic'] == 'registeredfor') {
							$math_date = strtotime($current_user->user_registered);
						} else if ($field_data['iflogic'] == 'expiresin') {
							$math_data = $ym_user->expire_date;
						} else {
							$math_date = '';
						}

						$seconds = ym_register_flow_date_math($match_value, $match_unit);

						$diff = time() - $math_date;
						if ($diff > $seconds) {
							$this_logic = 'then';
						} else {
							$this_logic = 'else';
						}
					} else {
						$this_logic = 'else';
					}
					break;

				default:
					$this_logic = 'else';
			}

			if (
				($this_logic == 'then' && $field_data['iflogic_showhide'] == 'show') ||
				($this_logic == 'else' && $field_data['iflogic_showhide'] == 'hide')
			) {
				$display = true;
			} else {
				$display = false;
			}
		}

		if ($field_data['label'] == 'page_logic' && (
			($this_logic == 'then' && $field_data['iflogic_showhide'] == 'hide') ||
			($this_logic == 'else' && $field_data['iflogic_showhide'] == 'hide')
		)) {
			$html .= '
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'#ym_register_flow_form\').submit();
	});
</script>
';
			$html .= '</form>';
			return $html;
		} else if ($field_data['label'] == 'page_logic') {
			// skip the IF and just skip the whole loop if should?
			continue;
		}
		
		/**
		 output render
		*/

		$html .= '<span class="' . $field_data['classes'] . '">';//open class span

		if ($field_data['types'] == 'freetext' && $display) {
			$html .= '<p>' . nl2br($field_data['names']) . '</p>';//names o.0 lol
		/**
		Customs Processor
		*/
		} else if ($field_data['types'] == 'custom' && $display) {
			// LIFTED FROM ym.php line 642
			// modded tooooo!
			if ($field_data['names'] == 'terms_and_conditions' && (!empty($ym_res->tos))) {
				$html .= '<p>
						<textarea name="tos" cols="29" rows="5" readonly="readonly">' . $ym_res->tos . '</textarea>';
				$html .= '</p>';
				$html .= '<p>
						<label class="ym_label" for="terms_and_conditions">
							<div><input type="checkbox" class="checkbox" name="terms_and_conditions" id="terms_and_conditions" value="1" />
							' . __('I agree to the Terms and Conditions.','ym') . '</div>
						</label>
					</p>' . "\n";
			} else if($field_data['names'] == 'ym_password'){
				$html .= '<label for="ym_password">' . $field_data['label'];
				$html .= '<input type="password" name="' . $field_data['names'] . '" value="" />';
				$html .= '<input type="hidden" name="required[' . $field_data['names'] . ']" value="' . $field_data['required'] . '" />';
					if ($field_data['required']) {
						$html .= $ym_sys->required_custom_field_symbol;
					}
				$html .= '</label>';
				if(!empty($field_data['options'])){
				$html .= '<label for="ym_password_check">'. __('Confirm Password','ym');
				$html .= '<input type="password" name="ym_password_check" value="" />';	
				$html .= '<input type="hidden" name="ym_password_dupe" value="1" />';	
				if ($field_data['required']) {
						$html .= $ym_sys->required_custom_field_symbol;
					}
				$html .='</label>';			}
			} else if ($field_data['names'] == 'subscription_introduction' && (!empty($ym_res->subs_intro))) {
				$html .= '<div class="ym_subs_intro">'. $ym_res->subs_intro .'</div>';
			} else if ($field_data['names'] == 'subscription_options') {
				global $ym_packs;
				
				$upsell_from = ym_request('gateway_return', false) ? $pack_id : false;

				// TO DO
				$pack_data = false;
				if ($pack_id && !ym_request('gateway_return', false)) {
					$pack_data = ym_get_pack_by_id($pack_id);
					
					if ($pack_data) {
						$label = ym_get_pack_label($pack_id);
						$html .= '<p>' . sprintf(__('You are subscribing to <b>%s</b>', 'ym'), $label) . '</p>';
						$html .= '<input type="hidden" name="pack_id" value="' . $pack_id . '" />';
					} else {
						return __('A Register Flow Error Occurred (Type 4) Specified Pack Not Found', 'ym');
					}
				} else {
					$ym_packs->packs = apply_filters('ym_packs', $ym_packs->packs);
					foreach ($ym_packs->packs as $pack) {
						if ($upsell_from == $pack['id']) {
							continue;
						}
						$label = ym_get_pack_label($pack['id']);
						
						$html .= '<label for="pack_id_' . $pack['id'] . '">';
						$html .= '<input type="radio" name="pack_id" id="pack_id_' . $pack['id'] . '" value="' . $pack['id'] . '" />';
						$html .= ' ' . $label . ' ';
						$html .= '</label>';
					}
				}
				
			} else if ($field_data['names'] == 'birthdate') {
				$html .= '<label for="ym_birthdate_month">' . $field_data['label'];
				$birthdate_fields = ym_birthdate_fields('ym_birthdate', ym_post('ym_birthdate_month', ''), ym_post('ym_birthdate_day', ''), ym_post('ym_birthdate_year', ''));
				$html .= $birthdate_fields;
				$html .= '<input type="hidden" name="required[ym_birthdate_month]" value="' . $field_data['required'] . '" />';
				$html .= '<input type="hidden" name="required[ym_birthdate_day]" value="' . $field_data['required'] . '" />';
				$html .= '<input type="hidden" name="required[ym_birthdate_year]" value="' . $field_data['required'] . '" />';
				if ($field_data['required']) {
					$html .= $ym_sys->required_custom_field_symbol;
				}
				$html .= '</label>';
			} else if ($field_data['names'] == 'country') {
				$html .= '<label for="ym_country">' . $field_data['label'];
				$countries_sel = ym_countries_list('ym_country', ym_post('ym_country', false));
				$html .= $countries_sel;
				$html .= '<input type="hidden" name="required[ym_country]" value="' . $field_data['required'] . '" />';
				if ($field_data['required']) {
					$html .= $ym_sys->required_custom_field_symbol;
				}
				$html .= '</label>';

			} else {
				
				// HERE
				$this_custom = '';
				foreach ($custom_data as $custom) {
					$label = $custom['label'];
					if (!$label) {
						$label = strtolower(str_replace(' ', '_', $custom['name']));
					}
					
					if ($label == $field_data['label']) {
						// found
						$this_custom = $custom;
						break;
					}
				}
				
				if ($this_custom) {
					$ro = ($this_custom['readonly'] ? 'readonly="readonly"':'');

					// check for special
					$value = $this_custom['value'];
					if (strpos($value, ':') !== false) {
						$array = explode(':', $value);
										
						if (count($array)) {
							switch($array[0]) {
								case 'cookie':
									$value = ym_cookie($array[1]);
									break;
								case 'session':
									$value = ym_session($array[1]);
									break;
								case 'get':
									$value = ym_get($array[1]);
									break;
								case 'post':
									$value = ym_post($array[1]);
									break;
								case 'request':
								case 'qs':
									$value = ym_request($array[1]);
									break;
								default:
									$value = '';
									break;
							}
										
							$this_custom['value'] = ym_post($this_custom['name'], $value);
						}
					} else if (is_user_logged_in()) {
						$this_custom['value'] = ym_custom_value($this_custom['id']);
					} else {
						$this_custom['value'] = ym_post($this_custom['name'], $this_custom['value']);

					}
					// ro adjust for fields that should not be changed
					switch ($this_custom['type']) {
						case 'password':
						case 'text':

							$html .= '<label for="' . $this_custom['name'] . '">' . $this_custom['label'];
							$html .= '<input type="' . $this_custom['type'] . '" name="' . $this_custom['name'] . '" value="' . $this_custom['value'] . '" ' . $ro . ' />';
							$html .= '<input type="hidden" name="required[' . $this_custom['name'] . ']" value="' . $field_data['required'] . '" />';
							if ($field_data['required'] && !$ro) {
								$html .= $ym_sys->required_custom_field_symbol;
							}
							$html .= '</label>';
							
							break;
						case 'hidden':
							$html .= '<input type="hidden" name="' . $this_custom['name'] . '" value="' . $this_custom['value'] . '" ' . $ro . ' />';
							break;

						case 'yesnocheckbox':
							$html .= '<label for="' . $this_custom['name'] . '">' . $this_custom['label'];
							$html .= '<input type="checkbox" name="' . $this_custom['name'] . '" value="1" ' . ($this_custom['value'] ? 'checked="checked"' : '') . ' ' . $ro . ' />';
							$html .= '<input type="hidden" name="required[' . $this_custom['name'] . ']" value="' . $field_data['required'] . '" />';
							if ($field_data['required'] && !$ro) {
								$html .= $ym_sys->required_custom_field_symbol;
							}
							$html .= '</label>';
							break;

						case 'yesno':
						case 'select':
						case 'multiselect':
							$html .= '<label for="' . $this_custom['name'] . '">' . $this_custom['label'];

							if ($this_custom['type'] == 'multiselect') {
								$html .= '<select name="' . $this_custom['name'] . '[]" multiple="multiple"';
							} else {
								$html .= '<select name="' . $this_custom['name'] . '" ';
							}

							$html .= '>';
							
							if ($this_custom['type'] == 'select' || $this_custom['type'] == 'multiselect') {
								$options = explode(';', $this_custom['available_values']);
							} else {
								$options = array(__('Yes', 'ym'), __('No', 'ym'));
							}
							
							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
									$html .= '<option value="' . $option . '" ' . ($option == $this_custom['value'] ? 'selected="selected"':'') . '>' . $val . '</option>';
								} else {
									$html .= '<option value="' . $option . '" ' . ($option == $this_custom['value'] ? 'selected="selected"':'') . '>' . $option . '</option>';
								}
							}

							$html .= '
</select>
';
							$html .= '<input type="hidden" name="required[' . $this_custom['name'] . ']" value="' . $field_data['required'] . '" />';
							if ($field_data['required'] && !$ro) {
								$html .= $ym_sys->required_custom_field_symbol;
							}
							$html .= '</label>';
							break;
						case 'textarea':
							$html .= '<label for="' . $this_custom['name'] . '">' . $this_custom['label'];
							$html .= '<textarea name="' . $this_custom['name'] . '" cols="29" rows="5" ' . $ro . '>' . $this_custom['value'] . '</textarea>';
							$html .= '<input type="hidden" name="required[' . $this_custom['name'] . ']" value="' . $field_data['required'] . '" />';
							if ($field_data['required'] && !$ro) {
								$html .= $ym_sys->required_custom_field_symbol;
							}
							$html .= '</label>';
							break;

						case 'file':
							$html .= '<label for="' . $this_custom['name'] . '">' . $this_custom['label'];
							$html .= '<input type="file" name="' . $this_custom['name'] . '" />';
							$html .= '</label>';
							break;
					}
				}
			}
			// END LIFT
		/**
		Buttons
		*/
		} else if (($field_data['types'] == 'payment_button' || $field_data['types'] == 'payment_action') && $display) {
			$payment_gateway_detected = true;

			add_filter('ym_additional_code', 'ym_register_flow_override_return', 10, 3);

			$enabled = get_option('ym_modules');

			// use the ym user id function
			if (ym_get_user_id()) {
				if (in_array($field_data['names'], $enabled)) {
					// register flow
					$class = $field_data['names'];
					$pay = new $class();

					if ($first_button) {
						$html .= '</form>';
						$first_button = false;
					}

					$this_pack = $pack_data;
					// coupon check
					if (isset($post_data['coupon_value']) && $post_data['coupon_value']) {
						// stop
						// stash
						$value = ym_apply_coupon($post_data['coupon'], $post_data['coupon_type'], $this_pack['cost']);
						$type = ym_get_coupon_type($value);

						if ($type == 'percent') {
							// percent cost change
							$this_pack['cost'] = ($this_pack['cost'] / 100) * $value;
						} else if ($type == 'sub_pack') {
							// diff pack
							$this_pack = ym_get_pack_by_id($value);
						} else {
							// other
							// new cost
							$this_pack['cost'] = $value;
						}

						ym_register_coupon_use($post_data['coupon'], ym_get_user_id(), 'buy_subscription_' . $pack_data['id']);

						if (!$this_pack['cost']) {
							// change to free
//							$field_data['names'] = 'ym_free';

// lifted from 135 of ym-register.include.php
$code_to_use = 'freebie_code';
// attempt to redirect to the processor.
$loc = $ym_home .'/index.php?ym_process=ym_free&' . $code_to_use . '=buy_subscription_' . $this_pack['id'] .'_' . ym_get_user_id();

if (!headers_sent()) {
	header('Location: ' . $loc);
	exit;
} else {
	echo '<script type="text/javascript">window.location = "'. $loc .'";</script>';
}
die;

						}
					}

					// there will always be pack data becuase I picked the default one earlier
					// but it will default to the default pack anyway
					if ($this_pack['cost']) {// && $field_data['names'] != 'ym_free') {
//						$gw_button_form = $pay->getButton($this_pack['id'], (isset($post_data['coupon_value']) ? $this_pack['cost'] : false));
//						$html .= $gw_button_form;

						$gw_button_form = $pay->getButton($this_pack['id'], (isset($post_data['coupon_value']) ? $this_pack['cost'] : false));
						if ($field_data['types'] == 'payment_action') {
							if (method_exists($pay, 'register_auto_payment_action')) {
								$html .= $pay->register_auto_payment_action($this_pack['id'], (isset($post_data['coupon_value']) ? $this_pack['cost'] : false), true);
							} else if ($gw_button_form) {
								$html .= $gw_button_form . '
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'.' . $field_data['names'] . '_form\').submit();
	});
</script>
';
							}
						} else if (method_exists($pay, 'register_payment_action')) {
							$html .= $pay->register_payment_action(true);
						} else {
							$html .= $gw_button_form;
						}
					} else if (!$this_pack['cost'] && $field_data['names'] == 'ym_free') {
						// free
						$gw_button_form = $pay->getButton($this_pack['id'], false);
						$html .= $gw_button_form;
						
						if ($field_data['types'] == 'payment_action') {
							if (method_exists($pay, 'register_auto_payment_action')) {
								$html .= $pay->register_auto_payment_action($this_pack['id'], false, true);
							} else if ($gw_button_form) {
								$html .= '
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'.ym_free_form\').submit();
	});
</script>
';							}
						} else if (method_exists($pay, 'register_payment_action')) {
							$html .= $pay->register_payment_action(true);
						}
					} else if (isset($post_data['coupon_value']) && $post_data['coupon_value']) {
						return __('A Register Flow Error Occurred (Type 5) Pack/Coupon Error', 'ym');
					}
				}
			} else {
				// user not logged in // No User ID Determined
				return __('In order to continue you need to Register or Login', 'ym');
			}
		/**
		Widgets
		*/
		} else if ($field_data['types'] == 'widget' && $display) {
			if ($field_data['names'] == 'login') {
				// login form
				$html .= '
	<input type="hidden" name="ym_register_flow_page" value="' . $current_page . '" />
	<input type="hidden" name="ym_register_flow_next_page" value="' . $current_page . '" />
	';
//	<input type="hidden" name="ym_register_flow_do_login" value="1" />';
				$html .= ym_login_form();
			} else if ($field_data['names'] == 'register_facebook') {
				// check for and handle a signed request
				if ($fb_widget_ok) {
					// skippy
					$html .= '
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery(\'#ym_register_flow_form\').submit();
						});
					</script>
					';
				} else {
					$html .= '
<iframe src="https://www.facebook.com/plugins/registration?
             client_id=' . get_option('ym_register_flow_fb_app_id') . '&
             redirect_uri=' . get_permalink() . '&
             fields=name,email,first_name,last_name,password"
        scrolling="auto"
        frameborder="no"
        style="border:none"
        allowTransparency="true"
        width="100%"
        height="550">
</iframe>
';
					// no next/complete please
				}
				$payment_gateway_detected = true;
			} else {
				$html .= 'Undefined Widget: (' . $field_data['names'] . ')';
			}
		/**
		Coupon
		*/
		} else if ($field_data['names'] == 'coupon' && $display) {
			$value = isset($post_data[$field_data['names']]) ? $post_data[$field_data['names']] : '';

			$html .= '<label for="' . $field_data['names'] . '">' . $field_data['label'];
			$html .= '<input type="text" name="' . $field_data['names'] . '" id="' . $field_data['names'] . '" value="' . $value . '" />';
			$html .= '<input type="hidden" name="coupon_type" value="' . $field_data['types'] . '" />';
			$html .= '<input type="hidden" name="required[' . $field_data['names'] . ']" value="' . $field_data['required'] . '" />';
			if ($field_data['required']) {
				$html .= $ym_sys->required_custom_field_symbol;
			}
			$html .= '</label>';
		/**
		Display everything else
		*/
		} else if ($display) {
			$value = isset($post_data[$field_data['names']]) ? $post_data[$field_data['names']] : '';

			$html .= '<label for="' . $field_data['names'] . '">' . $field_data['label'];
			$html .= '<input type="' . $field_data['types'] . '" name="' . $field_data['names'] . '" id="' . $field_data['names'] . '" value="' . $value . '" />';
			$html .= '<input type="hidden" name="required[' . $field_data['names'] . ']" value="' . $field_data['required'] . '" />';
			if ($field_data['required']) {
				$html .= $ym_sys->required_custom_field_symbol;
			}
			$html .= '</label>';
		}

		$html .= '</span>';//closes class span
	}

	if (!$first_button) {
		// kill id
		$html = str_replace('id="ym_register_flow_form"', '', $html);

		// complete?
		if (!$next_page) {
			$query = 'SELECT complete_url FROM ' . $wpdb->prefix . 'ym_register_flows WHERE flow_id = ' . $flow_id;
			if ($url = $wpdb->get_var($query)) {
				$url = site_url($url);
				$form_top = str_replace('<form action=""', '<form action="' . $url . '"', $form_top);
			}
		}

		// append the form top
		$html .= $form_top;
		// end it
	}
	
	// payment gateway?
	if (!$payment_gateway_detected) {
		$html .= '<p>';
		if ($next_page) {
			$html .= '<input type="submit" value="' . $next_button . '" />';
		} else {
			$html .= '
<input type="hidden" name="flowcomplete" value="1" />
<input type="submit" value="' . $complete_button . '" />';
		}
		$html .= '</p>';
	}
	
	$html .= '</form>';

	return $html;
}

function ym_register_catch_gateway() {
	if (ym_get('gateway_return') && !ym_post('ym_did_gateway_return')) {

		// callback script
		$_GET['from_gateway'] = ym_get('gateway_return');
		ym_login_js();

		// continue
		echo '
<form action="" method="post" id="ym_register_flow_form">
	<input type="hidden" name="ym_register_flow_page" value="' . $_REQUEST['ym_register_flow_page'] . '" />
	<input type="hidden" name="ym_register_flow_next_page" value="' . $_REQUEST['ym_register_flow_next_page'] . '" />
	';
	if (!$_REQUEST['ym_register_flow_next_page']) {
		echo '<input type="hidden" name="flowcomplete" value="1" />';
	}
	echo '
	<input type="hidden" name="ym_did_gateway_return" value="1" />
	<input type="submit" value="' . __('Continue', 'ym') . '" />
</form>	

<script type="text/javascript">
	document.forms["ym_register_flow_form"].submit();
</script>
';
		exit;
	}
}

function ym_register_flow_override_return($additional_code, $gateway, $item) {
	global $current_page, $next_page, $the_flow_id, $wpdb;

	$url = get_permalink();
	if (!$next_page) {
		$query = 'SELECT complete_url FROM ' . $wpdb->prefix . 'ym_register_flows WHERE flow_id = ' . $the_flow_id;
		if ($newurl = $wpdb->get_var($query)) {
			$url = site_url($newurl);
		}
	}

	if (strpos($url, '?')) {
		$url .= '&';
	} else {
		$url .= '?';
	}
	$url .= 'gateway_return=' . $gateway;
	$url .= '&item=' . $item;
	$url .= '&ym_register_flow_page=' . $current_page;
	$url .= '&ym_register_flow_next_page=' . $next_page;
	$url .= '&user_id=' . ym_get_user_id();

	switch ($gateway) {
		case 'ym_2checkout':
			$additional_code['custom_return_url'] = $url;
			break;
		case 'ym_authorize_net':
			$additional_code['x_custom_2'] = $url;
			break;
		case 'ym_free':
		case 'ym_paypal':
		case 'ym_paypal_pro':
		case 'ym_stripe':
			$additional_code['return'] = $url;
			break;
		case 'ym_skirll':
			$additional_code['return_url'] = $url;
			break;
		case 'ym_worldpay':
			$additional_code['M_return'] = $url;
			break;
		case 'ym_zombaio':
			$additional_code['return_url_approve'] = $url;
	}

	return $additional_code;
}

// units: hour day week month year
function ym_register_flow_date_math($value, $unit) {
	switch ($unit) {
		case 'year':
			$time = $value * 31536000;//365days
			break;
		case 'month':
			$time = $value * 16934400;//28dayslater
			break;
		case 'week':
			$time = $value * 604800;
			break;
		case 'day':
			$time = $value * 86400;
			break;
		case 'hour':
			$time = $value * 3600;
	}

	return $time;
}

function ym_get_flows_dropdown($name='flow_id', $value=false, $return=true) {
	global $wpdb;
	$flows_table = $wpdb->prefix . 'ym_register_flows';

	$query = 'SELECT * FROM ' . $flows_table . ' ORDER BY flow_id ASC';
	$flows = $wpdb->get_results($query);
	$flow_pages = $row->flow_pages;
	if($flows){
	
		$html = '<select class="ym_flow_dropdown" id="' . $name . '" name="' . $name . '">';
		foreach ($flows as $flow) {
			$selected = ym_selected($value, $flow->flow_id);
			$html .= '<option ' . $selected . ' value="' . $flow->flow_id . '">' . $flow->flow_name . '</option>';
		}
		$html .= '</select>';
	}
	else{
		$html =  __('You need to create at least 1 flow', 'ym');
	}
	
	
	
	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}
