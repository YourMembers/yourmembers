<?php

/*
* $Id: ym-register.include.php 2529 2013-01-17 11:58:50Z bcarlyon $
* $Revision: 2529 $
* $Date: 2013-01-17 11:58:50 +0000 (Thu, 17 Jan 2013) $
*/

/**
Select a default pack
*/
function ym_get_default_pack() {
	global $ym_packs;
	$packs = $ym_packs->packs;
	$return = false;
	$zero = false;
	foreach ($packs as $pack) {
		$zero = $zero ? $zero : ($pack['hide_subscription'] ? false : $pack['id']);
		if (isset($pack['default']) && $pack['default']) {
			$return = $pack['id'];
			break;
		}
	}
	if (!$return) {
		$return = $zero;
	}

	return $return;
}
/**
Default Registration when modified is off
*/

function ym_register_default($user_id) {
	global $wpdb;
	
	if (!isset($_SESSION['error_on_page'])) {
		$pack_id = ym_get_default_pack();

		if (!$user_pass = ym_post('ym_password')) {
			$user_pass = substr(md5(uniqid(microtime())), 0, 7);
		}
			
		$user_pass_md5 = md5($user_pass);

		$wpdb->query("UPDATE $wpdb->users SET user_pass = '$user_pass_md5' WHERE ID = '$user_id'");
	
		wp_new_user_notification($user_id, $user_pass);

		// redirect to ym_subscribe
		$userdata = get_userdata($user_id);
	
		$redirect = add_query_arg(array('username'=>$userdata->user_login, 'ym_subscribe'=>1), get_option('siteurl') );
		
		if ($redirector = ym_post('ym_redirector', ym_post('redirect_to'))) {
			$redirect = add_query_arg(array('redirector'=>$redirector), $redirect);
		}
					
		$redirect = add_query_arg(array('pack_id'=>$pack_id), $redirect);
		wp_redirect($redirect);
	
		exit;
	}
}
/**
End Default Registration when modified is off
*/

/**
Remove The Password Emailed to You String
Caller/setup
Actioner
*/
function ym_login_remove_password_string() {
	add_filter('gettext', 'ym_login_remove_password_string_gettext', 20, 3);
}
function ym_login_remove_password_string_gettext($string) {
	$text = 'A password will be e-mailed to you.';
	if ($text == $string) {
		return '';
	}
	return $string;
}

/**
Non Flow Register/Upgrade
*/

function ym_available_modules($username=false, $return=false, $coupon_type = 0) {
//	echo 'ym_available_modules: ' . $username . ', ' . $return . ', ' . $coupon_type . '<br />';
	global $ym_active_modules, $ym_packs;

	//coupons
	$user_id = ym_get_user_id();
	$ym_custom_fields = ym_get_custom_field_array($user_id);

	$ym_home = site_url();
	$base = $ym_home . '/index.php?ym_subscribe=1&username=' . $username;

	$html = '';
	if ($pack_id = ym_get('pack_id')) {
		// pack ID has been selected

		$pack = ym_get_pack_by_id($pack_id);
		// strip commas
		$cost = str_replace(',', '', $pack['cost']);
		$override = FALSE;

		$code_to_use = 'custom';
		// coupon check
		if (isset($ym_custom_fields['coupon']) && $ym_custom_fields['coupon']) {
			$cost = ym_apply_coupon($ym_custom_fields['coupon'], $coupon_type, $cost);
			if (substr($cost, 0, 4) == 'pack') {
				$pack_id = substr($cost, 5);
				// apply new pack
				$pack = ym_get_pack($pack_id);
				// import data
				$cost = $pack['cost'];
				$duration = $pack['duration'];
				$duration_type = $pack['duration_type'];
				$account_type  = $pack['account_type'];
				$num_cycles = $pack['num_cycles'];
			} else {
				// makre sure formatted ok
				$cost = number_format($cost, 2);
				$override = $cost;
				$code_to_use = 'freebie_code';
			}

			ym_register_coupon_use($ym_custom_fields['coupon'], ym_get_user_id(), 'buy_subscription_' . $pack_id);
		}

		// is it free?
		if ($cost == 0) {
			// auto redirect
			$redirector = ym_get('redirector');
			// attempt to redirect to the processor.
			// if attempt fails, we show the button
			$loc = $ym_home .'/index.php?ym_process=ym_free&' . (ym_get('ym_autologin') ? 'ym_autologin=1&':'') . $code_to_use . '=buy_subscription_' . $pack['id'] .'_' . ym_get_user_id() . '&redirector='. urlencode($redirector);
							
			if (!headers_sent()) {
				header('Location: ' . $loc);
				exit;
			} else {
				echo '<script type="text/javascript">window.location = "'. $loc .'";</script>';
			}
			die;
		}

		// gateway selection BuyNow
		$shown = 0;
		$shown_name = '';
		$shown_button = '';

		foreach ($ym_active_modules as $module) {
			$get_button = FALSE;

			if ($module == 'ym_free') {
				continue;
			} else {
				// do pack gateway check
				$get_button = TRUE;
			}

			if ($get_button) {
				$$module = new $module();

				$this_button = $$module->getButton($pack_id, $override, 'ym_available_modules');
				// a button pay not be returned (pack restrict gateway)
				if ($this_button) {
					$shown_name = $module;
					$shown_button = $this_button;
					$shown++;

					$html .= $this_button;
				}
			}
		}
		
		if ($shown == 0) {
			$html .= __('There are no payment gateways available at this time.','ym');
		} else if ($shown == 1) {
			if (!method_exists($$shown_name, 'abort_auto')) {
				// TODO: Are we on a page where HTML has been outputted?
				// auto fire
				$html = '<html>
						<head>
							<title>Redirecting...</title>
							<script type="text/javascript">
								function load() {
									document.forms["' . $shown_name . '_form"].submit();
								}
							</script>
						</head>
						<body onload="load();">';
				$html .= '<div style="display: none;">' . $shown_button . '</div>'
					. '<div style="color: #333333; font-size: 14px; margin: 180px 250px; font-family: tahoma; text-align: center; padding: 50px; border: 1px solid silver;" id="ym_pay_redirect">'
					. '<div>You are being redirected. If this page does not refresh in 5 seconds then click <a onclick="document.forms[\'' . $module . '_form\'].submit();">here</a>.</div>'
					. '<div style="margin-top: 10px;"><img alt="" src="' . YM_IMAGES_DIR_URL . 'loading.gif" /></div>'
					. '</div>'
					. '</body></html>';

				echo $html;
				die;
			} else {
				// aborted the auto fire step
				$html .= $$shown_name->abort_auto();
			}
		}
	} else if (!ym_get('sel', FALSE)) {
		$html .= '<table width="100%" cellpadding="3" cellspacing="0" border="0" align="center" class="like_form">'
			. '<tr>'
			. '<th><h3 class="ym_register_heading">' . __('Select Payment Gateway','ym') . '</h3></th>'
			. '</tr>';
		/**
		No Gateway Selected
		Show Gateway Selection
		*/
		$shown = 0;
		$shown_name = '';
		
		foreach ($ym_active_modules as $module) {
			$pay = new $module();

			$packs = $ym_packs->packs;
			$packs = apply_filters('ym_packs', $packs, $pay->code);

			if (count($packs)) {
				$html .= '<tr>'
					. '<td align="center" style="padding: 5px; text-align: center;">'
					. '<a href="' . $base . '&sel=' . $module . '">'
					. '<div class="ym_module_name"><strong>' . $pay->name . '</strong></div>'
					. '<img class="ym_module_logo" src="'. $pay->logo .'" alt="'. $pay->name .'" title="'. $pay->name .'" />'
					. '</a>'
					. '</td>'
					. '</tr>';
				$shown++;
				$no_gateway = FALSE;
				$shown_name = $module;
			}

			unset($pay, $packs);
		}

		$html .= '</table>';

		if ($shown == 0) {
			$html .= __('There are no payment gateways available at this time.','ym');
		} else if ($shown == 1) {
			// we only have one to show....
			// auto fire
			$loc = $base . '&sel=' . $shown_name;
			if (!headers_sent()) {
				header('Location: ' . $loc);
			} else {
				echo '<script type="text/javascript">window.location="'. $loc . '";</script>';
			}
			exit;
		}
	} else if ($selected = ym_get('sel')) {
		/**
		Gateway selected
		Show Buy Now Buttons for this gateway
		*/
		// user has selected a gateway
		if (!class_exists($selected)) {
			wp_die(sprintf(__('Unknown Module: %s', 'ym'), $selected));
		}

		$pay = new $selected();
		$html .= $pay->generateButtons(true);
	} else {
		/**
		Should not get here
		*/
		wp_die(__('An error Occured (Code: YM_AVAILABLE_MODULES'));
	}

	/**
	Return
	*/
	if ($return) {
		return $html;
	} else {
		echo $html;
		return;
	}
}
